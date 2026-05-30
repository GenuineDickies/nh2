<?php

namespace App\Models;

use App\Core\Repository;

final class FileAttachment extends Repository
{
    public const TYPES = ['photo', 'pdf', 'signature', 'receipt_image', 'document', 'other'];

    public function create(array $data): int
    {
        if (!in_array($data['file_type'], self::TYPES, true)) {
            throw new \InvalidArgumentException('Unknown file type: ' . $data['file_type']);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO file_attachments (
                related_type, related_id, file_type, file_path, original_filename,
                mime_type, file_size, caption, latitude, longitude, uploaded_by, created_at
            ) VALUES (
                :related_type, :related_id, :file_type, :file_path, :original_filename,
                :mime_type, :file_size, :caption, :latitude, :longitude, :uploaded_by, :created_at
            )'
        );
        $stmt->execute([
            'related_type' => $data['related_type'],
            'related_id' => $data['related_id'],
            'file_type' => $data['file_type'],
            'file_path' => $data['file_path'],
            'original_filename' => $data['original_filename'],
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
            'caption' => $data['caption'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'created_at' => $this->now(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function forRelated(string $relatedType, int $relatedId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM file_attachments
             WHERE related_type = :related_type AND related_id = :related_id
             ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute([
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);

        return $stmt->fetchAll();
    }

    public function forMany(array $relatedPairs): array
    {
        $attachments = [];
        foreach ($relatedPairs as $pair) {
            if (!empty($pair['related_id'])) {
                $attachments = array_merge($attachments, $this->forRelated($pair['related_type'], (int) $pair['related_id']));
            }
        }

        return $attachments;
    }
}
