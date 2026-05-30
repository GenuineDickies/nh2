<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CatalogItem;

final class CatalogController extends Controller
{
    public function services(): void
    {
        $this->view('layouts/app', [
            'title' => 'Service Catalog',
            'active' => 'catalog',
            'content' => 'catalog/index',
            'items' => (new CatalogItem())->all(['service', 'labor', 'fee']),
            'mode' => 'services',
        ]);
    }

    public function items(): void
    {
        $this->view('layouts/app', [
            'title' => 'Parts & Materials',
            'active' => 'catalog',
            'content' => 'catalog/index',
            'items' => (new CatalogItem())->all(['part', 'material']),
            'mode' => 'items',
        ]);
    }

    public function newService(): void
    {
        $this->new('services');
    }

    public function createService(): void
    {
        $this->create('services');
    }

    public function editService(string $id): void
    {
        $this->edit('services', $id);
    }

    public function updateService(string $id): void
    {
        $this->update('services', $id);
    }

    public function newItem(): void
    {
        $this->new('items');
    }

    public function createItem(): void
    {
        $this->create('items');
    }

    public function editItem(string $id): void
    {
        $this->edit('items', $id);
    }

    public function updateItem(string $id): void
    {
        $this->update('items', $id);
    }

    private function new(string $mode): void
    {
        $this->view('layouts/app', [
            'title' => $mode === 'services' ? 'New Service' : 'New Catalog Item',
            'active' => 'catalog',
            'content' => 'catalog/form',
            'mode' => $mode,
            'item' => null,
            'old' => $this->defaults($mode),
            'errors' => [],
        ]);
    }

    private function create(string $mode): void
    {
        $data = $this->requestData($mode);
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => $mode === 'services' ? 'New Service' : 'New Catalog Item',
                'active' => 'catalog',
                'content' => 'catalog/form',
                'mode' => $mode,
                'item' => null,
                'old' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $id = (new CatalogItem())->create($data);
        $this->redirect($this->editPath($mode, $id));
    }

    private function edit(string $mode, string $id): void
    {
        $item = (new CatalogItem())->find((int) $id);

        if (!$item) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Catalog item not found',
                'message' => 'That catalog item could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => 'Edit ' . $item['name'],
            'active' => 'catalog',
            'content' => 'catalog/form',
            'mode' => $mode,
            'item' => $item,
            'old' => $item,
            'errors' => [],
        ]);
    }

    private function update(string $mode, string $id): void
    {
        $model = new CatalogItem();
        $item = $model->find((int) $id);

        if (!$item) {
            $this->redirect($this->indexPath($mode));
        }

        $data = $this->requestData($mode);
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'Edit ' . $item['name'],
                'active' => 'catalog',
                'content' => 'catalog/form',
                'mode' => $mode,
                'item' => $item,
                'old' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $model->update((int) $id, $data);
        $this->redirect($this->indexPath($mode));
    }

    private function requestData(string $mode): array
    {
        return [
            'sku' => $this->input('sku', ''),
            'item_type' => $this->input('item_type', $mode === 'services' ? 'service' : 'part'),
            'name' => $this->input('name', ''),
            'category' => $this->input('category', ''),
            'price' => $this->input('price', '0'),
            'price_type' => $this->input('price_type', 'flat_rate'),
            'taxable' => $this->input('taxable', '') === '1',
            'status' => $this->input('status', 'active'),
            'short_description' => $this->input('short_description', ''),
            'long_description' => $this->input('long_description', ''),
            'warranty_eligible' => $this->input('warranty_eligible', '') === '1',
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        foreach (['sku', 'name', 'category'] as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[$field] = 'Required';
            }
        }

        if (!in_array($data['item_type'], CatalogItem::ITEM_TYPES, true)) {
            $errors['item_type'] = 'Choose a valid type';
        }

        if (!in_array($data['price_type'], CatalogItem::PRICE_TYPES, true)) {
            $errors['price_type'] = 'Choose a valid price type';
        }

        if (!in_array($data['status'], CatalogItem::STATUSES, true)) {
            $errors['status'] = 'Choose a valid status';
        }

        if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors['price'] = 'Use a valid price';
        }

        return $errors;
    }

    private function defaults(string $mode): array
    {
        return [
            'sku' => '',
            'item_type' => $mode === 'services' ? 'service' : 'part',
            'name' => '',
            'category' => '',
            'price' => '0.00',
            'price_type' => 'flat_rate',
            'taxable' => 0,
            'status' => 'active',
            'short_description' => '',
            'long_description' => '',
            'warranty_eligible' => 0,
        ];
    }

    private function indexPath(string $mode): string
    {
        return $mode === 'services' ? '/catalog/services' : '/catalog/items';
    }

    private function editPath(string $mode, int $id): string
    {
        return $this->indexPath($mode) . '/' . $id . '/edit';
    }
}
