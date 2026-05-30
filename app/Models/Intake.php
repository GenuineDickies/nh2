<?php

namespace App\Models;

use App\Core\Repository;
use App\Services\NumberingService;

final class Intake extends Repository
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM intakes ORDER BY created_at DESC, id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM intakes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO intakes (
                intake_number, first_name, last_name, phone, service_requested,
                location_address, location_city, location_state, location_postal_code,
                vehicle_year, vehicle_make, vehicle_model, vehicle_color,
                lead_source, notes, status, created_at, updated_at
            ) VALUES (
                :intake_number, :first_name, :last_name, :phone, :service_requested,
                :location_address, :location_city, :location_state, :location_postal_code,
                :vehicle_year, :vehicle_make, :vehicle_model, :vehicle_color,
                :lead_source, :notes, :status, :created_at, :updated_at
            )'
        );
        $now = $this->now();
        $stmt->execute([
            'intake_number' => NumberingService::next('INT'),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'service_requested' => $data['service_requested'],
            'location_address' => $data['location_address'] ?? null,
            'location_city' => $data['location_city'] ?? null,
            'location_state' => $data['location_state'] ?? null,
            'location_postal_code' => $data['location_postal_code'] ?? null,
            'vehicle_year' => $data['vehicle_year'] ?? null,
            'vehicle_make' => $data['vehicle_make'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'vehicle_color' => $data['vehicle_color'] ?? null,
            'lead_source' => $data['lead_source'],
            'notes' => $data['notes'] ?? null,
            'status' => 'saved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE intakes
             SET first_name = :first_name, last_name = :last_name, phone = :phone,
                 service_requested = :service_requested, location_address = :location_address,
                 location_city = :location_city, location_state = :location_state,
                 location_postal_code = :location_postal_code, vehicle_year = :vehicle_year,
                 vehicle_make = :vehicle_make, vehicle_model = :vehicle_model,
                 vehicle_color = :vehicle_color, lead_source = :lead_source,
                 notes = :notes, updated_at = :updated_at
             WHERE id = :id AND status != :converted_status'
        );
        $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'service_requested' => $data['service_requested'],
            'location_address' => $data['location_address'] ?? null,
            'location_city' => $data['location_city'] ?? null,
            'location_state' => $data['location_state'] ?? null,
            'location_postal_code' => $data['location_postal_code'] ?? null,
            'vehicle_year' => $data['vehicle_year'] ?? null,
            'vehicle_make' => $data['vehicle_make'] ?? null,
            'vehicle_model' => $data['vehicle_model'] ?? null,
            'vehicle_color' => $data['vehicle_color'] ?? null,
            'lead_source' => $data['lead_source'],
            'notes' => $data['notes'] ?? null,
            'updated_at' => $this->now(),
            'id' => $id,
            'converted_status' => 'converted',
        ]);
    }

    public function markConverted(int $id, int $serviceRequestId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE intakes
             SET status = :status, converted_service_request_id = :service_request_id, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'converted',
            'service_request_id' => $serviceRequestId,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }
}
