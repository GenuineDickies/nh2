<?php

namespace App\Models;

use App\Core\Repository;

final class DocumentMatch extends Repository
{
    public const TABLES = [
        'vendors',
        'customers',
        'vehicles',
        'service_requests',
        'invoices',
        'vendor_documents',
        'catalog_items',
    ];

    public function forIntake(int $documentIntakeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM document_matches
             WHERE document_intake_id = :id
             ORDER BY match_type ASC, match_confidence DESC, id ASC'
        );
        $stmt->execute(['id' => $documentIntakeId]);

        return $stmt->fetchAll();
    }

    public function deleteForIntake(int $documentIntakeId): void
    {
        $stmt = $this->db->prepare('DELETE FROM document_matches WHERE document_intake_id = :id');
        $stmt->execute(['id' => $documentIntakeId]);
    }

    public function create(int $documentIntakeId, array $data): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO document_matches (
                document_intake_id, match_type, matched_table, matched_record_id,
                match_confidence, match_reason, accepted_by_user, created_at, updated_at
            ) VALUES (
                :document_intake_id, :match_type, :matched_table, :matched_record_id,
                :match_confidence, :match_reason, :accepted_by_user, :created_at, :updated_at
            )'
        );
        $stmt->execute([
            'document_intake_id' => $documentIntakeId,
            'match_type' => $data['match_type'],
            'matched_table' => $data['matched_table'],
            'matched_record_id' => (int) $data['matched_record_id'],
            'match_confidence' => isset($data['match_confidence']) ? (float) $data['match_confidence'] : null,
            'match_reason' => $data['match_reason'] ?? null,
            'accepted_by_user' => !empty($data['accepted_by_user']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
