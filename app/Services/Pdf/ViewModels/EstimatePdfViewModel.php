<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfMoney;

/**
 * Estimate PDF — the customer-facing quote.
 *
 * Source-of-truth fields:
 *   estimate_number, status, subtotal, tax_total, total, disclaimer_text
 *     -> estimates row (reloaded after Estimate::recalculate)
 *   service_request_number, requested_service
 *     -> joined service_requests row
 *   first_name, last_name, phone, email
 *     -> joined customers row
 *   year, make, model, color, vin
 *     -> joined vehicles row (optional)
 *   line items
 *     -> estimate_line_items joined to catalog_items
 *
 * Nothing is invented here. If subtotal/tax/total drift from the line
 * items, the controller pre-calls Estimate::recalculate(); we re-fetch
 * and trust the recalculated values.
 */
final class EstimatePdfViewModel extends PdfViewModel
{
    private array $estimate;
    /** @var array<int, array<string, mixed>> */
    private array $lines;

    public function __construct(array $estimate, array $lines)
    {
        parent::__construct();
        $this->estimate = $estimate;
        $this->lines = $lines;
    }

    public function title(): string
    {
        return 'Estimate ' . ($this->estimate['estimate_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return 'Estimate ' . ($this->estimate['estimate_number'] ?? '');
    }

    public function render(PdfDocument $document): void
    {
        $validator = new PdfDataValidator();
        $validator->require($this->estimate['estimate_number'] ?? null, 'Estimate number');
        $validator->require($this->estimate['service_request_number'] ?? null, 'Service request number');
        $validator->require(trim(($this->estimate['first_name'] ?? '') . ' ' . ($this->estimate['last_name'] ?? '')), 'Customer name');
        $validator->requireNonEmpty($this->lines, 'Estimate line items');
        $validator->requirePositiveAmount($this->estimate['total'] ?? null, 'Estimate total');
        $validator->failIfAny();

        $createdAt = $this->estimate['created_at'] ?? date('Y-m-d H:i:s');
        $createdTs = strtotime((string) $createdAt) ?: time();

        $this->paintMasthead($document, [
            ['Estimate #', (string) $this->estimate['estimate_number']],
            ['Job', (string) $this->estimate['service_request_number']],
            ['Status', ucwords((string) $this->estimate['status'])],
            ['Date', date('M j, Y', $createdTs)],
            ['Time', date('g:i A', $createdTs)],
        ]);
        $document->banner('Estimate');

        // Recompute totals from the line items before painting anything,
        // so the meta strip and charges table cannot disagree. The
        // controller has already called Estimate::recalculate(), but
        // computing here too makes the view model self-contained for
        // tests.
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
        $tax = round($taxableSubtotal * Estimate::TAX_RATE, 2);
        $total = round($subtotal + $tax, 2);

        $document->metaStrip(
            $this->customerBlock($this->estimate),
            $this->vehicleTable($this->estimate),
            [
                ['Subtotal', PdfMoney::format($subtotal)],
                ['Tax', PdfMoney::format($tax)],
                ['Quoted Total', PdfMoney::format($total)],
            ],
            'Quoted Total'
        );

        $service = $this->estimate['requested_service'] ?? '';
        if ($service !== '') {
            $document->detailBar([
                ['Service Type', (string) $service],
            ]);
        }

        $document->chargesTable(
            $columns,
            $rows,
            [
                ['label' => 'Subtotal', 'amount' => PdfMoney::format($subtotal)],
                ['label' => 'Tax (' . self::formatPercent(Estimate::TAX_RATE) . ')', 'amount' => PdfMoney::format($tax)],
            ],
            ['label' => 'Quoted Total', 'amount' => PdfMoney::format($total)]
        );

        $document->sectionHeading('Disclaimer');
        $document->paragraphs([
            (string) ($this->estimate['disclaimer_text'] ?? Estimate::DISCLAIMER),
        ]);

        $document->footer(
            'This is a quote, not an invoice. Final invoice may vary if scope changes — see Disclaimer above. '
            . 'No customer signature is required on this Estimate; authorization is captured separately before work begins.',
            null,
            ['label' => 'Quoted Total', 'amount' => PdfMoney::format($total)]
        );
    }

    private static function formatPercent(float $rate): string
    {
        return rtrim(rtrim(number_format($rate * 100, 2), '0'), '.') . '%';
    }
}
