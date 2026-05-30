<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class DocumentIntake extends Repository
{
    public const SOURCE_TYPES = ['customer', 'vendor', 'internal', 'unknown'];

    public const STATUSES = [
        'uploaded',
        'processing',
        'needs_review',
        'approved',
        'rejected',
        'failed',
        'posted',
    ];

    public const DOCUMENT_TYPES = [
        'customer_invoice',
        'vendor_receipt',
        'vendor_bill',
        'purchase_order',
        'payment_receipt',
        'estimate',
        'work_order',
        'service_report',
        'warranty_document',
        'refund_receipt',
        'credit_memo',
        'core_return_document',
        'customer_authorization',
        'unknown',
    ];

    public function all(?string $status = null): array
    {
        $sql = 'SELECT di.*, v.name AS related_vendor_name,
                       c.first_name AS related_customer_first_name,
                       c.last_name AS related_customer_last_name
                FROM document_intakes di
                LEFT JOIN vendors v ON v.id = di.related_vendor_id
                LEFT JOIN customers c ON c.id = di.related_customer_id';

        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE di.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY di.created_at DESC, di.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT di.*, v.name AS related_vendor_name,
                    c.first_name AS related_customer_first_name,
                    c.last_name AS related_customer_last_name
             FROM document_intakes di
             LEFT JOIN vendors v ON v.id = di.related_vendor_id
             LEFT JOIN customers c ON c.id = di.related_customer_id
             WHERE di.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByFileHash(string $hash): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM document_intakes WHERE file_hash = :h LIMIT 1');
        $stmt->execute(['h' => $hash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $now = $this->now();
        $sourceType = in_array($data['source_type'] ?? 'unknown', self::SOURCE_TYPES, true)
            ? $data['source_type']
            : 'unknown';

        $stmt = $this->db->prepare(
            'INSERT INTO document_intakes (
                document_number, original_filename, stored_filename, file_path,
                file_mime_type, file_size, file_hash, source_type, status,
                related_customer_id, related_vehicle_id, related_vendor_id,
                related_service_request_id, related_invoice_id, related_purchase_order_id,
                uploaded_by_user_id, uploaded_at, notes, created_at, updated_at
            ) VALUES (
                :document_number, :original_filename, :stored_filename, :file_path,
                :file_mime_type, :file_size, :file_hash, :source_type, :status,
                :related_customer_id, :related_vehicle_id, :related_vendor_id,
                :related_service_request_id, :related_invoice_id, :related_purchase_order_id,
                :uploaded_by_user_id, :uploaded_at, :notes, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            'document_number' => NumberingService::next('DOC'),
            'original_filename' => $data['original_filename'],
            'stored_filename' => $data['stored_filename'],
            'file_path' => $data['file_path'],
            'file_mime_type' => $data['file_mime_type'],
            'file_size' => (int) $data['file_size'],
            'file_hash' => $data['file_hash'] ?? null,
            'source_type' => $sourceType,
            'status' => 'uploaded',
            'related_customer_id' => $this->nullableInt($data['related_customer_id'] ?? null),
            'related_vehicle_id' => $this->nullableInt($data['related_vehicle_id'] ?? null),
            'related_vendor_id' => $this->nullableInt($data['related_vendor_id'] ?? null),
            'related_service_request_id' => $this->nullableInt($data['related_service_request_id'] ?? null),
            'related_invoice_id' => $this->nullableInt($data['related_invoice_id'] ?? null),
            'related_purchase_order_id' => $this->nullableInt($data['related_purchase_order_id'] ?? null),
            'uploaded_by_user_id' => $this->nullableInt($data['uploaded_by_user_id'] ?? null),
            'uploaded_at' => $now,
            'notes' => $data['notes'] ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $errorMessage = null): ?array
    {
        if (!in_array($status, self::STATUSES, true)) {
            return null;
        }

        $current = $this->find($id);
        if (!$current) {
            return null;
        }

        $now = $this->now();
        $sets = ['status = :status', 'updated_at = :updated_at'];
        $params = [
            'status' => $status,
            'updated_at' => $now,
            'id' => $id,
        ];

        $timestampField = match ($status) {
            'processing' => 'processed_at',
            'needs_review' => 'reviewed_at',
            'approved' => 'approved_at',
            'rejected' => 'rejected_at',
            'posted' => 'posted_at',
            default => null,
        };

        if ($timestampField && empty($current[$timestampField])) {
            $sets[] = "{$timestampField} = :ts";
            $params['ts'] = $now;
        }

        if ($status === 'failed' || $errorMessage !== null) {
            $sets[] = 'error_message = :error_message';
            $params['error_message'] = $errorMessage;
        } elseif (in_array($status, ['processing', 'needs_review', 'approved', 'posted'], true)) {
            // A successful transition clears any prior error so the UI doesn't
            // keep showing a stale failure alert.
            $sets[] = 'error_message = NULL';
        }

        $sql = 'UPDATE document_intakes SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'old_status' => $current['status'],
            'new_status' => $status,
        ];
    }

    public function applyStaging(int $id, array $staging): void
    {
        $warningsJson = !empty($staging['warnings'])
            ? json_encode($staging['warnings'], JSON_UNESCAPED_SLASHES)
            : null;

        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET staged_file_path = :sp,
                 staged_mime_type = :sm,
                 staged_file_size = :ss,
                 staging_driver = :sd,
                 staging_warnings = :sw,
                 updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'sp' => $staging['staged_path'] ?? null,
            'sm' => $staging['staged_mime'] ?? null,
            'ss' => $staging['staged_size'] ?? null,
            'sd' => $staging['driver'] ?? null,
            'sw' => $warningsJson,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function applyClassification(int $id, ?string $documentType, ?float $confidence): void
    {
        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET detected_document_type = :t,
                 document_type_confidence = :c,
                 updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            't' => $documentType,
            'c' => $confidence,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function applyReviewedRelations(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET related_customer_id = :cust,
                 related_vehicle_id = :veh,
                 related_vendor_id = :vend,
                 related_service_request_id = :sr,
                 related_invoice_id = :inv,
                 detected_document_type = :dt,
                 notes = :notes,
                 updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'cust' => $this->nullableInt($data['related_customer_id'] ?? null),
            'veh' => $this->nullableInt($data['related_vehicle_id'] ?? null),
            'vend' => $this->nullableInt($data['related_vendor_id'] ?? null),
            'sr' => $this->nullableInt($data['related_service_request_id'] ?? null),
            'inv' => $this->nullableInt($data['related_invoice_id'] ?? null),
            'dt' => in_array($data['detected_document_type'] ?? '', self::DOCUMENT_TYPES, true)
                ? $data['detected_document_type']
                : null,
            'notes' => $data['notes'] ?? null,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function setDuplicateOf(int $id, ?int $otherIntakeId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET duplicate_of_intake_id = :other, updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'other' => $otherIntakeId,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function setDuplicateOverride(int $id, bool $override): void
    {
        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET duplicate_override = :ov, updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'ov' => $override ? 1 : 0,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function findDuplicateCandidates(int $intakeId): array
    {
        // Look for OTHER intakes with the same file hash. Prefer ones already
        // posted (those are the real "we already booked this" cases). Self-
        // exclude so an intake never duplicates itself.
        $current = $this->find($intakeId);
        if (!$current || empty($current['file_hash'])) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT id, document_number, status, posted_at, posted_vendor_document_id
             FROM document_intakes
             WHERE file_hash = :hash AND id <> :self
             ORDER BY (status = \'posted\') DESC, created_at DESC
             LIMIT 5'
        );
        $stmt->execute([
            'hash' => $current['file_hash'],
            'self' => $intakeId,
        ]);
        return $stmt->fetchAll();
    }

    public function setPostedVendorDocument(int $id, int $vendorDocumentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE document_intakes
             SET posted_vendor_document_id = :v, updated_at = :u
             WHERE id = :id'
        );
        $stmt->execute([
            'v' => $vendorDocumentId,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function statusCounts(): array
    {
        $rows = $this->db->query(
            'SELECT status, COUNT(*) AS c FROM document_intakes GROUP BY status'
        )->fetchAll();

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }

        return $counts;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }
}
