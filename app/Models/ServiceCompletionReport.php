<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class ServiceCompletionReport extends Repository
{
    public const STATUSES = ['completed', 'incomplete', 'cancelled'];

    public function findByWorkOrder(int $workOrderId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM service_completion_reports WHERE work_order_id = :work_order_id LIMIT 1');
        $stmt->execute(['work_order_id' => $workOrderId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT scr.*, wo.work_order_number, sr.service_request_number, sr.requested_service,
                    c.first_name, c.last_name, c.phone,
                    v.year, v.make, v.model, v.color, v.vin
             FROM service_completion_reports scr
             JOIN work_orders wo ON wo.id = scr.work_order_id
             JOIN service_requests sr ON sr.id = scr.service_request_id
             JOIN customers c ON c.id = scr.customer_id
             LEFT JOIN vehicles v ON v.id = scr.vehicle_id
             WHERE scr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromWorkOrder(array $workOrder, array $data): int
    {
        $existing = $this->findByWorkOrder((int) $workOrder['id']);

        if ($existing) {
            return (int) $existing['id'];
        }

        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO service_completion_reports (
                report_number, service_request_id, work_order_id, customer_id, vehicle_id,
                actual_work_performed, technician_notes, customer_notes, odometer,
                vin_captured, no_vehicle_serviced_flag, completion_status,
                completed_at, created_at, updated_at
            ) VALUES (
                :report_number, :service_request_id, :work_order_id, :customer_id, :vehicle_id,
                :actual_work_performed, :technician_notes, :customer_notes, :odometer,
                :vin_captured, :no_vehicle_serviced_flag, :completion_status,
                :completed_at, :created_at, :updated_at
            )'
        );
        $stmt->execute([
            'report_number' => NumberingService::next('SCR'),
            'service_request_id' => $workOrder['service_request_id'],
            'work_order_id' => $workOrder['id'],
            'customer_id' => $workOrder['customer_id'],
            'vehicle_id' => $workOrder['vehicle_id'] ?: null,
            'actual_work_performed' => $data['actual_work_performed'],
            'technician_notes' => $data['technician_notes'] ?: null,
            'customer_notes' => $data['customer_notes'] ?: null,
            'odometer' => $data['odometer'] ?: null,
            'vin_captured' => $data['vin_captured'] ?: null,
            'no_vehicle_serviced_flag' => !empty($data['no_vehicle_serviced_flag']) ? 1 : 0,
            'completion_status' => $data['completion_status'],
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }
}

