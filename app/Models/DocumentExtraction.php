<?php

namespace App\Models;

use App\Core\Repository;

final class DocumentExtraction extends Repository
{
    public function forIntake(int $documentIntakeId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM document_extractions
             WHERE document_intake_id = :id
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $documentIntakeId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $documentIntakeId, array $data): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO document_extractions (
                document_intake_id, openai_model, raw_response_json, normalized_json,
                extraction_confidence, warnings_json, error_message,
                input_tokens, output_tokens, total_tokens, estimated_cost_cents,
                reused_from_extraction_id, created_at, updated_at
            ) VALUES (
                :document_intake_id, :openai_model, :raw, :norm,
                :conf, :warn, :err,
                :input_tokens, :output_tokens, :total_tokens, :cost,
                :reused_from, :created_at, :updated_at
            )'
        );
        $stmt->execute([
            'document_intake_id' => $documentIntakeId,
            'openai_model' => $data['openai_model'] ?? null,
            'raw' => $data['raw_response_json'] ?? null,
            'norm' => $data['normalized_json'] ?? null,
            'conf' => $data['extraction_confidence'] ?? null,
            'warn' => $data['warnings_json'] ?? null,
            'err' => $data['error_message'] ?? null,
            'input_tokens' => $data['input_tokens'] ?? null,
            'output_tokens' => $data['output_tokens'] ?? null,
            'total_tokens' => $data['total_tokens'] ?? null,
            'cost' => $data['estimated_cost_cents'] ?? null,
            'reused_from' => $data['reused_from_extraction_id'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function totalUsage(): array
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS extraction_count,
                    COALESCE(SUM(input_tokens), 0) AS input_tokens,
                    COALESCE(SUM(output_tokens), 0) AS output_tokens,
                    COALESCE(SUM(total_tokens), 0) AS total_tokens,
                    COALESCE(SUM(estimated_cost_cents), 0) AS cost_hundredths_cent
             FROM document_extractions
             WHERE reused_from_extraction_id IS NULL'
        )->fetch();
        return $row ?: [
            'extraction_count' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'cost_hundredths_cent' => 0,
        ];
    }

    public function updateNormalized(int $documentIntakeId, string $normalizedJson): void
    {
        $existing = $this->forIntake($documentIntakeId);
        if (!$existing) {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE document_extractions
             SET normalized_json = :norm, updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'norm' => $normalizedJson,
            'u' => $this->now(),
            'id' => (int) $existing['id'],
        ]);
    }
}
