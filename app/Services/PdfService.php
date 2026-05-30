<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FileAttachment;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\ViewModels\PdfViewModel;

/**
 * Thin orchestrator on top of the PDF pipeline.
 *
 * Responsibilities:
 *   1. Ask the view model to paint itself onto a fresh PdfDocument.
 *   2. Write the resulting bytes to /storage/generated-pdfs/YYYY/MM/.
 *   3. Register a file_attachment row that owns the file.
 *
 * Validation lives in the view model (PdfDataValidator), not here. If
 * the view model throws PdfValidationException the caller (controller)
 * handles redirect + flash messaging.
 */
final class PdfService
{
    public function renderViewModel(int $documentId, PdfViewModel $viewModel): int
    {
        $document = new PdfDocument();
        $viewModel->render($document);
        $bytes = $document->output();

        $storageDir = dirname(__DIR__, 2) . '/storage/generated-pdfs/' . date('Y/m');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $filename = 'doc-' . $documentId . '-' . bin2hex(random_bytes(8)) . '.pdf';
        $absolutePath = $storageDir . '/' . $filename;
        $relativePath = 'storage/generated-pdfs/' . date('Y/m') . '/' . $filename;

        file_put_contents($absolutePath, $bytes);

        return (new FileAttachment())->create([
            'related_type' => 'generated_document',
            'related_id' => $documentId,
            'file_type' => 'pdf',
            'file_path' => $relativePath,
            'original_filename' => $filename,
            'mime_type' => 'application/pdf',
            'file_size' => filesize($absolutePath) ?: 0,
            'caption' => $viewModel->fileCaption(),
        ]);
    }
}
