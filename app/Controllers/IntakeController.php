<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Intake;
use App\Models\Location;
use App\Models\ServiceRequest;
use App\Models\Vehicle;

final class IntakeController extends Controller
{
    public function index(): void
    {
        $this->view('layouts/app', [
            'title' => 'Intake',
            'active' => 'intake',
            'content' => 'intake/index',
            'intakes' => (new Intake())->all(),
        ]);
    }

    public function new(): void
    {
        $this->view('layouts/app', [
            'title' => 'New Intake',
            'active' => 'intake',
            'content' => 'intake/new',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function create(): void
    {
        $data = $this->requestData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'New Intake',
                'active' => 'intake',
                'content' => 'intake/new',
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $id = (new Intake())->create($data);
        $this->redirect('/intake/' . $id);
    }

    public function show(string $id): void
    {
        $intake = (new Intake())->find((int) $id);

        if (!$intake) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Intake not found',
                'message' => 'That intake record could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $intake['intake_number'],
            'active' => 'intake',
            'content' => 'intake/show',
            'intake' => $intake,
        ]);
    }

    public function edit(string $id): void
    {
        $intake = (new Intake())->find((int) $id);

        if (!$intake) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Intake not found',
                'message' => 'That intake record could not be found.',
            ]);
            return;
        }

        if ($intake['status'] === 'converted') {
            $this->redirect('/intake/' . (int) $id);
        }

        $this->view('layouts/app', [
            'title' => 'Edit ' . $intake['intake_number'],
            'active' => 'intake',
            'content' => 'intake/edit',
            'intake' => $intake,
            'errors' => [],
            'old' => $intake,
        ]);
    }

    public function update(string $id): void
    {
        $intakeModel = new Intake();
        $intake = $intakeModel->find((int) $id);

        if (!$intake || $intake['status'] === 'converted') {
            $this->redirect('/intake/' . (int) $id);
        }

        $data = $this->requestData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'Edit ' . $intake['intake_number'],
                'active' => 'intake',
                'content' => 'intake/edit',
                'intake' => $intake,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $intakeModel->update((int) $id, $data);
        $this->redirect('/intake/' . (int) $id);
    }

    public function convert(string $id): void
    {
        $intakeModel = new Intake();
        $intake = $intakeModel->find((int) $id);

        if (!$intake || $intake['status'] === 'converted') {
            $this->redirect('/intake/' . (int) $id);
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $customerId = (new Customer())->createIfMissing($intake['first_name'], $intake['last_name'], $intake['phone']);
            $locationId = (new Location())->createFromIntake($intake);
            $vehicleId = (new Vehicle())->createFromIntake($customerId, $intake);
            $serviceRequestId = (new ServiceRequest())->createFromIntake($intake, $customerId, $vehicleId, $locationId);
            $intakeModel->markConverted((int) $intake['id'], $serviceRequestId);

            (new AuditLog())->record('created_from_intake', 'service_request', $serviceRequestId, null, [
                'intake_id' => (int) $intake['id'],
                'status' => 'pending',
            ]);

            $db->commit();
            $this->redirect('/service-requests/' . $serviceRequestId);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private function requestData(): array
    {
        return [
            'first_name' => $this->input('first_name', ''),
            'last_name' => $this->input('last_name', ''),
            'phone' => $this->formatPhone($this->input('phone', '')),
            'service_requested' => $this->input('service_requested', ''),
            'location_address' => $this->input('location_address', ''),
            'location_city' => $this->input('location_city', ''),
            'location_state' => $this->input('location_state', ''),
            'location_postal_code' => $this->input('location_postal_code', ''),
            'vehicle_year' => $this->input('vehicle_year', ''),
            'vehicle_make' => $this->input('vehicle_make', ''),
            'vehicle_model' => $this->input('vehicle_model', ''),
            'vehicle_color' => $this->input('vehicle_color', ''),
            'lead_source' => $this->input('lead_source', 'direct'),
            'notes' => $this->input('notes', ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        foreach (['first_name', 'last_name', 'phone', 'service_requested'] as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[$field] = 'Required';
            }
        }

        if ($data['phone'] !== '' && !preg_match('/^\(\d{3}\) \d{3}-\d{4}$/', $data['phone'])) {
            $errors['phone'] = 'Use (xxx) xxx-xxxx';
        }

        return $errors;
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }

        return $phone;
    }
}
