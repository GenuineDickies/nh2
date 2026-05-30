<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\FileAttachment;
use App\Models\ServiceCompletionReport;
use App\Models\WorkOrder;
use App\Services\FileUploadService;

final class ServiceReportController extends Controller
{
    public function new(): void
    {
        $workOrderId = (int) ($_GET['work_order_id'] ?? 0);
        $workOrder = $workOrderId > 0 ? (new WorkOrder())->findWithDetails($workOrderId) : null;

        $this->view('layouts/app', [
            'title' => 'New Service Report',
            'active' => 'work-orders',
            'content' => 'service-reports/new',
            'workOrder' => $workOrder,
            'errors' => [],
            'old' => $this->defaults($workOrder),
        ]);
    }

    public function create(): void
    {
        $workOrderId = (int) $this->input('work_order_id', '0');
        $workOrder = $workOrderId > 0 ? (new WorkOrder())->findWithDetails($workOrderId) : null;

        if (!$workOrder) {
            $this->redirect('/work-orders');
        }

        $data = $this->requestData();
        $errors = $this->validate($data, $workOrder);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'New Service Report',
                'active' => 'work-orders',
                'content' => 'service-reports/new',
                'workOrder' => $workOrder,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $reportId = (new ServiceCompletionReport())->createFromWorkOrder($workOrder, $data);

            if (!empty($data['vin_captured']) && !empty($workOrder['vehicle_id'])) {
                $stmt = $db->prepare('UPDATE vehicles SET vin = :vin, updated_at = :updated_at WHERE id = :id');
                $stmt->execute([
                    'vin' => $data['vin_captured'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id' => (int) $workOrder['vehicle_id'],
                ]);
            }

            (new WorkOrder())->updateStatus((int) $workOrder['id'], 'completed');
            (new AuditLog())->record('service_report_created', 'service_request', (int) $workOrder['service_request_id'], null, [
                'service_report_id' => $reportId,
                'work_order_id' => (int) $workOrder['id'],
            ]);
            $db->commit();
            $this->redirect('/service-reports/' . $reportId);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function show(string $id): void
    {
        $report = (new ServiceCompletionReport())->findWithDetails((int) $id);

        if (!$report) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Service report not found',
                'message' => 'That service report could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $report['report_number'],
            'active' => 'work-orders',
            'content' => 'service-reports/show',
            'report' => $report,
            'attachments' => (new FileAttachment())->forRelated('service_report', (int) $id),
            'uploadErrors' => [],
        ]);
    }

    public function uploadAttachment(string $id): void
    {
        $reportId = (int) $id;
        $report = (new ServiceCompletionReport())->findWithDetails($reportId);

        if (!$report) {
            $this->redirect('/work-orders');
        }

        $fileType = $this->input('file_type', 'photo') ?? 'photo';
        $caption = $this->input('caption', '');
        $result = (new FileUploadService())->storeUpload(
            $_FILES['attachment'] ?? [],
            'service_report',
            $reportId,
            $fileType,
            $caption
        );

        if ($result['errors']) {
            $this->view('layouts/app', [
                'title' => $report['report_number'],
                'active' => 'work-orders',
                'content' => 'service-reports/show',
                'report' => $report,
                'attachments' => (new FileAttachment())->forRelated('service_report', $reportId),
                'uploadErrors' => $result['errors'],
            ]);
            return;
        }

        (new AuditLog())->record('attachment_uploaded', 'service_request', (int) $report['service_request_id'], null, [
            'service_report_id' => $reportId,
            'attachment_id' => $result['attachment_id'],
            'file_type' => $fileType,
        ]);

        $this->redirect('/service-reports/' . $reportId);
    }

    private function requestData(): array
    {
        return [
            'work_order_id' => $this->input('work_order_id', '0'),
            'actual_work_performed' => $this->input('actual_work_performed', ''),
            'technician_notes' => $this->input('technician_notes', ''),
            'customer_notes' => $this->input('customer_notes', ''),
            'odometer' => $this->input('odometer', ''),
            'vin_captured' => strtoupper($this->input('vin_captured', '')),
            'no_vehicle_serviced_flag' => $this->input('no_vehicle_serviced_flag', '') === '1',
            'completion_status' => $this->input('completion_status', 'completed'),
        ];
    }

    private function validate(array $data, array $workOrder): array
    {
        $errors = [];

        if ($data['actual_work_performed'] === '') {
            $errors['actual_work_performed'] = 'Required';
        }

        if (!in_array($data['completion_status'], ServiceCompletionReport::STATUSES, true)) {
            $errors['completion_status'] = 'Choose a valid status';
        }

        $hasVehicle = !empty($workOrder['vehicle_id']);
        $hasVin = $data['vin_captured'] !== '' || !empty($workOrder['vin']);

        if ($hasVehicle && !$hasVin && empty($data['no_vehicle_serviced_flag'])) {
            $errors['vin_captured'] = 'Capture VIN or mark no vehicle serviced';
        }

        return $errors;
    }

    private function defaults(?array $workOrder): array
    {
        return [
            'actual_work_performed' => '',
            'technician_notes' => '',
            'customer_notes' => '',
            'odometer' => '',
            'vin_captured' => $workOrder['vin'] ?? '',
            'no_vehicle_serviced_flag' => 0,
            'completion_status' => 'completed',
        ];
    }
}
