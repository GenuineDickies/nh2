<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class WorkOrder extends Repository
{
    public const STATUSES = ['pending', 'dispatched', 'completed', 'cancelled', 'invoiced'];

    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE wo.work_order_number LIKE :q
                       OR sr.service_request_number LIKE :q
                       OR COALESCE(e.estimate_number, '') LIKE :q
                       OR COALESCE(sr.requested_service, '') LIKE :q
                       OR wo.status LIKE :q
                       OR c.first_name LIKE :q
                       OR c.last_name LIKE :q
                       OR c.phone LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT wo.*, e.estimate_number, sr.service_request_number, sr.requested_service,
                    c.first_name, c.last_name, c.phone
                FROM work_orders wo
                JOIN service_requests sr ON sr.id = wo.service_request_id
                LEFT JOIN estimates e ON e.id = wo.estimate_id
                JOIN customers c ON c.id = sr.customer_id
                {$where}
                ORDER BY wo.created_at DESC, wo.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT wo.*, e.estimate_number, e.total AS estimate_total,
                    sr.service_request_number, sr.requested_service, sr.customer_id, sr.vehicle_id, sr.location_id,
                    c.first_name, c.last_name, c.phone,
                    v.year, v.make, v.model, v.color, v.vin,
                    l.address_line_1, l.city, l.state, l.postal_code
             FROM work_orders wo
             JOIN service_requests sr ON sr.id = wo.service_request_id
             LEFT JOIN estimates e ON e.id = wo.estimate_id
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             JOIN locations l ON l.id = sr.location_id
             WHERE wo.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByEstimate(int $estimateId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM work_orders WHERE estimate_id = :estimate_id LIMIT 1');
        $stmt->execute(['estimate_id' => $estimateId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromEstimate(array $estimate): int
    {
        $existing = $this->findByEstimate((int) $estimate['id']);

        if ($existing) {
            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO work_orders (
                work_order_number, service_request_id, estimate_id, status, created_at, updated_at
            ) VALUES (
                :work_order_number, :service_request_id, :estimate_id, :status, :created_at, :updated_at
            )'
        );
        $now = $this->now();
        $stmt->execute([
            'work_order_number' => NumberingService::next('WOR'),
            'service_request_id' => $estimate['service_request_id'],
            'estimate_id' => $estimate['id'],
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): ?array
    {
        if (!in_array($status, self::STATUSES, true)) {
            return null;
        }

        $current = $this->findWithDetails($id);

        if (!$current) {
            return null;
        }

        $fields = ['status = :status', 'updated_at = :updated_at'];
        $params = [
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ];

        if ($status === 'dispatched' && empty($current['dispatch_started_at'])) {
            $fields[] = 'dispatch_started_at = :dispatch_started_at';
            $params['dispatch_started_at'] = $this->now();
        }

        if ($status === 'completed' && empty($current['completed_at'])) {
            $fields[] = 'completed_at = :completed_at';
            $params['completed_at'] = $this->now();
        }

        $stmt = $this->db->prepare('UPDATE work_orders SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        return [
            'old_status' => $current['status'],
            'new_status' => $status,
        ];
    }

    public function markArrived(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE work_orders SET arrived_at = :arrived_at, updated_at = :updated_at WHERE id = :id');
        $now = $this->now();
        $stmt->execute([
            'arrived_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);
    }
}
