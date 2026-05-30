<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionLineItem;
use App\Models\DocumentIntake;
use App\Models\DocumentPostingLog;
use App\Models\FileAttachment;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorDocumentLineItem;
use PDO;
use RuntimeException;

/**
 * Creates or updates application records once a document intake has been
 * approved. For the first complete version this handles vendor_receipt /
 * vendor_bill / purchase_order → existing vendor_documents.
 *
 * Other document types (invoices, payments, warranty, etc.) are still kept
 * in the document_intakes queue as approved but not yet posted, and the
 * intake remains the source of truth until a posting handler is added.
 */
final class DocumentPostingService
{
    public function __construct(private ?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Map AI line-item category enum onto the existing VendorDocumentLineItem
     * category enum. Unknown / unmapped values fall back to 'other'.
     */
    private const CATEGORY_MAP = [
        'resold_part' => 'resold_part',
        'inventory_part' => 'inventory_part',
        'consumable' => 'consumable',
        'tool_or_equipment' => 'tool_equipment',
        'ppe_or_work_supplies' => 'ppe',
        'fuel' => 'fuel',
        'food_or_personal' => 'meal_personal',
        'office_expense' => 'office',
        'vehicle_expense' => 'other',
        'other_expense' => 'other',
        'unknown' => 'other',
    ];

    /**
     * Post an approved intake into the appropriate application records.
     * Returns an array describing what was created / updated.
     */
    public function post(int $documentIntakeId): array
    {
        $intakeModel = new DocumentIntake();
        $intake = $intakeModel->find($documentIntakeId);
        if (!$intake) {
            throw new RuntimeException('Document intake not found.');
        }

        if ($intake['status'] !== 'approved') {
            throw new RuntimeException('Approve the document before posting.');
        }

        $documentType = (string) ($intake['detected_document_type'] ?? '');
        $vendorPostable = ['vendor_receipt', 'vendor_bill', 'purchase_order'];

        if (!in_array($documentType, $vendorPostable, true)) {
            // Mark posted with no downstream record so the operator can decide
            // what to do next manually. Posting handlers for other document
            // types ship in later phases.
            $intakeModel->updateStatus($documentIntakeId, 'posted');
            (new DocumentPostingLog())->record(
                $documentIntakeId,
                'document_intake',
                $documentIntakeId,
                'noop_unsupported_type',
                ['document_type' => $documentType],
                ['note' => 'No automatic posting handler for this document type yet.']
            );
            return [
                'status' => 'posted_without_record',
                'document_type' => $documentType,
            ];
        }

        return $this->postAsVendorDocument($intake);
    }

    private function postAsVendorDocument(array $intake): array
    {
        $intakeId = (int) $intake['id'];
        $extraction = (new DocumentExtraction())->forIntake($intakeId);
        $normalized = $extraction && !empty($extraction['normalized_json'])
            ? json_decode((string) $extraction['normalized_json'], true)
            : null;
        $normalized = is_array($normalized) ? $normalized : [];

        $lineModel = new DocumentExtractionLineItem();
        $lines = $lineModel->forIntake($intakeId);

        $this->db->beginTransaction();
        try {
            $vendorId = $this->resolveVendor($intake, $normalized);

            $documentType = $this->vendorDocumentType((string) $intake['detected_document_type']);
            $financial = is_array($normalized['financial_summary'] ?? null)
                ? $normalized['financial_summary']
                : [];

            $vendorDocModel = new VendorDocument();
            $vendorDocumentId = $vendorDocModel->create([
                'vendor_id' => $vendorId,
                'document_type' => $documentType,
                'external_document_number' => $normalized['document_number'] ?? null,
                'document_date' => $normalized['document_date'] ?? null,
                'total' => (float) ($financial['total'] ?? 0),
                'tax_total' => (float) ($financial['tax'] ?? 0),
                'payment_method' => $this->vendorPaymentMethod($normalized),
                'notes' => $this->postingNotes($intake, $normalized),
            ]);

            // Reuse the uploaded file by registering a fresh file_attachment row
            // pointing at the same stored file so the existing vendor-document
            // attachment relationship works without copying the file.
            $attachmentId = (new FileAttachment())->create([
                'related_type' => 'vendor_document',
                'related_id' => $vendorDocumentId,
                'file_type' => 'document',
                'file_path' => $intake['file_path'],
                'original_filename' => $intake['original_filename'],
                'mime_type' => $intake['file_mime_type'],
                'file_size' => (int) $intake['file_size'],
                'caption' => 'From document intake ' . $intake['document_number'],
                'uploaded_by' => Auth::userId(),
            ]);
            $vendorDocModel->attachFile($vendorDocumentId, $attachmentId);

            $vendorLineModel = new VendorDocumentLineItem();
            foreach ($lines as $line) {
                $category = $this->mapCategory($line);
                $vendorLineModel->create($vendorDocumentId, [
                    'item_name' => (string) ($line['description'] ?? 'Unnamed item'),
                    'part_number' => $line['manufacturer_part_number']
                        ?? $line['vendor_part_number']
                        ?? $line['sku']
                        ?? null,
                    'category' => $category,
                    'quantity' => (float) ($line['quantity'] ?? 1),
                    'unit_cost' => (float) ($line['unit_price'] ?? 0),
                    'reviewed_flag' => 1,
                ]);
            }
            $vendorDocModel->recalculate($vendorDocumentId);

            (new DocumentIntake())->setPostedVendorDocument($intakeId, $vendorDocumentId);
            (new DocumentIntake())->updateStatus($intakeId, 'posted');

            (new DocumentPostingLog())->record(
                $intakeId,
                'vendor_document',
                $vendorDocumentId,
                'created',
                null,
                [
                    'vendor_id' => $vendorId,
                    'document_type' => $documentType,
                    'line_count' => count($lines),
                    'total' => (float) ($financial['total'] ?? 0),
                ]
            );

            $this->db->commit();

            return [
                'status' => 'posted',
                'vendor_document_id' => $vendorDocumentId,
                'vendor_id' => $vendorId,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function resolveVendor(array $intake, array $normalized): ?int
    {
        if (!empty($intake['related_vendor_id'])) {
            return (int) $intake['related_vendor_id'];
        }

        $name = isset($normalized['source_party']['name'])
            ? trim((string) $normalized['source_party']['name'])
            : '';
        if ($name === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT id FROM vendors WHERE LOWER(name) = LOWER(:n) LIMIT 1');
        $stmt->execute(['n' => $name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        // Auto-create vendor from the extracted source_party so the posted
        // expense isn't orphaned. The operator can edit details later.
        return (new Vendor())->create([
            'name' => $name,
            'phone' => $normalized['source_party']['phone'] ?? null,
            'email' => $normalized['source_party']['email'] ?? null,
            'address' => $normalized['source_party']['address'] ?? null,
            'website' => null,
            'notes' => 'Auto-created from document intake ' . $intake['document_number'],
            'status' => 'active',
        ]);
    }

    private function vendorDocumentType(string $detected): string
    {
        return match ($detected) {
            'vendor_bill' => 'invoice',
            'purchase_order' => 'purchase_order',
            default => 'receipt',
        };
    }

    private function vendorPaymentMethod(array $normalized): ?string
    {
        $raw = strtolower(trim((string) ($normalized['payment']['payment_method'] ?? '')));
        if ($raw === '') {
            return null;
        }
        $map = [
            'cash' => 'cash',
            'check' => 'check',
            'cheque' => 'check',
            'card' => 'card',
            'credit' => 'card',
            'credit card' => 'card',
            'debit' => 'card',
            'visa' => 'card',
            'mastercard' => 'card',
            'amex' => 'card',
            'discover' => 'card',
            'ach' => 'ach',
            'wire' => 'ach',
            'unpaid' => 'unpaid',
        ];
        return $map[$raw] ?? 'other';
    }

    private function postingNotes(array $intake, array $normalized): string
    {
        $parts = ['Posted from document intake ' . $intake['document_number'] . '.'];
        if (!empty($normalized['raw_text_summary'])) {
            $parts[] = 'Summary: ' . $normalized['raw_text_summary'];
        }
        if (!empty($intake['notes'])) {
            $parts[] = 'Intake notes: ' . $intake['notes'];
        }
        return implode("\n", $parts);
    }

    private function mapCategory(array $line): string
    {
        $candidate = $line['reviewed_category'] ?? $line['category_guess'] ?? null;
        if (!is_string($candidate) || $candidate === '') {
            return 'other';
        }
        return self::CATEGORY_MAP[$candidate] ?? 'other';
    }
}
