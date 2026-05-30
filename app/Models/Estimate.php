<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class Estimate extends Repository
{
    public const DISCLAIMER = 'Final invoice may vary if the scope of work changes. Customer authorization is required for material changes, including differences over $200 or over 10%, whichever is smaller.';
    public const TAX_RATE = 0.095;
    public const STATUSES = ['draft', 'sent', 'approved', 'declined', 'expired', 'converted'];

    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE e.estimate_number LIKE :q
                       OR sr.service_request_number LIKE :q
                       OR COALESCE(sr.requested_service, '') LIKE :q
                       OR e.status LIKE :q
                       OR c.first_name LIKE :q
                       OR c.last_name LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT e.*, c.first_name, c.last_name, sr.service_request_number, sr.requested_service
                FROM estimates e
                JOIN customers c ON c.id = e.customer_id
                JOIN service_requests sr ON sr.id = e.service_request_id
                {$where}
                ORDER BY e.created_at DESC, e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, c.first_name, c.last_name, c.phone,
                    sr.service_request_number, sr.requested_service,
                    v.year, v.make, v.model, v.color, v.vin
             FROM estimates e
             JOIN customers c ON c.id = e.customer_id
             JOIN service_requests sr ON sr.id = e.service_request_id
             LEFT JOIN vehicles v ON v.id = e.vehicle_id
             WHERE e.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromServiceRequest(array $serviceRequest): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO estimates (
                estimate_number, service_request_id, customer_id, vehicle_id, status,
                subtotal, tax_total, total, disclaimer_text, created_at, updated_at
            ) VALUES (
                :estimate_number, :service_request_id, :customer_id, :vehicle_id, :status,
                :subtotal, :tax_total, :total, :disclaimer_text, :created_at, :updated_at
            )'
        );
        $now = $this->now();
        $stmt->execute([
            'estimate_number' => NumberingService::next('EST'),
            'service_request_id' => $serviceRequest['id'],
            'customer_id' => $serviceRequest['customer_id'],
            'vehicle_id' => $serviceRequest['vehicle_id'] ?: null,
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'disclaimer_text' => self::DISCLAIMER,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function recalculate(int $id): void
    {
        $stmt = $this->db->prepare('SELECT quantity, unit_price, taxable FROM estimate_line_items WHERE estimate_id = :id');
        $stmt->execute(['id' => $id]);
        $subtotal = 0.0;
        $taxableSubtotal = 0.0;

        foreach ($stmt->fetchAll() as $line) {
            $lineSubtotal = round((float) $line['quantity'] * (float) $line['unit_price'], 2);
            $subtotal += $lineSubtotal;

            if ((int) $line['taxable'] === 1) {
                $taxableSubtotal += $lineSubtotal;
            }
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxableSubtotal * self::TAX_RATE, 2);
        $total = round($subtotal + $taxTotal, 2);

        $update = $this->db->prepare(
            'UPDATE estimates
             SET subtotal = :subtotal, tax_total = :tax_total, total = :total, updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function approvalRequired(array $estimate): bool
    {
        return (float) $estimate['total'] > 200;
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

        $approvedAtSql = $status === 'approved' ? ', approved_at = :approved_at' : '';
        $stmt = $this->db->prepare(
            "UPDATE estimates
             SET status = :status, updated_at = :updated_at {$approvedAtSql}
             WHERE id = :id"
        );
        $params = [
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ];

        if ($status === 'approved') {
            $params['approved_at'] = $this->now();
        }

        $stmt->execute($params);

        return [
            'old_status' => $current['status'],
            'new_status' => $status,
        ];
    }
}
