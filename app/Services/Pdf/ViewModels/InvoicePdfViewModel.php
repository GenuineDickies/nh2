<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Models\Invoice;
use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfMoney;

/**
 * Invoice PDF.
 *
 * Source-of-truth fields:
 *   invoice_number, status, subtotal, tax_total, total, amount_paid,
 *   balance_due, issued_at, no_vehicle_serviced_flag
 *     -> invoices row (reloaded after Invoice::recalculate)
 *   service_request_number, requested_service, report_number
 *     -> joined service_requests / service_completion_reports
 *   customer, vehicle  -> joined customers / vehicles
 *   line items         -> invoice_line_items
 *
 * The amounts shown in totals are RECOMPUTED from the line items at
 * render time, not blindly trusted from the stored row. The view model
 * does not mutate the database — Invoice::recalculate() is called by
 * the controller before construction.
 */
final class InvoicePdfViewModel extends PdfViewModel
{
    private array $invoice;
    /** @var array<int, array<string, mixed>> */
    private array $lines;

    public function __construct(array $invoice, array $lines)
    {
        parent::__construct();
        $this->invoice = $invoice;
        $this->lines = $lines;
    }

    public function title(): string
    {
        return 'Invoice ' . ($this->invoice['invoice_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return 'Invoice ' . ($this->invoice['invoice_number'] ?? '');
    }

    public function render(PdfDocument $document): void
    {
        $validator = new PdfDataValidator();
        $validator->require($this->invoice['invoice_number'] ?? null, 'Invoice number');
        $validator->require($this->invoice['service_request_number'] ?? null, 'Service request number');
        $validator->require(trim(($this->invoice['first_name'] ?? '') . ' ' . ($this->invoice['last_name'] ?? '')), 'Customer name');
        $validator->requireNonEmpty($this->lines, 'Invoice line items');
        if (empty($this->invoice['vin']) && (int) ($this->invoice['no_vehicle_serviced_flag'] ?? 0) !== 1) {
            $validator->add('Vehicle VIN is required (set the no-vehicle-serviced flag on the work order to skip)');
        }
        $validator->requirePositiveAmount($this->invoice['total'] ?? null, 'Invoice total');
        $validator->failIfAny();

        $issuedAt = $this->invoice['issued_at'] ?? $this->invoice['created_at'] ?? date('Y-m-d H:i:s');
        $issuedTs = strtotime((string) $issuedAt) ?: time();

        $meta = [
            ['Invoice #', (string) $this->invoice['invoice_number']],
            ['Job', (string) $this->invoice['service_request_number']],
            ['Status', ucwords(str_replace('_', ' ', (string) $this->invoice['status']))],
            ['Date', date('M j, Y', $issuedTs)],
            ['Time', date('g:i A', $issuedTs)],
        ];
        if (!empty($this->invoice['report_number'])) {
            $meta[] = ['Report', (string) $this->invoice['report_number']];
        }
        $this->paintMasthead($document, $meta);
        $document->banner('Invoice');

        // Recompute totals from line items so the meta strip and
        // charges table show the same numbers. The controller has
        // already called Invoice::recalculate(); doing it again here
        // makes the view model self-contained for tests.
        $columns = [
            ['label' => 'Description', 'align' => 'left'],
            ['label' => 'Qty', 'align' => 'right', 'width' => 50.0],
            ['label' => 'Unit Price', 'align' => 'right', 'width' => 80.0],
            ['label' => 'Amount', 'align' => 'right', 'width' => 90.0],
        ];
        $rows = [];
        $subtotal = 0.0;
        $taxableSubtotal = 0.0;
        foreach ($this->lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unit = (float) ($line['unit_price'] ?? 0);
            $lineSubtotal = round($qty * $unit, 2);
            $subtotal += $lineSubtotal;
            if ((int) ($line['taxable'] ?? 0) === 1) {
                $taxableSubtotal += $lineSubtotal;
            }
            $desc = (string) ($line['description'] ?? '');
            if ((int) ($line['taxable'] ?? 0) === 1) {
                $desc .= ' (taxable)';
            }
            $rows[] = [
                $desc,
                rtrim(rtrim(number_format($qty, 2), '0'), '.') ?: '0',
                PdfMoney::format($unit),
                PdfMoney::format($lineSubtotal),
            ];
        }
        $subtotal = round($subtotal, 2);
        $tax = round($taxableSubtotal * Invoice::TAX_RATE, 2);
        $total = round($subtotal + $tax, 2);
        $amountPaid = round((float) ($this->invoice['amount_paid'] ?? 0), 2);
        $balanceDue = max(0.0, round($total - $amountPaid, 2));

        $document->metaStrip(
            $this->customerBlock($this->invoice),
            $this->vehicleTable($this->invoice),
            [
                ['Subtotal', PdfMoney::format($subtotal)],
                ['Tax', PdfMoney::format($tax)],
                ['Total', PdfMoney::format($total)],
                ['Paid', PdfMoney::format($amountPaid)],
                ['Balance Due', PdfMoney::format($balanceDue)],
            ],
            'Balance Due'
        );

        if (!empty($this->invoice['requested_service'])) {
            $document->detailBar([
                ['Service Type', (string) $this->invoice['requested_service']],
            ]);
        }

        $document->chargesTable(
            $columns,
            $rows,
            [
                ['label' => 'Subtotal', 'amount' => PdfMoney::format($subtotal)],
                ['label' => 'Tax (' . self::formatPercent(Invoice::TAX_RATE) . ')', 'amount' => PdfMoney::format($tax)],
                ['label' => 'Total', 'amount' => PdfMoney::format($total)],
                ['label' => 'Payments Received', 'amount' => PdfMoney::format($amountPaid)],
            ],
            ['label' => 'Balance Due', 'amount' => PdfMoney::format($balanceDue)]
        );

        $document->sectionHeading('Payment Instructions');
        $document->paragraphs([
            'Payment is due on receipt unless other arrangements have been made in writing. '
            . 'Make checks payable to ' . $this->company->legalName . '. '
            . 'For questions, contact ' . $this->company->phone . ' or ' . $this->company->email . '.',
        ]);

        $document->footer(
            'Retain this invoice for your records. Workmanship is covered for 12 months or 12,000 miles, '
            . 'whichever comes first. Parts pass through their manufacturer warranty.',
            null,
            ['label' => 'Balance Due', 'amount' => PdfMoney::format($balanceDue)]
        );
    }

    private static function formatPercent(float $rate): string
    {
        return rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') . '%';
    }
}
