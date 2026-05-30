<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class ServiceRequest extends Repository
{
    public const STATUSES = ['pending', 'accepted', 'completed', 'cancelled', 'rejected'];

    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE sr.service_request_number LIKE :q
                       OR COALESCE(sr.requested_service, '') LIKE :q
                       OR sr.status LIKE :q
                       OR c.first_name LIKE :q
                       OR c.last_name LIKE :q
                       OR c.phone LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT sr.*, c.first_name, c.last_name, c.phone
                FROM service_requests sr
                JOIN customers c ON c.id = sr.customer_id
                {$where}
                ORDER BY sr.created_at DESC, sr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT sr.*, c.first_name, c.last_name, c.phone, c.email,
                    v.year, v.make, v.model, v.color, v.vin,
                    l.address_line_1, l.city, l.state, l.postal_code
             FROM service_requests sr
             JOIN customers c ON c.id = sr.customer_id
             LEFT JOIN vehicles v ON v.id = sr.vehicle_id
             JOIN locations l ON l.id = sr.location_id
             WHERE sr.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function timeline(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM audit_logs WHERE related_type = :type AND related_id = :id ORDER BY created_at DESC, id DESC');
        $stmt->execute([
            'type' => 'service_request',
            'id' => $id,
        ]);

        return $stmt->fetchAll();
    }

    public function createFromIntake(array $intake, int $customerId, ?int $vehicleId, int $locationId): int
    {
        return $this->create([
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'location_id' => $locationId,
            'intake_id' => $intake['id'],
            'requested_service' => $intake['service_requested'],
            'problem_description' => $intake['notes'],
            'status' => 'pending',
            'priority' => 'normal',
            'lead_source' => $intake['lead_source'],
        ]);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO service_requests (
                service_request_number, customer_id, vehicle_id, location_id, intake_id,
                requested_service, problem_description, status, priority, lead_source,
                created_at, updated_at
            ) VALUES (
                :service_request_number, :customer_id, :vehicle_id, :location_id, :intake_id,
                :requested_service, :problem_description, :status, :priority, :lead_source,
                :created_at, :updated_at
            )'
        );
        $now = $this->now();
        $stmt->execute([
            'service_request_number' => NumberingService::next('SER'),
            'customer_id' => $data['customer_id'],
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'location_id' => $data['location_id'],
            'intake_id' => $data['intake_id'] ?? null,
            'requested_service' => $data['requested_service'],
            'problem_description' => $data['problem_description'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'lead_source' => $data['lead_source'] ?? 'direct',
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

        $stmt = $this->db->prepare('UPDATE service_requests SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);

        return [
            'old_status' => $current['status'],
            'new_status' => $status,
        ];
    }

    public function updateCore(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE service_requests
             SET vehicle_id = :vehicle_id, requested_service = :requested_service,
                 problem_description = :problem_description, priority = :priority,
                 lead_source = :lead_source, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'requested_service' => $data['requested_service'],
            'problem_description' => $data['problem_description'] ?? null,
            'priority' => $data['priority'],
            'lead_source' => $data['lead_source'],
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function proofPacket(int $id): array
    {
        $serviceRequest = $this->findWithDetails($id);

        if (!$serviceRequest) {
            return [];
        }

        $packet = [
            'service_request' => $serviceRequest,
            'estimate' => $this->firstRow('SELECT * FROM estimates WHERE service_request_id = :id ORDER BY created_at DESC, id DESC LIMIT 1', $id),
            'approval' => $this->firstRow('SELECT * FROM customer_approvals WHERE service_request_id = :id ORDER BY created_at DESC, id DESC LIMIT 1', $id),
            'work_order' => $this->firstRow('SELECT * FROM work_orders WHERE service_request_id = :id ORDER BY created_at DESC, id DESC LIMIT 1', $id),
            'service_report' => $this->firstRow('SELECT * FROM service_completion_reports WHERE service_request_id = :id ORDER BY created_at DESC, id DESC LIMIT 1', $id),
            'invoice' => $this->firstRow('SELECT * FROM invoices WHERE service_request_id = :id ORDER BY created_at DESC, id DESC LIMIT 1', $id),
            'timeline' => $this->timeline($id),
        ];

        $invoiceId = $packet['invoice']['id'] ?? 0;
        $packet['payments'] = $invoiceId ? $this->rows('SELECT * FROM payments WHERE invoice_id = :id ORDER BY paid_at DESC, id DESC', (int) $invoiceId) : [];
        $packet['receipts'] = $invoiceId ? $this->rows('SELECT * FROM receipts WHERE invoice_id = :id ORDER BY created_at DESC, id DESC', (int) $invoiceId) : [];
        $packet['ledger_entries'] = $this->ledgerEntries($packet);
        $packet['attachments'] = (new FileAttachment())->forMany($this->attachmentRelatedPairs($packet));
        $packet['documents'] = (new GeneratedDocument())->forMany($this->documentRelatedPairs($packet));
        $packet['missing_items'] = $this->missingProofItems($packet);

        return $packet;
    }

    private function firstRow(string $sql, int $id): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function rows(string $sql, int $id): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }

    private function ledgerEntries(array $packet): array
    {
        $entries = [];
        if (!empty($packet['invoice']['id'])) {
            $entries = array_merge($entries, $this->ledgerRows('invoice', (int) $packet['invoice']['id']));
        }
        foreach ($packet['payments'] as $payment) {
            $entries = array_merge($entries, $this->ledgerRows('payment', (int) $payment['id']));
        }

        return $entries;
    }

    private function ledgerRows(string $sourceType, int $sourceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT le.*, COALESCE(SUM(lel.debit), 0) AS debit_total, COALESCE(SUM(lel.credit), 0) AS credit_total
             FROM ledger_entries le
             LEFT JOIN ledger_entry_lines lel ON lel.ledger_entry_id = le.id
             WHERE le.source_type = :source_type AND le.source_id = :source_id
             GROUP BY le.id
             ORDER BY le.id ASC'
        );
        $stmt->execute([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        return $stmt->fetchAll();
    }

    private function documentRelatedPairs(array $packet): array
    {
        $pairs = [[
            'related_type' => 'service_request',
            'related_id' => $packet['service_request']['id'] ?? null,
        ]];

        foreach ([
            'estimate' => 'estimate',
            'invoice' => 'invoice',
        ] as $packetKey => $relatedType) {
            if (!empty($packet[$packetKey]['id'])) {
                $pairs[] = [
                    'related_type' => $relatedType,
                    'related_id' => $packet[$packetKey]['id'],
                ];
            }
        }

        foreach ($packet['receipts'] as $receipt) {
            $pairs[] = [
                'related_type' => 'receipt',
                'related_id' => $receipt['id'],
            ];
        }

        return $pairs;
    }

    private function attachmentRelatedPairs(array $packet): array
    {
        $pairs = [[
            'related_type' => 'service_request',
            'related_id' => $packet['service_request']['id'] ?? null,
        ]];

        if (!empty($packet['work_order']['id'])) {
            $pairs[] = [
                'related_type' => 'work_order',
                'related_id' => $packet['work_order']['id'],
            ];
        }
        if (!empty($packet['service_report']['id'])) {
            $pairs[] = [
                'related_type' => 'service_report',
                'related_id' => $packet['service_report']['id'],
            ];
        }
        if (!empty($packet['invoice']['id'])) {
            $pairs[] = [
                'related_type' => 'invoice',
                'related_id' => $packet['invoice']['id'],
            ];
        }

        return $pairs;
    }

    private function missingProofItems(array $packet): array
    {
        $missing = [];
        foreach ([
            'estimate' => 'Estimate',
            'approval' => 'Customer approval',
            'work_order' => 'Work order',
            'service_report' => 'Service completion report',
            'invoice' => 'Invoice',
        ] as $key => $label) {
            if (empty($packet[$key])) {
                $missing[] = $label;
            }
        }

        if (empty($packet['payments'])) {
            $missing[] = 'Payment';
        }
        if (empty($packet['receipts'])) {
            $missing[] = 'Receipt';
        }
        if (empty($packet['ledger_entries'])) {
            $missing[] = 'Accounting entries';
        }
        if (empty($packet['attachments'])) {
            $missing[] = 'Photos or signatures';
        }
        if (empty($packet['service_request']['vin'])) {
            $missing[] = 'VIN';
        }

        return $missing;
    }
}
