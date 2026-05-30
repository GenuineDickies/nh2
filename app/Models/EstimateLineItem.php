<?php

namespace App\Models;

use App\Core\Repository;

final class EstimateLineItem extends Repository
{
    public function forEstimate(int $estimateId): array
    {
        $stmt = $this->db->prepare(
            'SELECT eli.*, ci.sku
             FROM estimate_line_items eli
             LEFT JOIN catalog_items ci ON ci.id = eli.catalog_item_id
             WHERE eli.estimate_id = :estimate_id
             ORDER BY eli.sort_order ASC, eli.id ASC'
        );
        $stmt->execute(['estimate_id' => $estimateId]);

        return $stmt->fetchAll();
    }

    public function create(int $estimateId, array $data): int
    {
        $quantity = round((float) $data['quantity'], 2);
        $unitPrice = round((float) $data['unit_price'], 2);
        $lineSubtotal = round($quantity * $unitPrice, 2);

        $stmt = $this->db->prepare(
            'INSERT INTO estimate_line_items (
                estimate_id, catalog_item_id, line_type, description, quantity,
                unit_price, taxable, line_subtotal, sort_order
            ) VALUES (
                :estimate_id, :catalog_item_id, :line_type, :description, :quantity,
                :unit_price, :taxable, :line_subtotal, :sort_order
            )'
        );
        $stmt->execute([
            'estimate_id' => $estimateId,
            'catalog_item_id' => $data['catalog_item_id'] ?: null,
            'line_type' => $data['line_type'],
            'description' => $data['description'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'taxable' => !empty($data['taxable']) ? 1 : 0,
            'line_subtotal' => $lineSubtotal,
            'sort_order' => $this->nextSortOrder($estimateId),
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function nextSortOrder(int $estimateId): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM estimate_line_items WHERE estimate_id = :estimate_id');
        $stmt->execute(['estimate_id' => $estimateId]);

        return (int) $stmt->fetchColumn();
    }
}

