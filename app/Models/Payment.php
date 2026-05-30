<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class Payment extends Repository
{
    public const METHODS = ['cash', 'card', 'check', 'square', 'stripe', 'zelle', 'other'];

    public function all(): array
    {
        $sql = 'SELECT p.*, i.invoice_number, c.first_name, c.last_name, r.id AS receipt_id, r.receipt_number
                FROM payments p
                JOIN invoices i ON i.id = p.invoice_id
                JOIN customers c ON c.id = p.customer_id
                LEFT JOIN receipts r ON r.payment_id = p.id
                ORDER BY p.paid_at DESC, p.id DESC';

        return $this->db->query($sql)->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, i.invoice_number, i.total, i.amount_paid, i.balance_due,
                    c.first_name, c.last_name, c.phone,
                    r.id AS receipt_id, r.receipt_number
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             JOIN customers c ON c.id = p.customer_id
             LEFT JOIN receipts r ON r.payment_id = p.id
             WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function validationErrors(array $invoice, string $method, float $amount): array
    {
        $errors = [];
        if (!in_array($method, self::METHODS, true)) {
            $errors[] = 'Payment method is invalid';
        }
        if ($amount <= 0) {
            $errors[] = 'Payment amount must be greater than zero';
        }
        if ($amount > round((float) $invoice['balance_due'], 2)) {
            $errors[] = 'Payment cannot exceed the invoice balance';
        }
        if (!in_array($invoice['status'], ['sent', 'partially_paid'], true)) {
            $errors[] = 'Invoice must be sent before payment is recorded';
        }

        return $errors;
    }

    public function record(array $invoice, string $method, float $amount, ?string $reference): array
    {
        $now = $this->now();
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO payments (
                    payment_number, invoice_id, customer_id, payment_method, amount,
                    payment_status, transaction_reference, paid_at, created_at, updated_at
                ) VALUES (
                    :payment_number, :invoice_id, :customer_id, :payment_method, :amount,
                    :payment_status, :transaction_reference, :paid_at, :created_at, :updated_at
                )'
            );
            $stmt->execute([
                'payment_number' => NumberingService::next('PAY'),
                'invoice_id' => $invoice['id'],
                'customer_id' => $invoice['customer_id'],
                'payment_method' => $method,
                'amount' => $amount,
                'payment_status' => 'completed',
                'transaction_reference' => $reference ?: null,
                'paid_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $paymentId = (int) $this->db->lastInsertId();
            (new Invoice())->applyPayment((int) $invoice['id'], $amount);
            $payment = $this->findWithDetails($paymentId);
            $receiptId = (new Receipt())->createForPayment($payment ?: [
                'id' => $paymentId,
                'invoice_id' => $invoice['id'],
                'customer_id' => $invoice['customer_id'],
            ]);

            $this->db->commit();

            return [
                'payment_id' => $paymentId,
                'receipt_id' => $receiptId,
            ];
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
