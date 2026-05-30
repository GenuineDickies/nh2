<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class CustomerApproval extends Repository
{
    public const METHODS = ['sms_link', 'email_link', 'phone_confirmed', 'onsite_signature'];

    public function forEstimate(int $estimateId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM customer_approvals WHERE estimate_id = :estimate_id ORDER BY approved_at DESC, id DESC');
        $stmt->execute(['estimate_id' => $estimateId]);

        return $stmt->fetchAll();
    }

    public function createForEstimate(array $estimate, string $customerName, string $method): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO customer_approvals (
                approval_number, service_request_id, estimate_id, approval_type,
                customer_name, approval_method, ip_address, user_agent,
                approved_at, created_at
            ) VALUES (
                :approval_number, :service_request_id, :estimate_id, :approval_type,
                :customer_name, :approval_method, :ip_address, :user_agent,
                :approved_at, :created_at
            )'
        );
        $stmt->execute([
            'approval_number' => NumberingService::next('EAP'),
            'service_request_id' => $estimate['service_request_id'],
            'estimate_id' => $estimate['id'],
            'approval_type' => 'estimate',
            'customer_name' => $customerName,
            'approval_method' => $method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'approved_at' => $now,
            'created_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }
}

