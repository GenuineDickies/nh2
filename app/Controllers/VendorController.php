<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Vendor;

final class VendorController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Vendors',
            'active' => 'vendors',
            'content' => 'vendors/index',
            'vendors' => (new Vendor())->search($q),
            'q' => $q,
        ]);
    }

    public function new(): void
    {
        $this->renderForm(null, [], [], 'New Vendor');
    }

    public function create(): void
    {
        $data = $this->inputData();
        $errors = (new Vendor())->validate($data);

        if ($errors) {
            $this->renderForm(null, $errors, $data, 'New Vendor');
            return;
        }

        $vendorId = (new Vendor())->create($data);
        (new AuditLog())->record('vendor_created', 'vendor', $vendorId, null, [
            'name' => $data['name'],
        ]);

        $this->redirect('/vendors/' . $vendorId);
    }

    public function show(string $id): void
    {
        $vendor = (new Vendor())->find((int) $id);

        if (!$vendor) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Vendor not found',
                'message' => 'That vendor record could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $vendor['name'],
            'active' => 'vendors',
            'content' => 'vendors/show',
            'vendor' => $vendor,
        ]);
    }

    public function edit(string $id): void
    {
        $vendor = (new Vendor())->find((int) $id);
        if (!$vendor) {
            $this->redirect('/vendors');
        }
        $this->renderForm($vendor, [], $vendor, 'Edit ' . $vendor['name']);
    }

    public function update(string $id): void
    {
        $vendorId = (int) $id;
        $vendorModel = new Vendor();
        $vendor = $vendorModel->find($vendorId);
        if (!$vendor) {
            $this->redirect('/vendors');
        }

        $data = $this->inputData();
        $errors = $vendorModel->validate($data);

        if ($errors) {
            $this->renderForm($vendor, $errors, $data, 'Edit ' . $vendor['name']);
            return;
        }

        $vendorModel->update($vendorId, $data);
        (new AuditLog())->record('vendor_updated', 'vendor', $vendorId, [
            'name' => $vendor['name'],
            'status' => $vendor['status'],
        ], [
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        $this->redirect('/vendors/' . $vendorId);
    }

    private function renderForm(?array $vendor, array $errors, array $data, string $title): void
    {
        $this->view('layouts/app', [
            'title' => $title,
            'active' => 'vendors',
            'content' => 'vendors/form',
            'vendor' => $vendor,
            'errors' => $errors,
            'data' => $data,
        ]);
    }

    private function inputData(): array
    {
        return [
            'name' => (string) $this->input('name', ''),
            'phone' => (string) $this->input('phone', ''),
            'email' => (string) $this->input('email', ''),
            'website' => (string) $this->input('website', ''),
            'address' => (string) $this->input('address', ''),
            'notes' => (string) $this->input('notes', ''),
            'status' => (string) $this->input('status', 'active'),
        ];
    }
}
