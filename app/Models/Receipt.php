<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class Receipt extends Repository
{
    public function createForPayment(array $payment): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO receipts (
                receipt_number, payment_id, invoice_id, customer_id, created_at
            ) VALUES (
                :receipt_number, :payment_id, :invoice_id, :customer_id, :created_at
            )'
        );
        $stmt->execute([
            'receipt_number' => NumberingService::next('RCT'),
            'payment_id' => $payment['id'],
            'invoice_id' => $payment['invoice_id'],
            'customer_id' => $payment['customer_id'],
            'created_at' => $this->now(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, p.payment_number, p.payment_method, p.amount, p.transaction_reference, p.paid_at,
                    i.invoice_number, i.total, i.amount_paid, i.balance_due,
                    c.first_name, c.last_name, c.phone
             FROM receipts r
             JOIN payments p ON p.id = r.payment_id
             JOIN invoices i ON i.id = r.invoice_id
             JOIN customers c ON c.id = r.customer_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
