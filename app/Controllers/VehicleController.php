<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Vehicle;

final class VehicleController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Vehicles',
            'active' => 'vehicles',
            'content' => 'vehicles/index',
            'vehicles' => (new Vehicle())->search($q),
            'q' => $q,
        ]);
    }

    public function show(string $id): void
    {
        $model = new Vehicle();
        $vehicle = $model->findWithDetails((int) $id);

        if (!$vehicle) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Vehicle not found',
                'message' => 'That vehicle record could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?: 'Vehicle',
            'active' => 'vehicles',
            'content' => 'vehicles/show',
            'vehicle' => $vehicle,
            'serviceRequests' => $model->serviceRequests((int) $id),
        ]);
    }
}

