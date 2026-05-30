<?php

namespace App\Models;

use App\Core\Repository;

final class Vendor extends Repository
{
    public const STATUSES = ['active', 'inactive'];

    public function all(): array
    {
        return $this->search('');
    }

    public function search(string $q): array
    {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE name LIKE :q
                       OR COALESCE(phone, '') LIKE :q
                       OR COALESCE(email, '') LIKE :q
                       OR COALESCE(address, '') LIKE :q
                       OR status LIKE :q";
            $params['q'] = '%' . $q . '%';
        }

        $sql = "SELECT * FROM vendors {$where} ORDER BY status DESC, name ASC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vendors WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO vendors (name, phone, email, website, address, notes, status, created_at, updated_at)
             VALUES (:name, :phone, :email, :website, :address, :notes, :status, :created_at, :updated_at)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => in_array($data['status'] ?? 'active', self::STATUSES, true) ? $data['status'] : 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE vendors
             SET name = :name, phone = :phone, email = :email, website = :website,
                 address = :address, notes = :notes, status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'notes' => $data['notes'] ?: null,
            'status' => in_array($data['status'] ?? 'active', self::STATUSES, true) ? $data['status'] : 'active',
            'updated_at' => $this->now(),
        ]);
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'Required';
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Use a valid email address';
        }

        $website = trim((string) ($data['website'] ?? ''));
        if ($website !== '' && !preg_match('#^https?://#i', $website)) {
            $errors['website'] = 'Start the URL with http:// or https://';
        }

        $status = $data['status'] ?? 'active';
        if (!in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Choose a valid status';
        }

        return $errors;
    }
}
