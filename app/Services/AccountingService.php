<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\VendorDocument;
use App\Models\VendorDocumentLineItem;

final class AccountingService
{
    private const CATEGORY_ACCOUNTS = [
        'resold_part' => '5000',       // Parts COGS
        'inventory_part' => '1200',    // Parts Inventory
        'consumable' => '6030',        // Consumables
        'tool_equipment' => '6020',    // Tools and Equipment
        'ppe' => '6040',               // PPE and Safety
        'fuel' => '6010',              // Fuel Expense
        'meal_personal' => '6070',     // Meals/Personal Non-Business Review
        'office' => '6050',            // Office/Admin
        'other' => '6050',             // Office/Admin (safe fallback)
    ];

    private const PAYMENT_CREDIT_ACCOUNTS = [
        'cash' => '1000',     // Cash
        'check' => '1010',    // Checking
        'card' => '2010',     // Credit Card Payable
        'ach' => '1010',      // Checking
        'unpaid' => '2000',   // Accounts Payable
        'other' => '2000',    // Accounts Payable
    ];

    public function postInvoice(int $invoiceId): ?int
    {
        $invoice = (new Invoice())->findWithDetails($invoiceId);
        if (!$invoice || !in_array($invoice['status'], ['sent', 'partially_paid', 'paid'], true)) {
            return null;
        }

        $ledger = new LedgerEntry();
        $existing = $ledger->findBySource('invoice', $invoiceId);
        if ($existing) {
            return (int) $existing['id'];
        }

        $accounts = new Account();
        $lines = [[
            'account_id' => $accounts->idForCode('1100'),
            'debit' => (float) $invoice['total'],
            'credit' => 0,
            'memo' => 'Accounts receivable for ' . $invoice['invoice_number'],
        ]];

        foreach ($this->invoiceRevenueByAccount($invoiceId) as $accountCode => $amount) {
            if ($amount > 0) {
                $lines[] = [
                    'account_id' => $accounts->idForCode($accountCode),
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => 'Revenue for ' . $invoice['invoice_number'],
                ];
            }
        }

        if ((float) $invoice['tax_total'] > 0) {
            $lines[] = [
                'account_id' => $accounts->idForCode('2020'),
                'debit' => 0,
                'credit' => (float) $invoice['tax_total'],
                'memo' => 'Sales tax for ' . $invoice['invoice_number'],
            ];
        }

        return $ledger->createPosted('invoice', $invoiceId, 'Issued invoice ' . $invoice['invoice_number'], $lines);
    }

    public function postPayment(int $paymentId): ?int
    {
        $payment = (new Payment())->findWithDetails($paymentId);
        if (!$payment || $payment['payment_status'] !== 'completed') {
            return null;
        }

        $ledger = new LedgerEntry();
        $existing = $ledger->findBySource('payment', $paymentId);
        if ($existing) {
            return (int) $existing['id'];
        }

        $accounts = new Account();
        $cashAccount = $this->cashAccountForMethod($payment['payment_method']);

        return $ledger->createPosted('payment', $paymentId, 'Payment ' . $payment['payment_number'], [[
            'account_id' => $accounts->idForCode($cashAccount),
            'debit' => (float) $payment['amount'],
            'credit' => 0,
            'memo' => 'Payment received for ' . $payment['invoice_number'],
        ], [
            'account_id' => $accounts->idForCode('1100'),
            'debit' => 0,
            'credit' => (float) $payment['amount'],
            'memo' => 'Reduce accounts receivable',
        ]]);
    }

    public function postVendorDocument(int $vendorDocumentId): ?int
    {
        $document = (new VendorDocument())->findWithDetails($vendorDocumentId);
        if (!$document || $document['status'] !== 'approved') {
            return null;
        }

        $ledger = new LedgerEntry();
        $existing = $ledger->findBySource('vendor_document', $vendorDocumentId);
        if ($existing) {
            return (int) $existing['id'];
        }

        $lines = (new VendorDocumentLineItem())->forDocument($vendorDocumentId);
        if (!$lines) {
            throw new \RuntimeException('Vendor document has no line items to post.');
        }

        $lineSubtotal = 0.0;
        $categoryTotals = [];
        foreach ($lines as $line) {
            $amount = round((float) $line['line_total'], 2);
            $lineSubtotal += $amount;
            $cat = $line['category'];
            $categoryTotals[$cat] = round(($categoryTotals[$cat] ?? 0) + $amount, 2);
        }
        $lineSubtotal = round($lineSubtotal, 2);

        $taxTotal = round((float) $document['tax_total'], 2);
        $headerTotal = round((float) $document['total'], 2);
        $expected = round($lineSubtotal + $taxTotal, 2);

        if (abs($expected - $headerTotal) > 0.01) {
            throw new \RuntimeException(sprintf(
                'Vendor document total $%s does not match line sum $%s + tax $%s.',
                number_format($headerTotal, 2),
                number_format($lineSubtotal, 2),
                number_format($taxTotal, 2)
            ));
        }

        $accounts = new Account();
        $ledgerLines = [];

        foreach ($categoryTotals as $category => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $code = self::CATEGORY_ACCOUNTS[$category] ?? self::CATEGORY_ACCOUNTS['other'];
            $ledgerLines[] = [
                'account_id' => $accounts->idForCode($code),
                'debit' => $amount,
                'credit' => 0,
                'memo' => ucwords(str_replace('_', ' ', $category)) . ' for ' . $document['document_number'],
            ];
        }

        if ($taxTotal > 0) {
            $ledgerLines[] = [
                'account_id' => $accounts->idForCode(self::CATEGORY_ACCOUNTS['office']),
                'debit' => $taxTotal,
                'credit' => 0,
                'memo' => 'Sales tax paid for ' . $document['document_number'],
            ];
        }

        $paymentMethod = $document['payment_method'] ?: 'other';
        $creditCode = self::PAYMENT_CREDIT_ACCOUNTS[$paymentMethod] ?? self::PAYMENT_CREDIT_ACCOUNTS['other'];
        $ledgerLines[] = [
            'account_id' => $accounts->idForCode($creditCode),
            'debit' => 0,
            'credit' => $headerTotal,
            'memo' => 'Paid via ' . str_replace('_', ' ', $paymentMethod) . ' for ' . $document['document_number'],
        ];

        return $ledger->createPosted(
            'vendor_document',
            $vendorDocumentId,
            'Vendor document ' . $document['document_number'],
            $ledgerLines
        );
    }

    private function invoiceRevenueByAccount(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT line_type, line_subtotal FROM invoice_line_items WHERE invoice_id = :invoice_id'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);
        $totals = [
            '4000' => 0.0,
            '4010' => 0.0,
            '4020' => 0.0,
        ];

        foreach ($stmt->fetchAll() as $line) {
            $account = '4000';
            if (in_array($line['line_type'], ['part', 'material'], true)) {
                $account = '4010';
            } elseif ($line['line_type'] === 'fee') {
                $account = '4020';
            }
            $totals[$account] += round((float) $line['line_subtotal'], 2);
        }

        return array_map(static fn (float $amount): float => round($amount, 2), $totals);
    }

    private function cashAccountForMethod(string $method): string
    {
        if ($method === 'cash') {
            return '1000';
        }
        if (in_array($method, ['square', 'stripe', 'card'], true)) {
            return '1050';
        }

        return '1010';
    }
}
