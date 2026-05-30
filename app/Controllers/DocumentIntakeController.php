<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionLineItem;
use App\Models\DocumentIntake;
use App\Models\DocumentMatch;
use App\Models\DocumentPostingLog;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Services\DocumentMatchingService;
use App\Services\DocumentPostingService;
use App\Services\ImageStagingService;
use App\Services\OpenAiDocumentExtractionService;

final class DocumentIntakeController extends Controller
{
    private const MAX_BYTES = 15 * 1024 * 1024;
    private const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    public function index(): void
    {
        $intakeModel = new DocumentIntake();
        $statusFilter = $this->query('status');
        if ($statusFilter !== null && !in_array($statusFilter, DocumentIntake::STATUSES, true)) {
            $statusFilter = null;
        }

        $this->view('layouts/app', [
            'title' => 'Document Intake',
            'active' => 'document-intake',
            'content' => 'document-intake/index',
            'documents' => $intakeModel->all($statusFilter),
            'counts' => $intakeModel->statusCounts(),
            'currentStatus' => $statusFilter,
            'usage' => (new DocumentExtraction())->totalUsage(),
        ]);
    }

    public function upload(): void
    {
        $this->renderUploadForm([], []);
    }

    public function store(): void
    {
        $data = [
            'source_type' => $this->input('source_type', 'unknown') ?: 'unknown',
            'related_vendor_id' => $this->input('related_vendor_id', ''),
            'related_customer_id' => $this->input('related_customer_id', ''),
            'related_vehicle_id' => $this->input('related_vehicle_id', ''),
            'related_service_request_id' => $this->input('related_service_request_id', ''),
            'related_invoice_id' => $this->input('related_invoice_id', ''),
            'notes' => $this->input('notes', ''),
        ];

        $upload = $_FILES['document_file'] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $this->renderUploadForm(['document_file' => 'Attach a document file.'], $data);
            return;
        }

        $fileErrors = $this->validateUpload($upload);
        if ($fileErrors) {
            $this->renderUploadForm($fileErrors, $data);
            return;
        }

        $stored = $this->moveUploadedFile($upload);
        if (!$stored) {
            $this->renderUploadForm(['document_file' => 'Could not save the uploaded file.'], $data);
            return;
        }

        $hash = hash_file('sha256', $stored['absolute_path']) ?: null;

        $duplicate = null;
        if ($hash) {
            $duplicate = (new DocumentIntake())->findByFileHash($hash);
        }

        $intakeModel = new DocumentIntake();
        $intakeId = $intakeModel->create([
            'original_filename' => $stored['original_filename'],
            'stored_filename' => $stored['stored_filename'],
            'file_path' => $stored['relative_path'],
            'file_mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
            'file_hash' => $hash,
            'source_type' => $data['source_type'],
            'related_vendor_id' => $data['related_vendor_id'] ?: null,
            'related_customer_id' => $data['related_customer_id'] ?: null,
            'related_vehicle_id' => $data['related_vehicle_id'] ?: null,
            'related_service_request_id' => $data['related_service_request_id'] ?: null,
            'related_invoice_id' => $data['related_invoice_id'] ?: null,
            'uploaded_by_user_id' => Auth::userId(),
            'notes' => $data['notes'] ?: '',
        ]);

        if ($duplicate) {
            $intakeModel->setDuplicateOf($intakeId, (int) $duplicate['id']);
        }

        (new AuditLog())->record('document_intake_uploaded', 'document_intake', $intakeId, null, [
            'source_type' => $data['source_type'],
            'file_size' => $stored['file_size'],
            'duplicate_of' => $duplicate['document_number'] ?? null,
        ]);

        $this->runExtraction($intakeId, $stored['absolute_path'], $stored['mime_type']);

        $this->redirect('/document-intake/' . $intakeId . '/review');
    }

    public function review(string $id): void
    {
        $intakeId = (int) $id;
        $intake = (new DocumentIntake())->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $this->renderReview($intake, null);
    }

    public function approve(string $id): void
    {
        $intakeId = (int) $id;
        $intakeModel = new DocumentIntake();
        $intake = $intakeModel->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $this->persistReviewEdits($intakeId);

        $intake = $intakeModel->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        if (!in_array($intake['status'], ['needs_review', 'uploaded', 'processing'], true)) {
            $this->renderReview($intake, 'Document is not in a reviewable state.');
            return;
        }

        // Block posting on a known duplicate unless the operator has explicitly
        // overridden. The override is a separate confirm action so it shows up
        // in the audit trail.
        $duplicates = $intakeModel->findDuplicateCandidates($intakeId);
        $hasPosted = false;
        foreach ($duplicates as $d) {
            if ($d['status'] === 'posted') {
                $hasPosted = true;
                break;
            }
        }
        if ($hasPosted && (int) ($intake['duplicate_override'] ?? 0) !== 1) {
            $this->renderReview(
                $intake,
                'A matching file has already been posted. Use "Post anyway (confirmed duplicate)" if you intend to post a second time.'
            );
            return;
        }

        $intakeModel->updateStatus($intakeId, 'approved');
        (new AuditLog())->record('document_intake_approved', 'document_intake', $intakeId, null, [
            'document_type' => $intake['detected_document_type'],
        ]);

        try {
            $result = (new DocumentPostingService())->post($intakeId);
        } catch (\Throwable $e) {
            $intakeModel->updateStatus($intakeId, 'failed', 'Posting error: ' . $e->getMessage());
            $intake = $intakeModel->find($intakeId);
            $this->renderReview($intake ?? [], 'Posting failed: ' . $e->getMessage());
            return;
        }

        (new AuditLog())->record('document_intake_posted', 'document_intake', $intakeId, null, $result);

        $this->redirect('/document-intake/' . $intakeId . '/review');
    }

    public function saveDraft(string $id): void
    {
        $intakeId = (int) $id;
        $intakeModel = new DocumentIntake();
        $intake = $intakeModel->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        if (!in_array($intake['status'], ['needs_review', 'uploaded', 'processing', 'failed'], true)) {
            $this->renderReview($intake, 'Document is not editable in its current state.');
            return;
        }

        $this->persistReviewEdits($intakeId);
        (new AuditLog())->record('document_intake_draft_saved', 'document_intake', $intakeId, null, null);

        $this->redirect('/document-intake/' . $intakeId . '/review?saved=1');
    }

    public function confirmDuplicate(string $id): void
    {
        $intakeId = (int) $id;
        $intakeModel = new DocumentIntake();
        $intake = $intakeModel->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $intakeModel->setDuplicateOverride($intakeId, true);
        (new AuditLog())->record('document_intake_duplicate_override', 'document_intake', $intakeId, null, [
            'duplicate_of_intake_id' => $intake['duplicate_of_intake_id'] ?? null,
        ]);

        $this->redirect('/document-intake/' . $intakeId . '/review');
    }

    public function reject(string $id): void
    {
        $intakeId = (int) $id;
        $intakeModel = new DocumentIntake();
        $intake = $intakeModel->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $intakeModel->updateStatus($intakeId, 'rejected');
        (new AuditLog())->record('document_intake_rejected', 'document_intake', $intakeId, null, [
            'reason' => $this->input('reason', '') ?: null,
        ]);

        $this->redirect('/document-intake/' . $intakeId . '/review');
    }

    public function reprocess(string $id): void
    {
        $intakeId = (int) $id;
        $intake = (new DocumentIntake())->find($intakeId);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . '/' . $intake['file_path'];
        if (!is_file($absolutePath)) {
            (new DocumentIntake())->updateStatus($intakeId, 'failed', 'Stored file is missing on disk.');
            $this->redirect('/document-intake/' . $intakeId . '/review');
            return;
        }

        $this->runExtraction($intakeId, $absolutePath, (string) $intake['file_mime_type']);

        (new AuditLog())->record('document_intake_reprocessed', 'document_intake', $intakeId, null, null);

        $this->redirect('/document-intake/' . $intakeId . '/review');
    }

    public function downloadFile(string $id): void
    {
        $intake = (new DocumentIntake())->find((int) $id);
        if (!$intake) {
            $this->notFound();
            return;
        }

        $useStaged = !empty($_GET['staged']) && !empty($intake['staged_file_path']);
        $relative = $useStaged ? $intake['staged_file_path'] : $intake['file_path'];
        $mime = $useStaged
            ? ($intake['staged_mime_type'] ?: 'image/jpeg')
            : ($intake['file_mime_type'] ?: 'application/octet-stream');

        $absolutePath = dirname(__DIR__, 2) . '/' . $relative;
        if (!is_file($absolutePath)) {
            $this->notFound();
            return;
        }

        $disposition = isset($_GET['download']) ? 'attachment' : 'inline';
        $filename = $useStaged
            ? ('staged-' . $intake['document_number'] . '.jpg')
            : ($intake['original_filename'] ?: ('intake-' . $intake['document_number']));

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($absolutePath));
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($absolutePath);
    }

    private function persistReviewEdits(int $intakeId): void
    {
        $intakeModel = new DocumentIntake();
        $intakeModel->applyReviewedRelations($intakeId, [
            'related_customer_id' => $this->input('related_customer_id', ''),
            'related_vehicle_id' => $this->input('related_vehicle_id', ''),
            'related_vendor_id' => $this->input('related_vendor_id', ''),
            'related_service_request_id' => $this->input('related_service_request_id', ''),
            'related_invoice_id' => $this->input('related_invoice_id', ''),
            'detected_document_type' => $this->input('detected_document_type', ''),
            'notes' => $this->input('notes', '') ?: null,
        ]);

        $lineModel = new DocumentExtractionLineItem();
        $lineIds = $_POST['line_id'] ?? [];
        if (is_array($lineIds)) {
            foreach ($lineIds as $idx => $lineIdRaw) {
                $lineId = (int) $lineIdRaw;
                if ($lineId <= 0) {
                    continue;
                }
                $existing = $lineModel->find($lineId);
                if (!$existing || (int) $existing['document_intake_id'] !== $intakeId) {
                    continue;
                }
                $lineModel->updateReviewed($lineId, [
                    'description' => $_POST['line_description'][$idx] ?? $existing['description'],
                    'sku' => $_POST['line_sku'][$idx] ?? $existing['sku'],
                    'manufacturer_part_number' => $_POST['line_mpn'][$idx] ?? $existing['manufacturer_part_number'],
                    'vendor_part_number' => $_POST['line_vpn'][$idx] ?? $existing['vendor_part_number'],
                    'quantity' => $_POST['line_quantity'][$idx] ?? $existing['quantity'],
                    'unit_price' => $_POST['line_unit_price'][$idx] ?? $existing['unit_price'],
                    'reviewed_category' => $_POST['line_reviewed_category'][$idx] ?? $existing['reviewed_category'],
                ]);
            }
        }
    }

    /**
     * If another intake with the exact same file hash already has a clean
     * extraction, copy it instead of paying for a new OpenAI call. Returns
     * true when reuse happened.
     */
    private function tryReuseExtraction(int $intakeId): bool
    {
        $intake = (new DocumentIntake())->find($intakeId);
        if (!$intake || empty($intake['file_hash'])) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'SELECT de.id, de.normalized_json, de.raw_response_json, de.openai_model,
                    de.warnings_json, de.extraction_confidence,
                    de.input_tokens, de.output_tokens, de.total_tokens
             FROM document_extractions de
             INNER JOIN document_intakes di ON di.id = de.document_intake_id
             WHERE di.file_hash = :hash
               AND di.id <> :self
               AND (de.error_message IS NULL OR de.error_message = \'\')
               AND de.normalized_json IS NOT NULL
             ORDER BY de.id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'hash' => $intake['file_hash'],
            'self' => $intakeId,
        ]);
        $prior = $stmt->fetch();
        if (!$prior) {
            return false;
        }

        (new DocumentExtraction())->create($intakeId, [
            'openai_model' => $prior['openai_model'],
            'raw_response_json' => $prior['raw_response_json'],
            'normalized_json' => $prior['normalized_json'],
            'extraction_confidence' => $prior['extraction_confidence'],
            'warnings_json' => $prior['warnings_json'],
            'error_message' => null,
            // Mark as reused — no cost charged, and totalUsage() excludes it.
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'estimated_cost_cents' => 0,
            'reused_from_extraction_id' => (int) $prior['id'],
        ]);

        // Apply classification + line items from the reused payload.
        $normalized = json_decode((string) $prior['normalized_json'], true) ?: [];
        $lineModel = new DocumentExtractionLineItem();
        $lineModel->deleteForIntake($intakeId);
        foreach ($normalized['line_items'] ?? [] as $index => $line) {
            $line['line_number'] = $line['line_number'] ?? ($index + 1);
            $lineModel->create($intakeId, $line);
        }

        (new DocumentIntake())->applyClassification(
            $intakeId,
            $normalized['document_type'] ?? null,
            isset($normalized['document_type_confidence']) ? (float) $normalized['document_type_confidence'] : null
        );

        (new DocumentMatchingService())->buildAndStoreMatches($intakeId, $normalized);

        (new AuditLog())->record('document_intake_extraction_reused', 'document_intake', $intakeId, null, [
            'reused_from_extraction_id' => (int) $prior['id'],
        ]);

        return true;
    }

    private function runExtraction(int $intakeId, string $absolutePath, string $mimeType): void
    {
        $intakeModel = new DocumentIntake();
        $intakeModel->updateStatus($intakeId, 'processing');

        // Cost-saving short-circuit: if another intake with the exact same
        // file hash already has a successful AI extraction, copy that result
        // instead of paying for a re-extract. The operator still reviews and
        // approves separately — only the spend is saved.
        $reused = $this->tryReuseExtraction($intakeId);
        if ($reused) {
            $intakeModel->updateStatus($intakeId, 'needs_review');
            return;
        }

        // If a previous staged file exists from an earlier extraction pass,
        // delete it before producing a new one so reprocess doesn't leak
        // orphan files on disk.
        $intakeRow = $intakeModel->find($intakeId);
        if ($intakeRow && !empty($intakeRow['staged_file_path'])) {
            $oldStaged = dirname(__DIR__, 2) . '/' . $intakeRow['staged_file_path'];
            if (is_file($oldStaged)) {
                @unlink($oldStaged);
            }
        }

        // Stage the upload first: re-encode/resize/rotate so the AI sees a
        // clean JPEG instead of whatever (possibly weird) source we got.
        // PDFs pass through. If staging fails we still try the original.
        $stagedDir = dirname(__DIR__, 2) . '/storage/document-intake/staged/' . date('Y/m');
        $staging = (new ImageStagingService())->stage($absolutePath, $mimeType, $stagedDir);

        // Normalize the staged_path to a forward-slash relative path so it
        // works across moves/deploys exactly like file_path does. dirname()
        // on Windows returns backslashes — normalize before stripping.
        if (!empty($staging['staged_path'])) {
            $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));
            $stagedForward = str_replace('\\', '/', $staging['staged_path']);
            $relative = ltrim(str_replace($appRoot . '/', '', $stagedForward), '/');
            if (str_starts_with($relative, 'storage/')) {
                $staging['staged_path'] = $relative;
            }
        }

        $intakeModel->applyStaging($intakeId, $staging);

        if ($staging['error'] !== null) {
            $intakeModel->updateStatus($intakeId, 'failed', 'Image staging failed: ' . $staging['error']);
            (new DocumentExtraction())->create($intakeId, [
                'openai_model' => null,
                'raw_response_json' => null,
                'normalized_json' => json_encode((new OpenAiDocumentExtractionService())->emptyNormalized(), JSON_UNESCAPED_SLASHES),
                'extraction_confidence' => null,
                'warnings_json' => json_encode(array_merge(['Image staging failed; AI extraction skipped.'], $staging['warnings'])),
                'error_message' => $staging['error'],
            ]);
            return;
        }

        $extractionPath = $absolutePath;
        $extractionMime = $mimeType;
        if (!empty($staging['staged_path'])) {
            $extractionPath = dirname(__DIR__, 2) . '/' . $staging['staged_path'];
            $extractionMime = $staging['staged_mime'] ?? $mimeType;
        }

        $service = new OpenAiDocumentExtractionService();
        $result = $service->extract($extractionPath, $extractionMime);

        // Surface staging warnings alongside AI warnings so the operator sees both.
        if (!empty($staging['warnings'])) {
            $result['warnings'] = array_merge($staging['warnings'], $result['warnings'] ?? []);
        }

        $extractionModel = new DocumentExtraction();
        $extractionModel->create($intakeId, [
            'openai_model' => $result['model'],
            'raw_response_json' => $result['raw'] ?: null,
            'normalized_json' => json_encode($result['normalized'], JSON_UNESCAPED_SLASHES),
            'extraction_confidence' => $result['normalized']['document_type_confidence'] ?? null,
            'warnings_json' => $result['warnings'] ? json_encode($result['warnings']) : null,
            'error_message' => $result['error'],
            'input_tokens' => $result['usage']['input_tokens'] ?? 0,
            'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            'total_tokens' => $result['usage']['total_tokens'] ?? 0,
            'estimated_cost_cents' => $result['usage']['cost_hundredths_cent'] ?? 0,
        ]);

        // Clear and rebuild line items so reprocessing replaces stale data.
        $lineModel = new DocumentExtractionLineItem();
        $lineModel->deleteForIntake($intakeId);

        foreach ($result['normalized']['line_items'] ?? [] as $index => $line) {
            $line['line_number'] = $line['line_number'] ?? ($index + 1);
            $lineModel->create($intakeId, $line);
        }

        $intakeModel->applyClassification(
            $intakeId,
            $result['normalized']['document_type'] ?? null,
            $result['normalized']['document_type_confidence'] ?? null
        );

        (new DocumentMatchingService())->buildAndStoreMatches($intakeId, $result['normalized']);

        if ($result['error']) {
            $intakeModel->updateStatus($intakeId, 'failed', $result['error']);
        } else {
            $intakeModel->updateStatus($intakeId, 'needs_review');
        }
    }

    private function renderReview(array $intake, ?string $flashError): void
    {
        $extraction = (new DocumentExtraction())->forIntake((int) $intake['id']);
        $normalized = $extraction && !empty($extraction['normalized_json'])
            ? (json_decode((string) $extraction['normalized_json'], true) ?: [])
            : [];
        $warnings = $extraction && !empty($extraction['warnings_json'])
            ? (json_decode((string) $extraction['warnings_json'], true) ?: [])
            : [];
        $lines = (new DocumentExtractionLineItem())->forIntake((int) $intake['id']);
        $matches = (new DocumentMatch())->forIntake((int) $intake['id']);
        $postingLogs = (new DocumentPostingLog())->forIntake((int) $intake['id']);

        $matchedRecordSummaries = $this->summarizeMatches($matches);

        $duplicates = (new DocumentIntake())->findDuplicateCandidates((int) $intake['id']);

        $this->view('layouts/app', [
            'title' => 'Review ' . ($intake['document_number'] ?? 'Document'),
            'active' => 'document-intake',
            'content' => 'document-intake/review',
            'intake' => $intake,
            'extraction' => $extraction,
            'normalized' => $normalized,
            'warnings' => $warnings,
            'lines' => $lines,
            'matches' => $matches,
            'matchSummaries' => $matchedRecordSummaries,
            'postingLogs' => $postingLogs,
            'flashError' => $flashError,
            'savedFlash' => !empty($_GET['saved']),
            'duplicates' => $duplicates,
            'vendors' => (new Vendor())->all(),
            'customers' => (new Customer())->all(),
            'vehicles' => (new Vehicle())->all(),
            'serviceRequests' => (new ServiceRequest())->all(),
            'invoices' => (new Invoice())->all(),
            'documentTypes' => DocumentIntake::DOCUMENT_TYPES,
            'lineCategories' => DocumentExtractionLineItem::CATEGORIES,
        ]);
    }

    private function summarizeMatches(array $matches): array
    {
        $summaries = [];
        foreach ($matches as $match) {
            $key = $match['matched_table'] . ':' . $match['matched_record_id'];
            $summaries[$key] = $this->describeMatchedRecord(
                (string) $match['matched_table'],
                (int) $match['matched_record_id']
            );
        }
        return $summaries;
    }

    private function describeMatchedRecord(string $table, int $id): string
    {
        $db = Database::connection();
        switch ($table) {
            case 'vendors':
                $stmt = $db->prepare('SELECT name FROM vendors WHERE id = :id');
                $stmt->execute(['id' => $id]);
                return (string) ($stmt->fetchColumn() ?: ('Vendor #' . $id));
            case 'customers':
                $stmt = $db->prepare('SELECT first_name, last_name, phone FROM customers WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                if ($row) {
                    return trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . ' (' . ($row['phone'] ?? '') . ')';
                }
                return 'Customer #' . $id;
            case 'vehicles':
                $stmt = $db->prepare('SELECT year, make, model, vin, plate_number FROM vehicles WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                if ($row) {
                    $parts = array_filter([
                        $row['year'] ?? null,
                        $row['make'] ?? null,
                        $row['model'] ?? null,
                    ]);
                    $label = implode(' ', $parts);
                    if (!empty($row['vin'])) {
                        $label .= ' (VIN ' . $row['vin'] . ')';
                    } elseif (!empty($row['plate_number'])) {
                        $label .= ' (Plate ' . $row['plate_number'] . ')';
                    }
                    return $label ?: ('Vehicle #' . $id);
                }
                return 'Vehicle #' . $id;
            case 'invoices':
                $stmt = $db->prepare('SELECT invoice_number, total FROM invoices WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                if ($row) {
                    return $row['invoice_number'] . ' — $' . number_format((float) $row['total'], 2);
                }
                return 'Invoice #' . $id;
            case 'service_requests':
                $stmt = $db->prepare('SELECT service_request_number FROM service_requests WHERE id = :id');
                $stmt->execute(['id' => $id]);
                return (string) ($stmt->fetchColumn() ?: ('Service Request #' . $id));
            default:
                return $table . ' #' . $id;
        }
    }

    private function renderUploadForm(array $errors, array $values): void
    {
        $this->view('layouts/app', [
            'title' => 'Upload Document for AI Intake',
            'active' => 'document-intake',
            'content' => 'document-intake/upload',
            'errors' => $errors,
            'values' => $values,
            'vendors' => (new Vendor())->all(),
            'customers' => (new Customer())->all(),
            'vehicles' => (new Vehicle())->all(),
            'serviceRequests' => (new ServiceRequest())->all(),
            'invoices' => (new Invoice())->all(),
            'sourceTypes' => DocumentIntake::SOURCE_TYPES,
            'extractionEnabled' => (new OpenAiDocumentExtractionService())->isEnabled(),
        ]);
    }

    private function validateUpload(array $upload): array
    {
        $errors = [];
        if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors['document_file'] = 'Upload failed. Try again.';
            return $errors;
        }
        if ((int) ($upload['size'] ?? 0) <= 0 || (int) $upload['size'] > self::MAX_BYTES) {
            $errors['document_file'] = 'File must be 15 MB or less.';
            return $errors;
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        if (!is_uploaded_file($tmpName)) {
            $errors['document_file'] = 'Uploaded file is not accessible.';
            return $errors;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $errors['document_file'] = 'Use a PDF or image (JPG, PNG, WebP, GIF).';
        }
        return $errors;
    }

    private function moveUploadedFile(array $upload): ?array
    {
        $storageDir = dirname(__DIR__, 2) . '/storage/document-intake/' . date('Y/m');
        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            return null;
        }

        $originalName = basename((string) $upload['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
        if (!in_array($extension, $allowedExt, true)) {
            // Fall back to mime → extension to keep the on-disk name sane.
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $upload['tmp_name']) ?: '';
            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'application/pdf' => 'pdf',
                default => 'bin',
            };
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $storageDir . DIRECTORY_SEPARATOR . $stored;

        if (!move_uploaded_file((string) $upload['tmp_name'], $absolutePath)) {
            return null;
        }

        $relative = 'storage/document-intake/' . date('Y/m') . '/' . $stored;
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolutePath) ?: 'application/octet-stream';

        return [
            'absolute_path' => $absolutePath,
            'relative_path' => $relative,
            'stored_filename' => $stored,
            'original_filename' => $originalName,
            'mime_type' => $mime,
            'file_size' => filesize($absolutePath) ?: 0,
        ];
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->view('layouts/error', [
            'title' => 'Document not found',
            'message' => 'That document intake record could not be found.',
        ]);
    }
}
