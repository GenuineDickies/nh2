<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Services\Pdf\PdfDataValidator;
use App\Services\Pdf\PdfDocument;

/**
 * Service Completion Report PDF — factual record of what was actually
 * done on scene, including captured VIN, odometer reading, technician
 * notes, and customer notes. Produced after the technician closes the
 * work order; feeds the Invoice that follows.
 *
 * Source-of-truth fields:
 *   report_number, completion_status, actual_work_performed,
 *   technician_notes, customer_notes, odometer, vin_captured,
 *   no_vehicle_serviced_flag, completed_at
 *     -> service_completion_reports row
 *   work_order_number          -> joined work_orders row
 *   service_request_number, requested_service -> joined service_requests
 *   first_name, last_name, phone               -> joined customers
 *   year, make, model, color, vin              -> joined vehicles
 */
final class ServiceCompletionReportPdfViewModel extends PdfViewModel
{
    private array $report;

    public function __construct(array $report)
    {
        parent::__construct();
        $this->report = $report;
    }

    public function title(): string
    {
        return 'Service Completion ' . ($this->report['report_number'] ?? '');
    }

    public function fileCaption(): string
    {
        return $this->title();
    }

    public function render(PdfDocument $document): void
    {
        $validator = new PdfDataValidator();
        $validator->require($this->report['report_number'] ?? null, 'Completion report number');
        $validator->require($this->report['work_order_number'] ?? null, 'Work order number');
        $validator->require($this->report['service_request_number'] ?? null, 'Service request number');
        $validator->require(trim(($this->report['first_name'] ?? '') . ' ' . ($this->report['last_name'] ?? '')), 'Customer name');
        $validator->require($this->report['actual_work_performed'] ?? null, 'Actual work performed');
        if (empty($this->report['vin_captured']) && (int) ($this->report['no_vehicle_serviced_flag'] ?? 0) !== 1) {
            $validator->add('Captured VIN is required (set the no-vehicle-serviced flag to skip)');
        }
        $validator->failIfAny();

        $completedTs = strtotime((string) ($this->report['completed_at'] ?? '')) ?: time();

        $this->paintMasthead($document, [
            ['Report #', (string) $this->report['report_number']],
            ['Work Order', (string) $this->report['work_order_number']],
            ['Job', (string) $this->report['service_request_number']],
            ['Status', ucwords((string) $this->report['completion_status'])],
            ['Completed', date('M j, Y g:i A', $completedTs)],
        ]);
        $document->banner('Service Completion');

        // Vehicle table mixes the joined `vehicles` row with the on-
        // scene captured VIN + odometer — captured values can differ
        // from the stored vehicle record (different VIN scanned, etc.)
        // and the as-captured values are what the technician swears to.
        $vehicleRows = $this->vehicleTable($this->report);
        if (!empty($this->report['vin_captured'])) {
            $vehicleRows[] = ['VIN Captured', (string) $this->report['vin_captured']];
        }
        if (!empty($this->report['odometer'])) {
            $vehicleRows[] = ['Odometer', (string) $this->report['odometer']];
        }
        if ((int) ($this->report['no_vehicle_serviced_flag'] ?? 0) === 1) {
            $vehicleRows[] = ['No-vehicle flag', 'Yes — no vehicle serviced'];
        }

        $document->metaStrip(
            $this->customerBlock($this->report),
            $vehicleRows,
            null,
            null
        );

        if (!empty($this->report['requested_service'])) {
            $document->detailBar([
                ['Originally Requested', (string) $this->report['requested_service']],
            ]);
        }

        $document->sectionHeading('Actual Work Performed');
        $document->paragraphs([
            (string) $this->report['actual_work_performed'],
        ]);

        $techNotes = trim((string) ($this->report['technician_notes'] ?? ''));
        if ($techNotes !== '') {
            $document->sectionHeading('Technician Notes');
            $document->paragraphs([$techNotes]);
        }

        $custNotes = trim((string) ($this->report['customer_notes'] ?? ''));
        if ($custNotes !== '') {
            $document->sectionHeading('Customer Notes');
            $document->paragraphs([$custNotes]);
        }

        $document->footer(
            'This Service Completion Report records the work performed on scene. '
            . 'It is the basis for the Invoice that follows and is retained as part of '
            . 'the proof packet for this job.',
            ['showCustomer' => false, 'showTechnician' => true, 'techLabel' => 'Technician — Report Author'],
            null
        );
    }
}
