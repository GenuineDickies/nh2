<?php

namespace App\Services;

use App\Models\FileAttachment;

final class FileUploadService
{
    private const MAX_BYTES = 10485760;
    private const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const VENDOR_DOC_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

    public function storeUpload(
        array $upload,
        string $relatedType,
        int $relatedId,
        string $fileType,
        ?string $caption = null
    ): array {
        $errors = $this->validate($upload, $fileType);
        if ($errors) {
            return [
                'attachment_id' => null,
                'errors' => $errors,
            ];
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/uploads/' . date('Y/m');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $originalName = basename((string) $upload['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
        $absolutePath = $storageDir . '/' . $filename;

        if (!move_uploaded_file((string) $upload['tmp_name'], $absolutePath)) {
            return [
                'attachment_id' => null,
                'errors' => ['file' => 'Upload could not be saved'],
            ];
        }

        $relativePath = 'storage/uploads/' . date('Y/m') . '/' . $filename;
        $attachmentId = (new FileAttachment())->create([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'file_type' => $fileType,
            'file_path' => $relativePath,
            'original_filename' => $originalName,
            'mime_type' => $this->mimeType((string) $absolutePath),
            'file_size' => filesize($absolutePath) ?: 0,
            'caption' => $caption,
        ]);

        return [
            'attachment_id' => $attachmentId,
            'errors' => [],
        ];
    }

    private function validate(array $upload, string $fileType): array
    {
        $errors = [];
        if (!in_array($fileType, ['photo', 'signature', 'document', 'receipt_image'], true)) {
            $errors['file_type'] = 'Choose a valid file type';
        }
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors['file'] = 'Choose a file to upload';
            return $errors;
        }
        if ((int) ($upload['size'] ?? 0) <= 0 || (int) $upload['size'] > self::MAX_BYTES) {
            $errors['file'] = 'File must be 10 MB or less';
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $mimeType = is_file($tmpName) ? $this->mimeType($tmpName) : '';

        $allowed = in_array($fileType, ['document', 'receipt_image'], true)
            ? self::VENDOR_DOC_MIME_TYPES
            : self::IMAGE_MIME_TYPES;

        if (!in_array($mimeType, $allowed, true)) {
            $errors['file'] = in_array($fileType, ['document', 'receipt_image'], true)
                ? 'Use a PDF or image (JPG, PNG, WebP, GIF)'
                : 'Use a JPG, PNG, WebP, or GIF image';
        }

        return $errors;
    }

    private function mimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }
}
