<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class GeneratedDocument extends Repository
{
    public const TYPES = [
        'estimate_pdf',
        'invoice_pdf',
        'receipt_pdf',
        'work_order_pdf',
        'service_completion_pdf',
        'proof_packet_pdf',
    ];

    public function createPlaceholder(string $documentType, string $relatedType, int $relatedId, string $title): int
    {
        if (!in_array($documentType, self::TYPES, true)) {
            throw new \InvalidArgumentException('Unknown document type: ' . $documentType);
        }

        $existing = $this->findExisting($documentType, $relatedType, $relatedId);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->insertRow($documentType, $relatedType, $relatedId, $title, 1);
    }

    /**
     * Insert a new version row that supersedes $existingDocument.
     * Caller is responsible for calling markSuperseded() on the existing row
     * and attachFile() on the new row once the PDF is rendered.
     */
    public function createNextVersion(array $existingDocument): int
    {
        $nextVersion = ((int) ($existingDocument['version'] ?? 1)) + 1;
        return $this->insertRow(
            (string) $existingDocument['document_type'],
            (string) $existingDocument['related_type'],
            (int) $existingDocument['related_id'],
            (string) $existingDocument['title'],
            $nextVersion
        );
    }

    public function markSuperseded(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE generated_documents
             SET superseded_at = :superseded_at, updated_at = :updated_at
             WHERE id = :id AND superseded_at IS NULL'
        );
        $now = $this->now();
        $stmt->execute([
            'superseded_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);
    }

    private function insertRow(string $documentType, string $relatedType, int $relatedId, string $title, int $version): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO generated_documents (
                document_number, document_type, related_type, related_id, title,
                status, version, generated_at, created_at, updated_at
            ) VALUES (
                :document_number, :document_type, :related_type, :related_id, :title,
                :status, :version, :generated_at, :created_at, :updated_at
            )'
        );
        $stmt->execute([
            'document_number' => NumberingService::next('PDF'),
            'document_type' => $documentType,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'title' => $title,
            'status' => 'placeholder',
            'version' => $version,
            'generated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findWithFile(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT gd.*, fa.file_path, fa.original_filename, fa.mime_type, fa.file_size
             FROM generated_documents gd
             LEFT JOIN file_attachments fa ON fa.id = gd.file_attachment_id
             WHERE gd.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function attachFile(int $id, int $fileAttachmentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE generated_documents
             SET file_attachment_id = :file_attachment_id, status = :status,
                 generated_at = :generated_at, updated_at = :updated_at
             WHERE id = :id'
        );
        $now = $this->now();
        $stmt->execute([
            'file_attachment_id' => $fileAttachmentId,
            'status' => 'generated',
            'generated_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);
    }

    public function forRelated(string $relatedType, int $relatedId): array
    {
        $stmt = $this->db->prepare(
            'SELECT gd.*, fa.file_path, fa.original_filename
             FROM generated_documents gd
             LEFT JOIN file_attachments fa ON fa.id = gd.file_attachment_id
             WHERE gd.related_type = :related_type AND gd.related_id = :related_id
             ORDER BY gd.created_at DESC, gd.id DESC'
        );
        $stmt->execute([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);

        return $stmt->fetchAll();
    }

    public function forMany(array $relatedPairs): array
    {
        $documents = [];
        foreach ($relatedPairs as $pair) {
            if (!empty($pair['related_id'])) {
                $documents = array_merge($documents, $this->forRelated($pair['related_type'], (int) $pair['related_id']));
            }
        }

        return $documents;
    }

    private function findExisting(string $documentType, string $relatedType, int $relatedId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM generated_documents
             WHERE document_type = :document_type
               AND related_type = :related_type
               AND related_id = :related_id
               AND superseded_at IS NULL
             ORDER BY version DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'document_type' => $documentType,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
