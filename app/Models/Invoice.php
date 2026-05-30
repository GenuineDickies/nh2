<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class Invoice extends Repository
{
    public const TAX_RATE = 0.095;
    public const STATUSES = ['draft', 'sent', 'partially_paid', 'paid', 'void', 'cancelled'];

    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = 'WHERE i.invoice_number LIKE :q
                       OR sr.service_request_number LIKE :q
                       OR i.status LIKE :q
                       OR c.first_name LIKE :q
                       OR c.last_name LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT i.*, c.first_name, c.last_name, sr.service_request_number
                FROM invoices i
                JOIN customers c ON c.id = i.customer_id
                JOIN service_requests sr ON sr.id = i.service_request_id
                {$where}
                ORDER BY i.created_at DESC, i.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, c.first_name, c.last_name, c.phone,
                    sr.service_request_number, sr.requested_service,
                    scr.report_number,
                    v.year, v.make, v.model, v.color, v.vin
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             JOIN service_requests sr ON sr.id = i.service_request_id
             LEFT JOIN service_completion_reports scr ON scr.id = i.service_completion_report_id
             LEFT JOIN vehicles v ON v.id = i.vehicle_id
             WHERE i.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByServiceReport(int $reportId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoices WHERE service_completion_report_id = :report_id LIMIT 1');
        $stmt->execute(['report_id' => $reportId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createFromServiceReport(array $report): int
    {
        $existing = $this->findByServiceReport((int) $report['id']);

        if ($existing) {
            return (int) $existing['id'];
        }

        $estimateId = $this->estimateIdForWorkOrder((int) $report['work_order_id']);
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO invoices (
                invoice_number, service_request_id, estimate_id, service_completion_report_id,
                customer_id, vehicle_id, no_vehicle_serviced_flag, status,
                subtotal, tax_total, total, amount_paid, balance_due,
                created_at, updated_at
            ) VALUES (
                :invoice_number, :service_request_id, :estimate_id, :service_completion_report_id,
                :customer_id, :vehicle_id, :no_vehicle_serviced_flag, :status,
                :subtotal, :tax_total, :total, :amount_paid, :balance_due,
                :created_at, :updated_at
            )'
        );
        $stmt->execute([
            'invoice_number' => NumberingService::next('INV'),
            'service_request_id' => $report['service_request_id'],
            'estimate_id' => $estimateId,
            'service_completion_report_id' => $report['id'],
            'customer_id' => $report['customer_id'],
            'vehicle_id' => $report['vehicle_id'] ?: null,
            'no_vehicle_serviced_flag' => (int) $report['no_vehicle_serviced_flag'],
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_due' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $invoiceId = (int) $this->db->lastInsertId();

        if ($estimateId) {
            (new InvoiceLineItem())->copyFromEstimate($invoiceId, $estimateId);
            $this->recalculate($invoiceId);
        }

        return $invoiceId;
    }

    public function recalculate(int $id): void
    {
        $stmt = $this->db->prepare('SELECT quantity, unit_price, taxable FROM invoice_line_items WHERE invoice_id = :id');
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
        $invoice = $this->findWithDetails($id);
        $amountPaid = $invoice ? (float) $invoice['amount_paid'] : 0.0;
        $balanceDue = max(0, round($total - $amountPaid, 2));
        $status = $invoice['status'] ?? 'draft';
        if ($amountPaid > 0 && $balanceDue <= 0) {
            $status = 'paid';
        } elseif ($amountPaid > 0) {
            $status = 'partially_paid';
        }

        $update = $this->db->prepare(
            'UPDATE invoices
             SET subtotal = :subtotal, tax_total = :tax_total, total = :total,
                 balance_due = :balance_due, status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'balance_due' => $balanceDue,
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function issue(int $id): ?array
    {
        $invoice = $this->findWithDetails($id);

        if (!$invoice || $invoice['status'] !== 'draft' || $this->validationErrors($invoice)) {
            return null;
        }

        $now = $this->now();
        $stmt = $this->db->prepare(
            'UPDATE invoices
             SET status = :status, issued_at = :issued_at, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'sent',
            'issued_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);

        return [
            'old_status' => $invoice['status'],
            'new_status' => 'sent',
        ];
    }

    public function applyPayment(int $id, float $amount): void
    {
        $invoice = $this->findWithDetails($id);
        if (!$invoice) {
            return;
        }

        $amountPaid = round((float) $invoice['amount_paid'] + $amount, 2);
        $balanceDue = max(0, round((float) $invoice['total'] - $amountPaid, 2));
        $status = $balanceDue <= 0 ? 'paid' : 'partially_paid';

        $stmt = $this->db->prepare(
            'UPDATE invoices
             SET amount_paid = :amount_paid, balance_due = :balance_due, status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function validationErrors(array $invoice): array
    {
        $errors = [];
        if (empty($invoice['customer_id'])) {
            $errors[] = 'Customer missing';
        }
        if (empty($invoice['service_request_id'])) {
            $errors[] = 'Service request missing';
        }
        if (empty($invoice['service_completion_report_id'])) {
            $errors[] = 'Completion report missing';
        }
        if (empty($invoice['vin']) && (int) $invoice['no_vehicle_serviced_flag'] !== 1) {
            $errors[] = 'VIN missing';
        }
        if ((float) $invoice['total'] <= 0) {
            $errors[] = 'Invoice total must be greater than zero';
        }
        return $errors;
    }

    private function estimateIdForWorkOrder(int $workOrderId): ?int
    {
        $stmt = $this->db->prepare('SELECT estimate_id FROM work_orders WHERE id = :id');
        $stmt->execute(['id' => $workOrderId]);
        $value = $stmt->fetchColumn();

        return $value ? (int) $value : null;
    }
}
