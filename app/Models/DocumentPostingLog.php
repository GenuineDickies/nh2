<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Repository;

final class DocumentPostingLog extends Repository
{
    public function forIntake(int $documentIntakeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM document_posting_logs
             WHERE document_intake_id = :id
             ORDER BY id ASC'
        );
        $stmt->execute(['id' => $documentIntakeId]);

        return $stmt->fetchAll();
    }

    public function record(
        int $documentIntakeId,
        string $postedRecordType,
        ?int $postedRecordId,
        string $actionTaken,
        ?array $before = null,
        ?array $after = null
    ): int {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO document_posting_logs (
                document_intake_id, posted_record_type, posted_record_id, action_taken,
                before_json, after_json, posted_by_user_id, posted_at, created_at
            ) VALUES (
                :document_intake_id, :posted_record_type, :posted_record_id, :action_taken,
                :before_json, :after_json, :posted_by_user_id, :posted_at, :created_at
            )'
        );
        $stmt->execute([
            'document_intake_id' => $documentIntakeId,
            'posted_record_type' => $postedRecordType,
            'posted_record_id' => $postedRecordId,
            'action_taken' => $actionTaken,
            'before_json' => $before !== null ? json_encode($before, JSON_UNESCAPED_SLASHES) : null,
            'after_json' => $after !== null ? json_encode($after, JSON_UNESCAPED_SLASHES) : null,
            'posted_by_user_id' => Auth::userId(),
            'posted_at' => $now,
            'created_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
