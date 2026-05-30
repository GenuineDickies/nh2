<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Estimate;
use App\Models\WorkOrder;

final class WorkOrderController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Work Orders',
            'active' => 'work-orders',
            'content' => 'work-orders/index',
            'workOrders' => (new WorkOrder())->search($q),
            'q' => $q,
        ]);
    }

    public function createFromEstimate(string $id): void
    {
        $estimate = (new Estimate())->findWithDetails((int) $id);

        if (!$estimate || $estimate['status'] !== 'approved') {
            $this->redirect('/estimates/' . (int) $id);
        }

        $workOrderId = (new WorkOrder())->createFromEstimate($estimate);
        (new AuditLog())->record('work_order_created', 'service_request', (int) $estimate['service_request_id'], null, [
            'estimate_id' => (int) $estimate['id'],
            'work_order_id' => $workOrderId,
        ]);

        $this->redirect('/work-orders/' . $workOrderId);
    }

    public function show(string $id): void
    {
        $workOrder = (new WorkOrder())->findWithDetails((int) $id);

        if (!$workOrder) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Work order not found',
                'message' => 'That work order could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $workOrder['work_order_number'],
            'active' => 'work-orders',
            'content' => 'work-orders/show',
            'workOrder' => $workOrder,
            'statuses' => WorkOrder::STATUSES,
        ]);
    }

    public function field(string $id): void
    {
        $workOrder = (new WorkOrder())->findWithDetails((int) $id);

        if (!$workOrder) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Work order not found',
                'message' => 'That work order could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => 'Field - ' . $workOrder['work_order_number'],
            'active' => 'work-orders',
            'content' => 'work-orders/field',
            'workOrder' => $workOrder,
        ]);
    }

    public function updateStatus(string $id): void
    {
        $workOrderId = (int) $id;
        $status = $this->input('status', '');
        $change = (new WorkOrder())->updateStatus($workOrderId, $status);

        if ($change && $change['old_status'] !== $change['new_status']) {
            (new AuditLog())->record('work_order_status_changed', 'work_order', $workOrderId, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
            ]);
        }

        $this->redirect('/work-orders/' . $workOrderId);
    }

    public function markArrived(string $id): void
    {
        $workOrderId = (int) $id;
        (new WorkOrder())->markArrived($workOrderId);
        (new AuditLog())->record('work_order_arrived', 'work_order', $workOrderId);

        $this->redirect('/work-orders/' . $workOrderId);
    }
}
