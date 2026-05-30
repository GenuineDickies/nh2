<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\GeneratedDocument;
use App\Services\Pdf\PdfTemplateResolver;
use App\Services\Pdf\PdfValidationException;
use App\Services\PdfService;

final class DocumentController extends Controller
{
    private PdfTemplateResolver $resolver;

    public function __construct()
    {
        $this->resolver = new PdfTemplateResolver();
    }

    public function generateEstimate(string $id): void
    {
        $this->handleGenerate('estimate_pdf', (int) $id);
    }

    public function generateInvoice(string $id): void
    {
        $this->handleGenerate('invoice_pdf', (int) $id);
    }

    public function generateReceipt(string $id): void
    {
        $this->handleGenerate('receipt_pdf', (int) $id);
    }

    public function generateWorkOrder(string $id): void
    {
        $this->handleGenerate('work_order_pdf', (int) $id);
    }

    public function generateServiceCompletion(string $id): void
    {
        $this->handleGenerate('service_completion_pdf', (int) $id);
    }

    public function generateProofPacket(string $id): void
    {
        $this->handleGenerate('proof_packet_pdf', (int) $id);
    }

    public function regenerate(string $id): void
    {
        $documents = new GeneratedDocument();
        $document = $documents->findWithFile((int) $id);
        if (!$document) {
            $this->redirect('/');
        }

        $documentType = (string) $document['document_type'];
        $relatedId = (int) $document['related_id'];

        $viewModel = $this->resolver->resolve($documentType, $relatedId);
        if ($viewModel === null) {
            $this->redirect($this->resolver->indexRedirectFor($documentType));
        }

        try {
            // Keep the old row + its file_attachment + the PDF on disk
            // intact; mark the row superseded and mint a fresh version
            // that owns the regenerated PDF. Customers who already have
            // a URL for a prior version can still download it.
            $documents->markSuperseded((int) $document['id']);
            $newDocumentId = $documents->createNextVersion($document);

            $attachmentId = (new PdfService())->renderViewModel($newDocumentId, $viewModel);
            $documents->attachFile($newDocumentId, $attachmentId);
        } catch (PdfValidationException $exception) {
            $this->renderValidationError($exception);
            return;
        }

        (new AuditLog())->record(
            'document_regenerated',
            (string) $document['related_type'],
            $relatedId,
            [
                'document_id' => (int) $document['id'],
                'version' => (int) ($document['version'] ?? 1),
                'file_attachment_id' => $document['file_attachment_id'] ? (int) $document['file_attachment_id'] : null,
            ],
            [
                'document_id' => $newDocumentId,
                'document_type' => $documentType,
                'version' => ((int) ($document['version'] ?? 1)) + 1,
                'file_attachment_id' => $attachmentId,
            ]
        );

        $this->redirect($this->resolver->successRedirectFor($documentType, $relatedId));
    }

    public function download(string $id): void
    {
        $document = (new GeneratedDocument())->findWithFile((int) $id);
        if (!$document || empty($document['file_path'])) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Document not available',
                'message' => 'That generated document has no rendered file yet.',
            ]);
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . '/' . $document['file_path'];
        if (!is_file($absolutePath)) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Document file missing',
                'message' => 'The PDF file was recorded but is no longer on disk.',
            ]);
            return;
        }

        $disposition = isset($_GET['download']) ? 'attachment' : 'inline';
        $filename = $document['original_filename'] ?: ('document-' . (int) $id . '.pdf');

        header('Content-Type: ' . ($document['mime_type'] ?: 'application/pdf'));
        header('Content-Length: ' . filesize($absolutePath));
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $filename) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($absolutePath);
    }

    private function handleGenerate(string $documentType, int $relatedId): void
    {
        $viewModel = $this->resolver->resolve($documentType, $relatedId);
        if ($viewModel === null) {
            $this->redirect($this->resolver->indexRedirectFor($documentType));
        }

        $relatedType = $this->resolver->relatedTypeFor($documentType);
        $successPath = $this->resolver->successRedirectFor($documentType, $relatedId);

        $documents = new GeneratedDocument();
        $documentId = $documents->createPlaceholder($documentType, $relatedType, $relatedId, $viewModel->title());
        $existing = $documents->findWithFile($documentId);

        if ($existing && !empty($existing['file_attachment_id'])) {
            $this->redirect($successPath);
        }

        try {
            $attachmentId = (new PdfService())->renderViewModel($documentId, $viewModel);
        } catch (PdfValidationException $exception) {
            $this->renderValidationError($exception);
            return;
        }
        $documents->attachFile($documentId, $attachmentId);

        (new AuditLog())->record('document_generated', $relatedType, $relatedId, null, [
            'document_id' => $documentId,
            'document_type' => $documentType,
            'file_attachment_id' => $attachmentId,
        ]);

        $this->redirect($successPath);
    }

    private function renderValidationError(PdfValidationException $exception): void
    {
        http_response_code(422);
        $bullets = $exception->errors
            ? ' Missing: ' . implode('; ', $exception->errors) . '.'
            : '';
        $this->view('layouts/error', [
            'title' => 'PDF cannot be generated',
            'message' => 'The PDF was not generated because the source record is missing required data.'
                . $bullets
                . ' Fix the source record and try again.',
        ]);
    }
}
