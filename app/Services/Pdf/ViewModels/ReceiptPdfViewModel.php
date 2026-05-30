<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfMoney;

/**
 * Receipt PDF — record of a single payment against an invoice.
 *
 * Source-of-truth fields:
 *   receipt_number, created_at -> receipts row
 *   payment_number, payment_method, amount, transaction_reference, paid_at
 *     -> joined payments row
 *   invoice_number, total, amount_paid, balance_due
 *     -> joined invoices row
 *   first_name, last_name, phone -> joined customers row
 *
 * Totals shown ("Invoice Total", "Paid This Receipt", "Balance Remaining")
 * are surfaced directly from the invoices row, which is itself recomputed
 * by Invoice::applyPayment whenever a payment is recorded. The view model
 * does NOT silently fix mismatched numbers — those would indicate an
 * accounting bug worth investigating.
 */
final class ReceiptPdfViewModel extends PdfViewModel
{
    private array $receipt;

    public function __construct(array $receipt)
    {
        parent::__construct();
        $this->receipt = $receipt;
    }

    public function title(): string
    {
        return 'Receipt ' . ($this->receipt['receipt_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return 'Receipt ' . ($this->receipt['receipt_number'] ?? '');
    }

    public function render(PdfDocument $document): void
    {
        $validator = new PdfDataValidator();
        $validator->require($this->receipt['receipt_number'] ?? null, 'Receipt number');
        $validator->require($this->receipt['invoice_number'] ?? null, 'Invoice number');
        $validator->require($this->receipt['payment_number'] ?? null, 'Payment number');
        $validator->require(trim(($this->receipt['first_name'] ?? '') . ' ' . ($this->receipt['last_name'] ?? '')), 'Customer name');
        $validator->requirePositiveAmount($this->receipt['amount'] ?? null, 'Payment amount');
        $validator->failIfAny();

        $paidAt = $this->receipt['paid_at'] ?? $this->receipt['created_at'] ?? date('Y-m-d H:i:s');
        $paidTs = strtotime((string) $paidAt) ?: time();

        $this->paintMasthead($document, [
            ['Receipt #', (string) $this->receipt['receipt_number']],
            ['Invoice', (string) $this->receipt['invoice_number']],
            ['Payment', (string) $this->receipt['payment_number']],
            ['Date', date('M j, Y', $paidTs)],
            ['Time', date('g:i A', $paidTs)],
        ]);
        $document->banner('Receipt');

        $paidThis = round((float) ($this->receipt['amount'] ?? 0), 2);
        $invoiceTotal = round((float) ($this->receipt['total'] ?? 0), 2);
        $amountPaid = round((float) ($this->receipt['amount_paid'] ?? 0), 2);
        $balanceDue = round((float) ($this->receipt['balance_due'] ?? 0), 2);

        $document->metaStrip(
            $this->customerBlock($this->receipt),
            null,
            [
                ['Invoice Total', PdfMoney::format($invoiceTotal)],
                ['Paid (this)', PdfMoney::format($paidThis)],
                ['Paid (total)', PdfMoney::format($amountPaid)],
                ['Balance', PdfMoney::format($balanceDue)],
            ],
            'Paid (this)'
        );

        $document->sectionHeading('Payment Detail');
        $document->kvTable([
            ['Method', $this->formatMethod((string) ($this->receipt['payment_method'] ?? ''))],
            ['Amount', PdfMoney::format($paidThis)],
            ['Reference', (string) ($this->receipt['transaction_reference'] ?? 'None')],
            ['Captured At', date('M j, Y g:i A', $paidTs)],
        ]);

        $document->sectionHeading('Warranty & Comeback Policy');
        $document->paragraphs([
            'Workmanship — 12 months or 12,000 miles, whichever comes first, on labour performed.',
            'Parts supplied by us — pass-through of the manufacturer warranty; retain this receipt as proof of purchase.',
            'Comeback policy — call ' . $this->company->phone . ' for any warranty-period concern related to our work.',
        ]);

        $document->footer(
            'Thank you for choosing ' . $this->company->name . '. Questions about this receipt? '
            . 'Contact ' . $this->company->phone . ' or ' . $this->company->email . '. '
            . 'Please retain this receipt for warranty claims and your records.',
            ['showCustomer' => true, 'showTechnician' => false, 'customerLabel' => 'Customer Acknowledgement'],
            ['label' => 'Total Paid', 'amount' => PdfMoney::format($paidThis)]
        );
    }

    private function formatMethod(string $value): string
    {
        if ($value === '') {
            return 'Unknown';
        }
        return ucwords(str_replace('_', ' ', $value));
    }
}
