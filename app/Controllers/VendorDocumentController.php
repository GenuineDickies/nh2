<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorDocumentLineItem;
use App\Services\AccountingService;
use App\Services\FileUploadService;

final class VendorDocumentController extends Controller
{
    public function index(): void
    {
        $this->view('layouts/app', [
            'title' => 'Vendor Documents',
            'active' => 'vendor-documents',
            'content' => 'vendor-documents/index',
            'documents' => (new VendorDocument())->all(),
        ]);
    }

    public function upload(): void
    {
        $this->renderUploadForm([], []);
    }

    public function store(): void
    {
        $data = $this->inputData();
        $errors = (new VendorDocument())->validate($data);

        if (!isset($_FILES['receipt_file']) || ($_FILES['receipt_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['receipt_file'] = 'Attach the vendor document file';
        }

        if ($errors) {
            $this->renderUploadForm($errors, $data);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $documentId = (new VendorDocument())->create($data);

            $upload = (new FileUploadService())->storeUpload(
                $_FILES['receipt_file'],
                'vendor_document',
                $documentId,
                'document',
                'Vendor document file'
            );

            if (!empty($upload['errors'])) {
                $db->rollBack();
                $this->renderUploadForm(['receipt_file' => current($upload['errors'])], $data);
                return;
            }

            (new VendorDocument())->attachFile($documentId, (int) $upload['attachment_id']);

            (new AuditLog())->record('vendor_document_uploaded', 'vendor_document', $documentId, null, [
                'vendor_id' => $data['vendor_id'] ?: null,
                'document_type' => $data['document_type'],
                'total' => (float) $data['total'],
            ]);

            $db->commit();
            $this->redirect('/vendor-documents/' . $documentId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function show(string $id): void
    {
        $documentId = (int) $id;
        $document = (new VendorDocument())->findWithDetails($documentId);

        if (!$document) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Vendor document not found',
                'message' => 'That vendor document could not be found.',
            ]);
            return;
        }

        $this->renderShow($document, [], []);
    }

    public function addLine(string $id): void
    {
        $documentId = (int) $id;
        $documentModel = new VendorDocument();
        $document = $documentModel->findWithDetails($documentId);

        if (!$document) {
            $this->redirect('/vendor-documents');
        }

        if (!$this->canEditLines($document)) {
            $this->redirect('/vendor-documents/' . $documentId);
        }

        $lineModel = new VendorDocumentLineItem();
        $data = $this->lineInput();
        $errors = $lineModel->validate($data);

        if ($errors) {
            $this->renderShow($document, $errors, $data);
            return;
        }

        $lineModel->create($documentId, $data);
        $documentModel->recalculate($documentId);

        $this->redirect('/vendor-documents/' . $documentId);
    }

    public function updateLine(string $documentId, string $lineId): void
    {
        $documentIdInt = (int) $documentId;
        $lineIdInt = (int) $lineId;
        $documentModel = new VendorDocument();
        $document = $documentModel->findWithDetails($documentIdInt);
        $lineModel = new VendorDocumentLineItem();
        $line = $lineModel->find($lineIdInt);

        if (!$document || !$line || (int) $line['vendor_document_id'] !== $documentIdInt) {
            $this->redirect('/vendor-documents/' . $documentIdInt);
        }

        if (!$this->canEditLines($document)) {
            $this->redirect('/vendor-documents/' . $documentIdInt);
        }

        $data = $this->lineInput();
        $errors = $lineModel->validate($data);

        if ($errors) {
            $this->renderShow($document, $errors, $data + ['_editing_line_id' => $lineIdInt]);
            return;
        }

        $lineModel->update($lineIdInt, $data);
        $documentModel->recalculate($documentIdInt);

        $this->redirect('/vendor-documents/' . $documentIdInt);
    }

    public function deleteLine(string $documentId, string $lineId): void
    {
        $documentIdInt = (int) $documentId;
        $lineIdInt = (int) $lineId;
        $document = (new VendorDocument())->findWithDetails($documentIdInt);
        $line = (new VendorDocumentLineItem())->find($lineIdInt);

        if (!$document || !$line || (int) $line['vendor_document_id'] !== $documentIdInt) {
            $this->redirect('/vendor-documents/' . $documentIdInt);
        }

        if (!$this->canEditLines($document)) {
            $this->redirect('/vendor-documents/' . $documentIdInt);
        }

        (new VendorDocumentLineItem())->delete($lineIdInt);
        (new VendorDocument())->recalculate($documentIdInt);

        $this->redirect('/vendor-documents/' . $documentIdInt);
    }

    public function markReview(string $id): void
    {
        $documentId = (int) $id;
        $documentModel = new VendorDocument();
        $document = $documentModel->findWithDetails($documentId);

        if (!$document) {
            $this->redirect('/vendor-documents');
        }

        if ($document['status'] !== 'uploaded') {
            $this->redirect('/vendor-documents/' . $documentId);
        }

        $change = $documentModel->updateStatus($documentId, 'needs_review');
        if ($change) {
            (new AuditLog())->record('vendor_document_status_changed', 'vendor_document', $documentId, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
            ]);
        }

        $this->redirect('/vendor-documents/' . $documentId);
    }

    public function approve(string $id): void
    {
        $documentId = (int) $id;
        $documentModel = new VendorDocument();
        $document = $documentModel->findWithDetails($documentId);

        if (!$document) {
            $this->redirect('/vendor-documents');
        }

        $errors = $this->approvalErrors($document);
        if ($errors) {
            $this->renderShow($document, ['__approval' => implode(' ', $errors)], []);
            return;
        }

        $change = $documentModel->updateStatus($documentId, 'approved');
        if ($change) {
            (new AuditLog())->record('vendor_document_approved', 'vendor_document', $documentId, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
            ]);
        }

        $this->redirect('/vendor-documents/' . $documentId);
    }

    public function post(string $id): void
    {
        $documentId = (int) $id;
        $documentModel = new VendorDocument();
        $document = $documentModel->findWithDetails($documentId);

        if (!$document) {
            $this->redirect('/vendor-documents');
        }

        if ($document['status'] !== 'approved') {
            $this->renderShow($document, ['__post' => 'Approve the document before posting.'], []);
            return;
        }

        try {
            $ledgerId = (new AccountingService())->postVendorDocument($documentId);
        } catch (\Throwable $e) {
            $this->renderShow($document, ['__post' => $e->getMessage()], []);
            return;
        }

        if (!$ledgerId) {
            $this->renderShow($document, ['__post' => 'Posting did not return a ledger entry.'], []);
            return;
        }

        $documentModel->updateStatus($documentId, 'posted');
        // Distinct placeholders so MySQL's native prepares accept the statement.
        $now = date('Y-m-d H:i:s');
        \App\Core\Database::connection()
            ->prepare('UPDATE vendor_documents SET posted_at = :posted_at, updated_at = :updated_at WHERE id = :id')
            ->execute(['posted_at' => $now, 'updated_at' => $now, 'id' => $documentId]);

        (new AuditLog())->record('vendor_document_posted', 'vendor_document', $documentId, null, [
            'ledger_entry_id' => $ledgerId,
        ]);

        $this->redirect('/vendor-documents/' . $documentId);
    }

    private function approvalErrors(array $document): array
    {
        $errors = [];

        if ($document['status'] === 'approved' || $document['status'] === 'posted') {
            $errors[] = 'Document is already approved.';
            return $errors;
        }

        if ($document['status'] !== 'needs_review') {
            $errors[] = 'Mark the document Needs Review before approving.';
        }

        $lines = (new VendorDocumentLineItem())->forDocument((int) $document['id']);
        if (!$lines) {
            $errors[] = 'Add at least one line item before approving.';
            return $errors;
        }

        $unreviewed = 0;
        $lineSum = 0.0;
        foreach ($lines as $line) {
            if ((int) $line['reviewed_flag'] !== 1) {
                $unreviewed++;
            }
            $lineSum += (float) $line['line_total'];
        }
        if ($unreviewed > 0) {
            $errors[] = "Review all {$unreviewed} unreviewed line(s) before approving.";
        }

        $expected = round($lineSum + (float) $document['tax_total'], 2);
        if (abs($expected - (float) $document['total']) > 0.01) {
            $errors[] = sprintf(
                'Line sum $%s + tax $%s does not match document total $%s.',
                number_format($lineSum, 2),
                number_format((float) $document['tax_total'], 2),
                number_format((float) $document['total'], 2)
            );
        }

        return $errors;
    }

    private function renderUploadForm(array $errors, array $data): void
    {
        $this->view('layouts/app', [
            'title' => 'Upload Vendor Document',
            'active' => 'vendor-documents',
            'content' => 'vendor-documents/upload',
            'vendors' => (new Vendor())->all(),
            'errors' => $errors,
            'values' => $data,
        ]);
    }

    private function renderShow(array $document, array $lineErrors, array $lineValues): void
    {
        $lines = (new VendorDocumentLineItem())->forDocument((int) $document['id']);
        $reviewedCount = 0;
        foreach ($lines as $line) {
            if ((int) $line['reviewed_flag'] === 1) {
                $reviewedCount++;
            }
        }

        $this->view('layouts/app', [
            'title' => $document['document_number'] . ' - ' . ($document['vendor_name'] ?: 'No vendor'),
            'active' => 'vendor-documents',
            'content' => 'vendor-documents/show',
            'document' => $document,
            'lines' => $lines,
            'reviewedCount' => $reviewedCount,
            'serviceRequests' => (new ServiceRequest())->all(),
            'invoices' => (new Invoice())->all(),
            'lineErrors' => $lineErrors,
            'lineValues' => $lineValues,
            'categories' => VendorDocumentLineItem::CATEGORIES,
            'canEditLines' => $this->canEditLines($document),
        ]);
    }

    private function canEditLines(array $document): bool
    {
        return in_array($document['status'], ['uploaded', 'needs_review'], true);
    }

    private function inputData(): array
    {
        return [
            'vendor_id' => $this->input('vendor_id', ''),
            'document_type' => (string) $this->input('document_type', 'receipt'),
            'external_document_number' => (string) $this->input('external_document_number', ''),
            'document_date' => (string) $this->input('document_date', ''),
            'total' => (string) $this->input('total', '0'),
            'tax_total' => (string) $this->input('tax_total', '0'),
            'payment_method' => (string) $this->input('payment_method', ''),
            'notes' => (string) $this->input('notes', ''),
        ];
    }

    private function lineInput(): array
    {
        return [
            'item_name' => (string) $this->input('item_name', ''),
            'part_number' => (string) $this->input('part_number', ''),
            'category' => (string) $this->input('category', 'other'),
            'quantity' => (string) $this->input('quantity', '1'),
            'unit_cost' => (string) $this->input('unit_cost', '0'),
            'service_request_id' => $this->input('service_request_id', ''),
            'invoice_id' => $this->input('invoice_id', ''),
            'reviewed_flag' => $this->input('reviewed_flag', '') ? 1 : 0,
        ];
    }
}
