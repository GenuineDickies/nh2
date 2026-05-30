<?php

namespace App\Models;

use App\Core\Repository;

final class DocumentExtractionLineItem extends Repository
{
    public const CATEGORIES = [
        'resold_part',
        'inventory_part',
        'consumable',
        'tool_or_equipment',
        'ppe_or_work_supplies',
        'fuel',
        'food_or_personal',
        'office_expense',
        'vehicle_expense',
        'other_expense',
        'unknown',
    ];

    public function forIntake(int $documentIntakeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM document_extraction_line_items
             WHERE document_intake_id = :id
             ORDER BY line_number ASC, id ASC'
        );
        $stmt->execute(['id' => $documentIntakeId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM document_extraction_line_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $documentIntakeId, array $data): int
    {
        $now = $this->now();
        $quantity = round((float) ($data['quantity'] ?? 1), 4);
        $unitPrice = round((float) ($data['unit_price'] ?? 0), 4);
        $subtotal = isset($data['line_subtotal']) && is_numeric($data['line_subtotal'])
            ? round((float) $data['line_subtotal'], 2)
            : round($quantity * $unitPrice, 2);

        $stmt = $this->db->prepare(
            'INSERT INTO document_extraction_line_items (
                document_intake_id, line_number, description, sku,
                manufacturer_part_number, vendor_part_number, quantity, unit_price,
                line_subtotal, taxable, category_guess, expense_type_guess,
                inventory_candidate, resale_candidate, warranty_candidate,
                confidence, created_at, updated_at
            ) VALUES (
                :document_intake_id, :line_number, :description, :sku,
                :mpn, :vpn, :quantity, :unit_price,
                :line_subtotal, :taxable, :category_guess, :expense_type_guess,
                :inv_cand, :resale_cand, :warr_cand,
                :conf, :created_at, :updated_at
            )'
        );

        $stmt->execute([
            'document_intake_id' => $documentIntakeId,
            'line_number' => (int) ($data['line_number'] ?? 0),
            'description' => (string) ($data['description'] ?? ''),
            'sku' => $data['sku'] ?: null,
            'mpn' => $data['manufacturer_part_number'] ?: null,
            'vpn' => $data['vendor_part_number'] ?: null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_subtotal' => $subtotal,
            'taxable' => !empty($data['taxable']) ? 1 : 0,
            'category_guess' => $data['category_guess'] ?: null,
            'expense_type_guess' => $data['expense_type_guess'] ?: null,
            'inv_cand' => !empty($data['inventory_candidate']) ? 1 : 0,
            'resale_cand' => !empty($data['resale_candidate']) ? 1 : 0,
            'warr_cand' => !empty($data['warranty_candidate']) ? 1 : 0,
            'conf' => isset($data['confidence']) ? (float) $data['confidence'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateReviewed(int $id, array $data): void
    {
        $quantity = round((float) ($data['quantity'] ?? 1), 4);
        $unitPrice = round((float) ($data['unit_price'] ?? 0), 4);
        $subtotal = round($quantity * $unitPrice, 2);

        $reviewedCategory = in_array($data['reviewed_category'] ?? '', self::CATEGORIES, true)
            ? $data['reviewed_category']
            : null;

        $stmt = $this->db->prepare(
            'UPDATE document_extraction_line_items
             SET description = :description,
                 sku = :sku,
                 manufacturer_part_number = :mpn,
                 vendor_part_number = :vpn,
                 quantity = :quantity,
                 unit_price = :unit_price,
                 line_subtotal = :line_subtotal,
                 reviewed_category = :reviewed_category,
                 updated_at = :u
             WHERE id = :id'
        );

        $stmt->execute([
            'description' => (string) ($data['description'] ?? ''),
            'sku' => $data['sku'] ?: null,
            'mpn' => $data['manufacturer_part_number'] ?: null,
            'vpn' => $data['vendor_part_number'] ?: null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_subtotal' => $subtotal,
            'reviewed_category' => $reviewedCategory,
            'u' => $this->now(),
            'id' => $id,
        ]);
    }

    public function deleteForIntake(int $documentIntakeId): void
    {
        $stmt = $this->db->prepare('DELETE FROM document_extraction_line_items WHERE document_intake_id = :id');
        $stmt->execute(['id' => $documentIntakeId]);
    }
}
