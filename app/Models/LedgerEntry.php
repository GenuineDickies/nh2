<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class LedgerEntry extends Repository
{
    public function all(): array
    {
        $sql = 'SELECT le.*,
                    COALESCE(SUM(lel.debit), 0) AS debit_total,
                    COALESCE(SUM(lel.credit), 0) AS credit_total
                FROM ledger_entries le
                LEFT JOIN ledger_entry_lines lel ON lel.ledger_entry_id = le.id
                GROUP BY le.id
                ORDER BY le.entry_date DESC, le.id DESC';

        return $this->db->query($sql)->fetchAll();
    }

    public function findBySource(string $sourceType, int $sourceId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ledger_entries WHERE source_type = :source_type AND source_id = :source_id LIMIT 1');
        $stmt->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findWithLines(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ledger_entries WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $entry = $stmt->fetch();

        if (!$entry) {
            return null;
        }

        $lines = $this->db->prepare(
            'SELECT lel.*, aa.account_code, aa.account_name, aa.account_type
             FROM ledger_entry_lines lel
             JOIN accounting_accounts aa ON aa.id = lel.account_id
             WHERE lel.ledger_entry_id = :id
             ORDER BY lel.id ASC'
        );
        $lines->execute(['id' => $id]);
        $entry['lines'] = $lines->fetchAll();

        return $entry;
    }

    public function createPosted(string $sourceType, int $sourceId, string $memo, array $lines): int
    {
        if ($this->findBySource($sourceType, $sourceId)) {
            return (int) $this->findBySource($sourceType, $sourceId)['id'];
        }

        $debitTotal = 0.0;
        $creditTotal = 0.0;
        foreach ($lines as $line) {
            $debitTotal += round((float) ($line['debit'] ?? 0), 2);
            $creditTotal += round((float) ($line['credit'] ?? 0), 2);
        }

        if (round($debitTotal, 2) !== round($creditTotal, 2) || $debitTotal <= 0) {
            throw new \RuntimeException('Ledger entry debits and credits must balance.');
        }

        $now = $this->now();
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ledger_entries (
                    entry_number, source_type, source_id, entry_date, memo, posted, posted_at, created_at
                ) VALUES (
                    :entry_number, :source_type, :source_id, :entry_date, :memo, 1, :posted_at, :created_at
                )'
            );
            $stmt->execute([
                'entry_number' => NumberingService::next('JRN'),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'entry_date' => date('Y-m-d'),
                'memo' => $memo,
                'posted_at' => $now,
                'created_at' => $now,
            ]);
            $entryId = (int) $this->db->lastInsertId();

            $lineStmt = $this->db->prepare(
                'INSERT INTO ledger_entry_lines (ledger_entry_id, account_id, debit, credit, memo, created_at)
                 VALUES (:ledger_entry_id, :account_id, :debit, :credit, :memo, :created_at)'
            );
            foreach ($lines as $line) {
                $lineStmt->execute([
                    'ledger_entry_id' => $entryId,
                    'account_id' => $line['account_id'],
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'memo' => $line['memo'] ?? null,
                    'created_at' => $now,
                ]);
            }

            $this->db->commit();
            return $entryId;
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
