<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class VendorDocument extends Repository
{
    public const TYPES = ['receipt', 'invoice', 'quote', 'purchase_order', 'warranty', 'other'];
    public const STATUSES = ['uploaded', 'needs_review', 'approved', 'posted', 'rejected'];
    public const PAYMENT_METHODS = ['cash', 'check', 'card', 'ach', 'unpaid', 'other'];

    public function all(): array
    {
        return $this->db->query(
            'SELECT vd.*, v.name AS vendor_name
             FROM vendor_documents vd
             LEFT JOIN vendors v ON v.id = vd.vendor_id
             ORDER BY vd.created_at DESC, vd.id DESC'
        )->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT vd.*, v.name AS vendor_name,
                    fa.file_path, fa.original_filename, fa.mime_type, fa.file_size
             FROM vendor_documents vd
             LEFT JOIN vendors v ON v.id = vd.vendor_id
             LEFT JOIN file_attachments fa ON fa.id = vd.file_attachment_id
             WHERE vd.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO vendor_documents (
                document_number, vendor_id, document_type, external_document_number,
                document_date, total, tax_total, subtotal, status, payment_method, notes,
                uploaded_at, created_at, updated_at
            ) VALUES (
                :document_number, :vendor_id, :document_type, :external_document_number,
                :document_date, :total, :tax_total, :subtotal, :status, :payment_method, :notes,
                :uploaded_at, :created_at, :updated_at
            )'
        );

        $total = round((float) ($data['total'] ?? 0), 2);
        $taxTotal = round((float) ($data['tax_total'] ?? 0), 2);
        $subtotal = round($total - $taxTotal, 2);

        $stmt->execute([
            'document_number' => NumberingService::next('VDC'),
            'vendor_id' => !empty($data['vendor_id']) ? (int) $data['vendor_id'] : null,
            'document_type' => in_array($data['document_type'] ?? 'receipt', self::TYPES, true) ? $data['document_type'] : 'receipt',
            'external_document_number' => $data['external_document_number'] ?: null,
            'document_date' => $data['document_date'] ?: null,
            'total' => $total,
            'tax_total' => $taxTotal,
            'subtotal' => $subtotal,
            'status' => 'uploaded',
            'payment_method' => in_array($data['payment_method'] ?? '', self::PAYMENT_METHODS, true) ? $data['payment_method'] : null,
            'notes' => $data['notes'] ?: null,
            'uploaded_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function attachFile(int $id, int $fileAttachmentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vendor_documents SET file_attachment_id = :file_attachment_id, updated_at = :updated_at WHERE id = :id'
        );
        $stmt->execute([
            'file_attachment_id' => $fileAttachmentId,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function recalculate(int $id): void
    {
        $sumStmt = $this->db->prepare('SELECT COALESCE(SUM(line_total), 0) FROM vendor_document_line_items WHERE vendor_document_id = :id');
        $sumStmt->execute(['id' => $id]);
        $lineSum = round((float) $sumStmt->fetchColumn(), 2);

        $current = $this->findWithDetails($id);
        if (!$current) {
            return;
        }

        $taxTotal = round((float) $current['tax_total'], 2);
        $headerTotal = round((float) $current['total'], 2);
        $hasHeaderTotal = $headerTotal > 0;
        $subtotal = $hasHeaderTotal ? round($headerTotal - $taxTotal, 2) : $lineSum;
        $total = $hasHeaderTotal ? $headerTotal : round($lineSum + $taxTotal, 2);

        $stmt = $this->db->prepare(
            'UPDATE vendor_documents
             SET subtotal = :subtotal, total = :total, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'subtotal' => $subtotal,
            'total' => $total,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function updateStatus(int $id, string $status): ?array
    {
        if (!in_array($status, self::STATUSES, true)) {
            return null;
        }

        $current = $this->findWithDetails($id);
        if (!$current) {
            return null;
        }

        $extra = '';
        $params = [
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ];

        if ($status === 'approved' && empty($current['approved_at'])) {
            $extra = ', approved_at = :approved_at';
            $params['approved_at'] = $this->now();
        }

        $stmt = $this->db->prepare("UPDATE vendor_documents SET status = :status, updated_at = :updated_at{$extra} WHERE id = :id");
        $stmt->execute($params);

        return [
            'old_status' => $current['status'],
            'new_status' => $status,
        ];
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (!in_array($data['document_type'] ?? '', self::TYPES, true)) {
            $errors['document_type'] = 'Choose a document type';
        }

        $total = $data['total'] ?? '';
        if (!is_numeric($total) || (float) $total < 0) {
            $errors['total'] = 'Enter a non-negative total';
        }

        $tax = $data['tax_total'] ?? '0';
        if (!is_numeric($tax) || (float) $tax < 0) {
            $errors['tax_total'] = 'Enter a non-negative tax amount';
        }

        if ((float) $tax > (float) ($total ?: 0)) {
            $errors['tax_total'] = 'Tax cannot exceed the total';
        }

        if (!empty($data['document_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['document_date'])) {
            $errors['document_date'] = 'Use YYYY-MM-DD';
        }

        if (!empty($data['payment_method']) && !in_array($data['payment_method'], self::PAYMENT_METHODS, true)) {
            $errors['payment_method'] = 'Choose a valid payment method';
        }

        return $errors;
    }
}
