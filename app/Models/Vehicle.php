<?php

namespace App\Models;

use App\Core\Repository;

final class Vehicle extends Repository
{
    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE COALESCE(v.vin, '') LIKE :q
                       OR COALESCE(v.plate_number, '') LIKE :q
                       OR COALESCE(v.make, '') LIKE :q
                       OR COALESCE(v.model, '') LIKE :q
                       OR COALESCE(v.year, '') LIKE :q
                       OR COALESCE(v.color, '') LIKE :q
                       OR COALESCE(c.first_name, '') LIKE :q
                       OR COALESCE(c.last_name, '') LIKE :q
                       OR COALESCE(c.phone, '') LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT v.*, c.first_name, c.last_name, c.phone,
                    COUNT(sr.id) AS service_request_count,
                    MAX(sr.created_at) AS last_service_at
                FROM vehicles v
                LEFT JOIN customers c ON c.id = v.customer_id
                LEFT JOIN service_requests sr ON sr.vehicle_id = v.id
                {$where}
                GROUP BY v.id
                ORDER BY v.created_at DESC, v.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*, c.first_name, c.last_name, c.phone, c.email
             FROM vehicles v
             LEFT JOIN customers c ON c.id = v.customer_id
             WHERE v.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function serviceRequests(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT sr.*, l.address_line_1, l.city, l.state
             FROM service_requests sr
             LEFT JOIN locations l ON l.id = sr.location_id
             WHERE sr.vehicle_id = :id
             ORDER BY sr.created_at DESC, sr.id DESC'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }

    public function createBasic(int $customerId, array $data): ?int
    {
        $hasVehicle = trim(($data['vehicle_year'] ?? '') . ($data['vehicle_make'] ?? '') . ($data['vehicle_model'] ?? '') . ($data['vehicle_color'] ?? '')) !== '';

        if (!$hasVehicle) {
            return null;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO vehicles (customer_id, year, make, model, color, no_plate_flag, created_at, updated_at)
             VALUES (:customer_id, :year, :make, :model, :color, :no_plate_flag, :created_at, :updated_at)'
        );
        $now = $this->now();
        $stmt->execute([
            'customer_id' => $customerId,
            'year' => $data['vehicle_year'] ?: null,
            'make' => $data['vehicle_make'] ?: null,
            'model' => $data['vehicle_model'] ?: null,
            'color' => $data['vehicle_color'] ?: null,
            'no_plate_flag' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createFromIntake(int $customerId, array $intake): ?int
    {
        return $this->createBasic($customerId, $intake);
    }

    public function updateBasic(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vehicles
             SET year = :year, make = :make, model = :model, color = :color, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'year' => $data['vehicle_year'] ?: null,
            'make' => $data['vehicle_make'] ?: null,
            'model' => $data['vehicle_model'] ?: null,
            'color' => $data['vehicle_color'] ?: null,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }
}
