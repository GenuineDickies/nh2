<?php

namespace App\Models;

use App\Core\Repository;

final class VendorDocumentLineItem extends Repository
{
    public const CATEGORIES = [
        'resold_part',
        'inventory_part',
        'consumable',
        'tool_equipment',
        'ppe',
        'fuel',
        'meal_personal',
        'office',
        'other',
    ];

    public function forDocument(int $vendorDocumentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT vdli.*, sr.service_request_number, inv.invoice_number
             FROM vendor_document_line_items vdli
             LEFT JOIN service_requests sr ON sr.id = vdli.service_request_id
             LEFT JOIN invoices inv ON inv.id = vdli.invoice_id
             WHERE vdli.vendor_document_id = :id
             ORDER BY vdli.sort_order ASC, vdli.id ASC'
        );
        $stmt->execute(['id' => $vendorDocumentId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vendor_document_line_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $vendorDocumentId, array $data): int
    {
        $now = $this->now();
        $quantity = round((float) ($data['quantity'] ?? 1), 2);
        $unitCost = round((float) ($data['unit_cost'] ?? 0), 2);
        $lineTotal = round($quantity * $unitCost, 2);

        $stmt = $this->db->prepare(
            'INSERT INTO vendor_document_line_items (
                vendor_document_id, service_request_id, invoice_id, item_name, part_number,
                category, quantity, unit_cost, line_total, reviewed_flag, sort_order, created_at, updated_at
            ) VALUES (
                :vendor_document_id, :service_request_id, :invoice_id, :item_name, :part_number,
                :category, :quantity, :unit_cost, :line_total, :reviewed_flag, :sort_order, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            'vendor_document_id' => $vendorDocumentId,
            'service_request_id' => !empty($data['service_request_id']) ? (int) $data['service_request_id'] : null,
            'invoice_id' => !empty($data['invoice_id']) ? (int) $data['invoice_id'] : null,
            'item_name' => $data['item_name'],
            'part_number' => $data['part_number'] ?: null,
            'category' => in_array($data['category'] ?? 'other', self::CATEGORIES, true) ? $data['category'] : 'other',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => $lineTotal,
            'reviewed_flag' => !empty($data['reviewed_flag']) ? 1 : 0,
            'sort_order' => $this->nextSortOrder($vendorDocumentId),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $quantity = round((float) ($data['quantity'] ?? 1), 2);
        $unitCost = round((float) ($data['unit_cost'] ?? 0), 2);
        $lineTotal = round($quantity * $unitCost, 2);

        $stmt = $this->db->prepare(
            'UPDATE vendor_document_line_items
             SET service_request_id = :service_request_id,
                 invoice_id = :invoice_id,
                 item_name = :item_name,
                 part_number = :part_number,
                 category = :category,
                 quantity = :quantity,
                 unit_cost = :unit_cost,
                 line_total = :line_total,
                 reviewed_flag = :reviewed_flag,
                 updated_at = :updated_at
             WHERE id = :id'
        );

        $stmt->execute([
            'service_request_id' => !empty($data['service_request_id']) ? (int) $data['service_request_id'] : null,
            'invoice_id' => !empty($data['invoice_id']) ? (int) $data['invoice_id'] : null,
            'item_name' => $data['item_name'],
            'part_number' => $data['part_number'] ?: null,
            'category' => in_array($data['category'] ?? 'other', self::CATEGORIES, true) ? $data['category'] : 'other',
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'line_total' => $lineTotal,
            'reviewed_flag' => !empty($data['reviewed_flag']) ? 1 : 0,
            'updated_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM vendor_document_line_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['item_name'] ?? '')) === '') {
            $errors['item_name'] = 'Required';
        }

        $quantity = $data['quantity'] ?? '';
        if (!is_numeric($quantity) || (float) $quantity <= 0) {
            $errors['quantity'] = 'Use a positive quantity';
        }

        $unitCost = $data['unit_cost'] ?? '';
        if (!is_numeric($unitCost) || (float) $unitCost < 0) {
            $errors['unit_cost'] = 'Use a non-negative unit cost';
        }

        if (!in_array($data['category'] ?? 'other', self::CATEGORIES, true)) {
            $errors['category'] = 'Choose a category';
        }

        return $errors;
    }

    private function nextSortOrder(int $vendorDocumentId): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM vendor_document_line_items WHERE vendor_document_id = :id');
        $stmt->execute(['id' => $vendorDocumentId]);

        return (int) $stmt->fetchColumn();
    }
}
