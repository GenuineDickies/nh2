<?php

namespace App\Models;

use App\Core\Repository;

final class Customer extends Repository
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
            $where = 'WHERE c.first_name LIKE :q
                       OR c.last_name LIKE :q
                       OR c.phone LIKE :q
                       OR COALESCE(c.email, \'\') LIKE :q';
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT c.*,
                    COUNT(DISTINCT v.id) AS vehicle_count,
                    COUNT(DISTINCT sr.id) AS service_request_count,
                    MAX(sr.created_at) AS last_service_at
                FROM customers c
                LEFT JOIN vehicles v ON v.customer_id = c.id
                LEFT JOIN service_requests sr ON sr.customer_id = c.id
                {$where}
                GROUP BY c.id
                ORDER BY c.created_at DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function vehicles(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicles WHERE customer_id = :id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }

    public function serviceRequests(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT sr.*, l.address_line_1, l.city, l.state
             FROM service_requests sr
             LEFT JOIN locations l ON l.id = sr.location_id
             WHERE sr.customer_id = :id
             ORDER BY sr.created_at DESC, sr.id DESC'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchAll();
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createIfMissing(string $firstName, string $lastName, string $phone): int
    {
        $existing = $this->findByPhone($phone);

        if ($existing) {
            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO customers (first_name, last_name, phone, created_at, updated_at)
             VALUES (:first_name, :last_name, :phone, :created_at, :updated_at)'
        );
        $now = $this->now();
        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateBasic(int $id, string $firstName, string $lastName, string $phone): void
    {
        $stmt = $this->db->prepare(
            'UPDATE customers
             SET first_name = :first_name, last_name = :last_name, phone = :phone, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }
}
