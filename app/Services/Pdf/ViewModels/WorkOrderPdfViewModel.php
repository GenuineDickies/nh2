<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Core\View;
use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;
use App\Services\Pdf\PdfMoney;

/**
 * Work Order PDF — the dispatch ticket the technician carries on
 * scene. Not customer-facing on its own (Waiver / Receipt are the
 * signed customer documents), but printed for the technician and
 * stored on the job record.
 *
 * Source-of-truth fields:
 *   work_order_number, status, dispatch_started_at, arrived_at,
 *   completed_at, notes
 *     -> work_orders row
 *   estimate_number, estimate_total           -> joined estimates row
 *   service_request_number, requested_service -> joined service_requests
 *   first_name, last_name, phone              -> joined customers
 *   year, make, model, color, vin             -> joined vehicles
 *   address_line_1, city, state, postal_code  -> joined locations
 *
 * Nothing is invented. The estimate total comes from the already-
 * recalculated `estimates.total` (recalculated whenever the estimate
 * is saved); this view model does not recompute it because no line
 * items are presented — the WO defers itemisation to the estimate or
 * the eventual invoice.
 */
final class WorkOrderPdfViewModel extends PdfViewModel
{
    private array $workOrder;

    public function __construct(array $workOrder)
    {
        parent::__construct();
        $this->workOrder = $workOrder;
    }

    public function title(): string
    {
        return 'Work Order ' . ($this->workOrder['work_order_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return $this->title();
    }

    public function render(PdfDocument $document): void
    {
        $validator = new PdfDataValidator();
        $validator->require($this->workOrder['work_order_number'] ?? null, 'Work order number');
        $validator->require($this->workOrder['service_request_number'] ?? null, 'Service request number');
        $validator->require(trim(($this->workOrder['first_name'] ?? '') . ' ' . ($this->workOrder['last_name'] ?? '')), 'Customer name');
        $validator->failIfAny();

        $createdTs = strtotime((string) ($this->workOrder['created_at'] ?? '')) ?: time();

        $meta = [
            ['Work Order #', (string) $this->workOrder['work_order_number']],
            ['Job', (string) $this->workOrder['service_request_number']],
            ['Status', ucwords(str_replace('_', ' ', (string) $this->workOrder['status']))],
            ['Date', date('M j, Y', $createdTs)],
            ['Time', date('g:i A', $createdTs)],
        ];
        if (!empty($this->workOrder['estimate_number'])) {
            $meta[] = ['Estimate', (string) $this->workOrder['estimate_number']];
        }
        $this->paintMasthead($document, $meta);
        $document->banner('Work Order');

        // The "totals" column on the right shows the dispatch
        // timeline rather than money — the WO is about timing /
        // execution, not billing. The estimate total is included as
        // the agreed budget reference.
        $timelineRows = [];
        if (!empty($this->workOrder['estimate_total'])) {
            $timelineRows[] = ['Quoted', PdfMoney::format($this->workOrder['estimate_total'])];
        }
        $timelineRows[] = ['Dispatched', $this->fmtTime($this->workOrder['dispatch_started_at'] ?? null)];
        $timelineRows[] = ['Arrived', $this->fmtTime($this->workOrder['arrived_at'] ?? null)];
        $timelineRows[] = ['Completed', $this->fmtTime($this->workOrder['completed_at'] ?? null)];

        $document->metaStrip(
            $this->customerBlock($this->workOrder, $this->workOrder),
            $this->vehicleTable($this->workOrder),
            $timelineRows,
            !empty($this->workOrder['completed_at']) ? 'Completed' : null
        );

        $document->detailBar([
            ['Service Type', (string) ($this->workOrder['requested_service'] ?? 'Not specified')],
            ['Location', View::address($this->workOrder, 'Not captured')],
        ]);

        $document->sectionHeading('Dispatch Notes');
        $notes = trim((string) ($this->workOrder['notes'] ?? ''));
        $document->paragraphs([
            $notes !== '' ? $notes : 'No additional dispatch notes recorded.',
        ]);

        // Technician on-scene checklist — workflow-only, not a data
        // entry surface. Each item references a downstream document
        // that captures the actual data.
        $document->sectionHeading('On-Scene Workflow');
        $document->bullets([
            'Confirm customer identity and location before unloading equipment.',
            'Complete the Hazard Assessment before any work begins.',
            'Capture vehicle condition photos (Vehicle Condition Report) prior to touching the vehicle.',
            'Have the customer sign the Service Authorization, Charges & Liability Waiver before work starts.',
            'If hidden conditions push the price up by more than the lesser of 10% or $200, STOP and produce a Change Order for signature.',
            'Capture Service Completion Report at finish: actual work performed, technician notes, odometer, captured VIN.',
            'Collect customer signature on the Receipt to anchor the warranty.',
        ]);

        $document->footer(
            'Internal dispatch ticket. The customer-facing documents for this job are the signed Waiver (before work) '
            . 'and the signed Receipt (at completion). This Work Order is the technician\'s execution record.',
            ['showCustomer' => false, 'showTechnician' => true, 'techLabel' => 'Technician — Dispatched / Completed'],
            null
        );
    }

    private function fmtTime(mixed $value): string
    {
        if (!$value) {
            return '—';
        }
        $ts = strtotime((string) $value);
        if (!$ts) {
            return '—';
        }
        return date('M j, g:i A', $ts);
    }
}
