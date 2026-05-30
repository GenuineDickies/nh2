<?php

namespace App\Services;

use App\Core\Database;
use PDO;

final class NumberingService
{
    public static function next(string $type): string
    {
        $db = Database::connection();
        $date = date('Ymd');
        $like = $type . '-' . $date . '-%';
        $tables = [
            'INT' => ['intakes', 'intake_number'],
            'SER' => ['service_requests', 'service_request_number'],
            'EST' => ['estimates', 'estimate_number'],
            'EAP' => ['customer_approvals', 'approval_number'],
            'WOR' => ['work_orders', 'work_order_number'],
            'SCR' => ['service_completion_reports', 'report_number'],
            'INV' => ['invoices', 'invoice_number'],
            'PAY' => ['payments', 'payment_number'],
            'RCT' => ['receipts', 'receipt_number'],
            'JRN' => ['ledger_entries', 'entry_number'],
            'PDF' => ['generated_documents', 'document_number'],
            'VDC' => ['vendor_documents', 'document_number'],
            'DOC' => ['document_intakes', 'document_number'],
        ];

        if (!isset($tables[$type])) {
            throw new \InvalidArgumentException('Unknown number type: ' . $type);
        }

        [$table, $column] = $tables[$type];
        $stmt = $db->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE :prefix ORDER BY {$column} DESC LIMIT 1");
        $stmt->execute(['prefix' => $like]);
        $last = $stmt->fetchColumn();
        $sequence = 1;

        if (is_string($last) && preg_match('/-(\d{3})-V\d+$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%03d-V1', $type, $date, $sequence);
    }
}
