<?php

declare(strict_types=1);

/**
 * Smoke test for the new PDF pipeline. Builds each view model with
 * realistic stub data, renders it, and verifies the resulting bytes
 * parse as a PDF (%PDF header + %%EOF trailer + non-trivial size).
 *
 * Run with:  php scripts/smoke-test-pdf.php
 *
 * Generated PDFs are written to storage/smoke-test-pdfs/ so a human
 * can open them in a reader to spot-check the visual output.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfValidationException;
use App\Services\Pdf\ViewModels\EstimatePdfViewModel;
use App\Services\Pdf\ViewModels\InvoicePdfViewModel;
use App\Services\Pdf\ViewModels\ProofPacketPdfViewModel;
use App\Services\Pdf\ViewModels\ReceiptPdfViewModel;
use App\Services\Pdf\ViewModels\ServiceCompletionReportPdfViewModel;
use App\Services\Pdf\ViewModels\WorkOrderPdfViewModel;

$outDir = dirname(__DIR__) . '/storage/smoke-test-pdfs';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$now = '2026-05-24 14:32:11';

$estimate = [
    'id' => 1,
    'estimate_number' => 'EST-20260524-001-V1',
    'status' => 'draft',
    'service_request_number' => 'SER-20260524-001-V1',
    'requested_service' => 'Mobile Mechanic — Battery Replacement',
    'first_name' => 'Alex',
    'last_name' => 'Rivera',
    'phone' => '(503) 555-0142',
    'email' => 'alex.rivera@example.com',
    'year' => '2017',
    'make' => 'Toyota',
    'model' => 'Corolla LE',
    'color' => 'Silver',
    'vin' => '4T1BF1FK3HU000123',
    'subtotal' => 180.00,
    'tax_total' => 9.50,
    'total' => 189.50,
    'disclaimer_text' => 'Final invoice may vary if the scope of work changes. Oregon law requires customer authorization for any change above the lesser of 10% or $200.',
    'created_at' => $now,
];

$estimateLines = [
    [
        'description' => 'Mobile Mechanic — Battery Replacement (Group 51R AGM)',
        'quantity' => 1,
        'unit_price' => 120.00,
        'taxable' => 0,
        'line_subtotal' => 120.00,
    ],
    [
        'description' => 'Replacement AGM battery, Group 51R',
        'quantity' => 1,
        'unit_price' => 60.00,
        'taxable' => 1,
        'line_subtotal' => 60.00,
    ],
];

$invoice = array_merge($estimate, [
    'invoice_number' => 'INV-20260524-001-V1',
    'status' => 'sent',
    'report_number' => 'SCR-20260524-001-V1',
    'issued_at' => $now,
    'amount_paid' => 50.00,
    'balance_due' => 139.50,
    'no_vehicle_serviced_flag' => 0,
]);
unset($invoice['estimate_number']);

$invoiceLines = $estimateLines;

$receipt = [
    'receipt_number' => 'RCT-20260524-001-V1',
    'invoice_number' => 'INV-20260524-001-V1',
    'payment_number' => 'PAY-20260524-001-V1',
    'paid_at' => $now,
    'created_at' => $now,
    'first_name' => 'Alex',
    'last_name' => 'Rivera',
    'phone' => '(503) 555-0142',
    'payment_method' => 'card',
    'amount' => 50.00,
    'transaction_reference' => 'A1B2C3D4',
    'total' => 189.50,
    'amount_paid' => 50.00,
    'balance_due' => 139.50,
];

$packet = [
    'service_request' => [
        'service_request_number' => 'SER-20260524-001-V1',
        'status' => 'completed',
        'requested_service' => 'Mobile Mechanic — Battery Replacement',
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'phone' => '(503) 555-0142',
        'email' => 'alex.rivera@example.com',
        'year' => '2017',
        'make' => 'Toyota',
        'model' => 'Corolla LE',
        'color' => 'Silver',
        'vin' => '4T1BF1FK3HU000123',
        'address_line_1' => '1234 SE Example St',
        'city' => 'Portland',
        'state' => 'OR',
        'postal_code' => '97214',
        'created_at' => $now,
    ],
    'estimate' => ['estimate_number' => 'EST-20260524-001-V1', 'total' => 189.50],
    'approval' => ['approval_number' => 'EAP-20260524-001-V1', 'approval_method' => 'sms_link'],
    'work_order' => ['work_order_number' => 'WOR-20260524-001-V1', 'status' => 'completed'],
    'service_report' => ['report_number' => 'SCR-20260524-001-V1', 'completion_status' => 'success'],
    'invoice' => [
        'invoice_number' => 'INV-20260524-001-V1',
        'total' => 189.50,
        'amount_paid' => 50.00,
        'balance_due' => 139.50,
    ],
    'payments' => [
        ['payment_number' => 'PAY-20260524-001-V1', 'payment_method' => 'card', 'paid_at' => $now, 'amount' => 50.00],
    ],
    'receipts' => [
        ['receipt_number' => 'RCT-20260524-001-V1'],
    ],
    'ledger_entries' => [
        ['entry_number' => 'JRN-20260524-001-V1', 'source_type' => 'invoice', 'debit_total' => 189.50, 'credit_total' => 189.50],
    ],
    'attachments' => [
        ['file_type' => 'photo', 'original_filename' => 'battery-before.jpg', 'caption' => 'Pre-work corroded terminal'],
        ['file_type' => 'signature', 'original_filename' => 'sig.png', 'caption' => 'Customer waiver signature'],
    ],
    'missing_items' => [],
];

$workOrder = [
    'work_order_number' => 'WOR-20260524-001-V1',
    'service_request_number' => 'SER-20260524-001-V1',
    'estimate_number' => 'EST-20260524-001-V1',
    'estimate_total' => 189.50,
    'requested_service' => 'Mobile Mechanic — Battery Replacement',
    'status' => 'dispatched',
    'created_at' => $now,
    'dispatch_started_at' => '2026-05-24 14:35:00',
    'arrived_at' => '2026-05-24 15:02:00',
    'completed_at' => null,
    'notes' => 'Customer reports slow crank, will inspect charging system on scene.',
    'first_name' => 'Alex',
    'last_name' => 'Rivera',
    'phone' => '(503) 555-0142',
    'year' => '2017',
    'make' => 'Toyota',
    'model' => 'Corolla LE',
    'color' => 'Silver',
    'vin' => '4T1BF1FK3HU000123',
    'address_line_1' => '1234 SE Example St',
    'city' => 'Portland',
    'state' => 'OR',
    'postal_code' => '97214',
];

$completionReport = [
    'report_number' => 'SCR-20260524-001-V1',
    'work_order_number' => 'WOR-20260524-001-V1',
    'service_request_number' => 'SER-20260524-001-V1',
    'requested_service' => 'Mobile Mechanic — Battery Replacement',
    'completion_status' => 'completed',
    'completed_at' => $now,
    'actual_work_performed' => 'Replaced Group 51R AGM battery. Charging system load-tested at 14.1V — within spec. '
        . 'Cleaned and re-coated both battery terminals. Verified no-start condition resolved with three cold cranks.',
    'technician_notes' => 'Right-side terminal showed early corrosion; recommended inspection at next service.',
    'customer_notes' => 'Customer asked us to check for any other warning lights — none observed.',
    'odometer' => '112,481',
    'vin_captured' => '4T1BF1FK3HU000123',
    'no_vehicle_serviced_flag' => 0,
    'first_name' => 'Alex',
    'last_name' => 'Rivera',
    'phone' => '(503) 555-0142',
    'year' => '2017',
    'make' => 'Toyota',
    'model' => 'Corolla LE',
    'color' => 'Silver',
    'vin' => '4T1BF1FK3HU000123',
];

$cases = [
    'estimate.pdf' => new EstimatePdfViewModel($estimate, $estimateLines),
    'invoice.pdf' => new InvoicePdfViewModel($invoice, $invoiceLines),
    'receipt.pdf' => new ReceiptPdfViewModel($receipt),
    'work-order.pdf' => new WorkOrderPdfViewModel($workOrder),
    'service-completion-report.pdf' => new ServiceCompletionReportPdfViewModel($completionReport),
    'proof-packet.pdf' => new ProofPacketPdfViewModel($packet),
];

$failures = 0;
foreach ($cases as $filename => $viewModel) {
    $doc = new PdfDocument();
    try {
        $viewModel->render($doc);
    } catch (PdfValidationException $e) {
        echo "FAIL  {$filename}: validation — " . $e->getMessage() . PHP_EOL;
        $failures++;
        continue;
    }
    $bytes = $doc->output();
    $size = strlen($bytes);
    $ok = str_starts_with($bytes, '%PDF-') && str_contains($bytes, '%%EOF') && $size > 600;
    file_put_contents($outDir . '/' . $filename, $bytes);
    echo ($ok ? 'OK    ' : 'FAIL  ') . $filename . ' (' . $size . ' bytes)' . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

// Validation should fire when required data is missing.
echo PHP_EOL . 'Negative case: empty estimate should throw' . PHP_EOL;
try {
    (new EstimatePdfViewModel(['estimate_number' => '', 'created_at' => $now], []))->render(new PdfDocument());
    echo 'FAIL  empty-estimate: no exception thrown' . PHP_EOL;
    $failures++;
} catch (PdfValidationException $e) {
    echo 'OK    empty-estimate threw: ' . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . ($failures === 0 ? 'All checks passed.' : "{$failures} check(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
