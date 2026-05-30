<?php

namespace App\Models;

use App\Core\Repository;

final class CatalogItem extends Repository
{
    public const ITEM_TYPES = ['service', 'part', 'material', 'fee', 'labor'];
    public const PRICE_TYPES = ['flat_rate', 'starting_at', 'estimate_required'];
    public const STATUSES = ['active', 'inactive'];

    public function all(?array $types = null): array
    {
        if ($types) {
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $stmt = $this->db->prepare("SELECT * FROM catalog_items WHERE item_type IN ({$placeholders}) ORDER BY status ASC, name ASC");
            $stmt->execute($types);

            return $stmt->fetchAll();
        }

        return $this->db->query('SELECT * FROM catalog_items ORDER BY status ASC, name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM catalog_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO catalog_items (
                sku, item_type, name, category, price, price_type, taxable, status,
                short_description, long_description, warranty_eligible, created_at, updated_at
            ) VALUES (
                :sku, :item_type, :name, :category, :price, :price_type, :taxable, :status,
                :short_description, :long_description, :warranty_eligible, :created_at, :updated_at
            )'
        );
        $now = $this->now();
        $stmt->execute($this->payload($data, $now));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $payload = $this->payload($data, $this->now());
        $payload['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE catalog_items
             SET sku = :sku, item_type = :item_type, name = :name, category = :category,
                 price = :price, price_type = :price_type, taxable = :taxable,
                 status = :status, short_description = :short_description,
                 long_description = :long_description, warranty_eligible = :warranty_eligible,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        unset($payload['created_at']);
        $stmt->execute($payload);
    }

    private function payload(array $data, string $now): array
    {
        return [
            'sku' => $data['sku'],
            'item_type' => $data['item_type'],
            'name' => $data['name'],
            'category' => $data['category'],
            'price' => $data['price'],
            'price_type' => $data['price_type'],
            'taxable' => !empty($data['taxable']) ? 1 : 0,
            'status' => $data['status'],
            'short_description' => $data['short_description'] ?: null,
            'long_description' => $data['long_description'] ?: null,
            'warranty_eligible' => !empty($data['warranty_eligible']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

