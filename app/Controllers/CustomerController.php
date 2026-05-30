<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

final class CustomerController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $model = new Customer();
        $this->view('layouts/app', [
            'title' => 'Customers',
            'active' => 'customers',
            'content' => 'customers/index',
            'customers' => $model->search($q),
            'q' => $q,
        ]);
    }

    public function show(string $id): void
    {
        $model = new Customer();
        $customer = $model->find((int) $id);

        if (!$customer) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Customer not found',
                'message' => 'That customer record could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $customer['first_name'] . ' ' . $customer['last_name'],
            'active' => 'customers',
            'content' => 'customers/show',
            'customer' => $customer,
            'vehicles' => $model->vehicles((int) $id),
            'serviceRequests' => $model->serviceRequests((int) $id),
        ]);
    }
}

