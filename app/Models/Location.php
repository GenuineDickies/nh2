<?php

namespace App\Models;

use App\Core\Repository;

final class Location extends Repository
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO locations (address_line_1, city, state, postal_code, location_source, notes, created_at, updated_at)
             VALUES (:address_line_1, :city, :state, :postal_code, :location_source, :notes, :created_at, :updated_at)'
        );
        $now = $this->now();
        $stmt->execute([
            'address_line_1' => $data['location_address'] ?: null,
            'city' => $data['location_city'] ?: null,
            'state' => $data['location_state'] ?: null,
            'postal_code' => $data['location_postal_code'] ?: null,
            'location_source' => 'manual',
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createFromIntake(array $intake): int
    {
        return $this->create($intake);
    }

    public function updateBasic(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE locations
             SET address_line_1 = :address_line_1, city = :city, state = :state,
                 postal_code = :postal_code, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'address_line_1' => $data['location_address'] ?: null,
            'city' => $data['location_city'] ?: null,
            'state' => $data['location_state'] ?: null,
            'postal_code' => $data['location_postal_code'] ?: null,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }
}
