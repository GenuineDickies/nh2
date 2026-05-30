<?php

namespace App\Services;

use App\Core\Database;
use App\Models\DocumentMatch;
use PDO;

/**
 * Suggests possible matches between an AI extraction and existing records.
 *
 * Returns suggestions only; nothing is created in the live tables. The user
 * accepts a suggestion during review.
 */
final class DocumentMatchingService
{
    public function __construct(private ?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Build suggestions from a normalized extraction and persist them as
     * document_matches rows. Old matches for the intake are cleared first
     * so reprocessing yields a fresh slate.
     */
    public function buildAndStoreMatches(int $documentIntakeId, array $normalized): array
    {
        $matchModel = new DocumentMatch();
        $matchModel->deleteForIntake($documentIntakeId);

        $matches = array_merge(
            $this->matchVendors($normalized),
            $this->matchCustomers($normalized),
            $this->matchVehicles($normalized),
            $this->matchInvoices($normalized),
            $this->matchServiceRequests($normalized)
        );

        foreach ($matches as $match) {
            $matchModel->create($documentIntakeId, $match);
        }

        return $matches;
    }

    public function matchVendors(array $normalized): array
    {
        $name = $normalized['source_party']['name'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            return [];
        }
        $name = trim($name);

        $stmt = $this->db->prepare(
            "SELECT id, name, phone, email, address
             FROM vendors
             WHERE LOWER(name) = LOWER(:exact)
                OR LOWER(name) LIKE LOWER(:like)
             ORDER BY (LOWER(name) = LOWER(:exact2)) DESC, name ASC
             LIMIT 5"
        );
        $stmt->execute([
            'exact' => $name,
            'exact2' => $name,
            'like' => '%' . $name . '%',
        ]);

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $confidence = $this->nameSimilarity($name, (string) $row['name']);
            $matches[] = [
                'match_type' => 'vendor',
                'matched_table' => 'vendors',
                'matched_record_id' => (int) $row['id'],
                'match_confidence' => $confidence,
                'match_reason' => sprintf(
                    'Vendor name match: AI "%s" vs existing "%s"',
                    $name,
                    $row['name']
                ),
            ];
        }

        return $matches;
    }

    public function matchCustomers(array $normalized): array
    {
        $name = $normalized['target_party']['name'] ?? null;
        $phone = $normalized['target_party']['phone'] ?? null;
        $email = $normalized['target_party']['email'] ?? null;

        $clauses = [];
        $params = [];

        if (is_string($phone) && trim($phone) !== '') {
            $digits = preg_replace('/\D+/', '', $phone);
            if ($digits !== null && $digits !== '') {
                $clauses[] = "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), '-', ''), ' ', ''), '(', ''), ')', '') LIKE :phone";
                $params['phone'] = '%' . $digits;
            }
        }

        if (is_string($email) && trim($email) !== '') {
            $clauses[] = 'LOWER(email) = LOWER(:email)';
            $params['email'] = trim($email);
        }

        if (is_string($name) && trim($name) !== '') {
            $clauses[] = "LOWER(first_name || ' ' || last_name) LIKE LOWER(:name)";
            $params['name'] = '%' . trim($name) . '%';
        }

        if (!$clauses) {
            return [];
        }

        // SQLite uses ||, MySQL uses CONCAT — rewrite for the active driver.
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $whereSql = implode(' OR ', $clauses);
        if ($driver === 'mysql') {
            $whereSql = str_replace(
                "first_name || ' ' || last_name",
                "CONCAT(first_name, ' ', last_name)",
                $whereSql
            );
        }

        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, phone, email
             FROM customers
             WHERE {$whereSql}
             ORDER BY id DESC
             LIMIT 5"
        );
        $stmt->execute($params);

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $reasons = [];
            $confidence = 0.5;
            if (isset($params['email']) && strcasecmp((string) $row['email'], (string) $params['email']) === 0) {
                $reasons[] = 'email match';
                $confidence = max($confidence, 0.95);
            }
            if (isset($params['phone'])) {
                $reasons[] = 'phone match';
                $confidence = max($confidence, 0.9);
            }
            if (is_string($name) && trim($name) !== '') {
                $reasons[] = 'name similarity';
                $confidence = max($confidence, $this->nameSimilarity(
                    trim($name),
                    trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))
                ));
            }

            $matches[] = [
                'match_type' => 'customer',
                'matched_table' => 'customers',
                'matched_record_id' => (int) $row['id'],
                'match_confidence' => $confidence,
                'match_reason' => 'Customer ' . implode(', ', $reasons),
            ];
        }

        return $matches;
    }

    public function matchVehicles(array $normalized): array
    {
        $vehicle = $normalized['vehicle'] ?? [];
        if (!is_array($vehicle)) {
            return [];
        }

        $vin = isset($vehicle['vin']) ? trim((string) $vehicle['vin']) : '';
        $plate = isset($vehicle['plate']) ? trim((string) $vehicle['plate']) : '';

        $matches = [];

        if ($vin !== '') {
            $stmt = $this->db->prepare('SELECT id, vin, plate_number, year, make, model FROM vehicles WHERE UPPER(vin) = UPPER(:vin) LIMIT 5');
            $stmt->execute(['vin' => $vin]);
            foreach ($stmt->fetchAll() as $row) {
                $matches[] = [
                    'match_type' => 'vehicle',
                    'matched_table' => 'vehicles',
                    'matched_record_id' => (int) $row['id'],
                    'match_confidence' => 0.98,
                    'match_reason' => 'VIN match: ' . $vin,
                ];
            }
        }

        if ($plate !== '' && !$matches) {
            $stmt = $this->db->prepare('SELECT id, vin, plate_number, year, make, model FROM vehicles WHERE UPPER(plate_number) = UPPER(:plate) LIMIT 5');
            $stmt->execute(['plate' => $plate]);
            foreach ($stmt->fetchAll() as $row) {
                $matches[] = [
                    'match_type' => 'vehicle',
                    'matched_table' => 'vehicles',
                    'matched_record_id' => (int) $row['id'],
                    'match_confidence' => 0.85,
                    'match_reason' => 'Plate match: ' . $plate,
                ];
            }
        }

        return $matches;
    }

    public function matchInvoices(array $normalized): array
    {
        $hints = $normalized['matching_hints'] ?? [];
        $number = is_array($hints) && isset($hints['possible_invoice_number'])
            ? trim((string) $hints['possible_invoice_number'])
            : '';
        if ($number === '') {
            $number = trim((string) ($normalized['document_number'] ?? ''));
        }
        if ($number === '') {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, invoice_number, total
             FROM invoices
             WHERE invoice_number LIKE :n
                OR invoice_number = :exact
             ORDER BY id DESC
             LIMIT 5'
        );
        $stmt->execute([
            'n' => '%' . $number . '%',
            'exact' => $number,
        ]);

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $exact = strcasecmp((string) $row['invoice_number'], $number) === 0;
            $matches[] = [
                'match_type' => 'invoice',
                'matched_table' => 'invoices',
                'matched_record_id' => (int) $row['id'],
                'match_confidence' => $exact ? 0.95 : 0.7,
                'match_reason' => 'Invoice number ' . ($exact ? 'exact match' : 'partial match')
                    . ': ' . $number,
            ];
        }

        return $matches;
    }

    public function matchServiceRequests(array $normalized): array
    {
        $hints = $normalized['matching_hints'] ?? [];
        $number = is_array($hints) && isset($hints['possible_service_request_number'])
            ? trim((string) $hints['possible_service_request_number'])
            : '';
        if ($number === '') {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, service_request_number
             FROM service_requests
             WHERE service_request_number LIKE :n
             ORDER BY id DESC
             LIMIT 5'
        );
        $stmt->execute(['n' => '%' . $number . '%']);

        $matches = [];
        foreach ($stmt->fetchAll() as $row) {
            $matches[] = [
                'match_type' => 'service_request',
                'matched_table' => 'service_requests',
                'matched_record_id' => (int) $row['id'],
                'match_confidence' => 0.85,
                'match_reason' => 'Service request number match: ' . $number,
            ];
        }

        return $matches;
    }

    private function nameSimilarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 0.99;
        }
        // similar_text writes percent into the third arg.
        $percent = 0.0;
        similar_text($a, $b, $percent);
        return max(0.0, min(0.99, $percent / 100));
    }
}
