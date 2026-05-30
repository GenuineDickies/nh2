<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerLinkToken;
use App\Models\Location;
use App\Models\ServiceRequest;
use App\Models\Vehicle;

final class ServiceRequestController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Service Requests',
            'active' => 'service-requests',
            'content' => 'service-requests/index',
            'serviceRequests' => (new ServiceRequest())->search($q),
            'q' => $q,
        ]);
    }

    public function new(): void
    {
        $this->view('layouts/app', [
            'title' => 'New Service Request',
            'active' => 'service-requests',
            'content' => 'service-requests/new',
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
                'title' => 'New Service Request',
                'active' => 'service-requests',
                'content' => 'service-requests/new',
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $customerId = (new Customer())->createIfMissing($data['first_name'], $data['last_name'], $data['phone']);
            $locationId = (new Location())->create($data);
            $vehicleId = (new Vehicle())->createBasic($customerId, $data);
            $serviceRequestId = (new ServiceRequest())->create([
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'location_id' => $locationId,
                'requested_service' => $data['requested_service'],
                'problem_description' => $data['problem_description'],
                'status' => 'pending',
                'priority' => $data['priority'],
                'lead_source' => $data['lead_source'],
            ]);

            (new AuditLog())->record('created_directly', 'service_request', $serviceRequestId, null, [
                'status' => 'pending',
                'requested_service' => $data['requested_service'],
            ]);

            $db->commit();
            $this->redirect('/service-requests/' . $serviceRequestId);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function show(string $id): void
    {
        $model = new ServiceRequest();
        $serviceRequest = $model->findWithDetails((int) $id);

        if (!$serviceRequest) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Service request not found',
                'message' => 'That service request could not be found.',
            ]);
            return;
        }

        $tokens = new CustomerLinkToken();
        $this->view('layouts/app', [
            'title' => $serviceRequest['service_request_number'],
            'active' => 'service-requests',
            'content' => 'service-requests/show',
            'serviceRequest' => $serviceRequest,
            'timeline' => $model->timeline((int) $id),
            'statuses' => ServiceRequest::STATUSES,
            'statusToken' => $tokens->latestForRelated('service_request', (int) $id, CustomerLinkToken::PURPOSE_STATUS),
            'locationToken' => $tokens->latestForRelated('service_request', (int) $id, CustomerLinkToken::PURPOSE_LOCATION_CONFIRMATION),
        ]);
    }

    public function mintStatusLink(string $id): void
    {
        $serviceRequestId = (int) $id;
        if (!(new ServiceRequest())->findWithDetails($serviceRequestId)) {
            $this->redirect('/service-requests');
        }

        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $token = (new CustomerLinkToken())->mint(
            'service_request',
            $serviceRequestId,
            CustomerLinkToken::PURPOSE_STATUS,
            false,
            $expires,
            Auth::userId()
        );

        (new AuditLog())->record('customer_link_minted', 'service_request', $serviceRequestId, null, [
            'purpose' => CustomerLinkToken::PURPOSE_STATUS,
            'expires_at' => $expires,
            'token_suffix' => substr($token, -8),
        ]);

        $this->redirect('/service-requests/' . $serviceRequestId);
    }

    public function mintLocationLink(string $id): void
    {
        $serviceRequestId = (int) $id;
        if (!(new ServiceRequest())->findWithDetails($serviceRequestId)) {
            $this->redirect('/service-requests');
        }

        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $token = (new CustomerLinkToken())->mint(
            'service_request',
            $serviceRequestId,
            CustomerLinkToken::PURPOSE_LOCATION_CONFIRMATION,
            true,
            $expires,
            Auth::userId()
        );

        (new AuditLog())->record('customer_link_minted', 'service_request', $serviceRequestId, null, [
            'purpose' => CustomerLinkToken::PURPOSE_LOCATION_CONFIRMATION,
            'expires_at' => $expires,
            'token_suffix' => substr($token, -8),
        ]);

        $this->redirect('/service-requests/' . $serviceRequestId);
    }

    public function proofPacket(string $id): void
    {
        $model = new ServiceRequest();
        $packet = $model->proofPacket((int) $id);

        if (!$packet) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Proof packet not found',
                'message' => 'That service request could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => 'Proof Packet',
            'active' => 'service-requests',
            'content' => 'service-requests/proof-packet',
            'packet' => $packet,
        ]);
    }

    public function edit(string $id): void
    {
        $serviceRequest = (new ServiceRequest())->findWithDetails((int) $id);

        if (!$serviceRequest) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Service request not found',
                'message' => 'That service request could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => 'Edit ' . $serviceRequest['service_request_number'],
            'active' => 'service-requests',
            'content' => 'service-requests/edit',
            'serviceRequest' => $serviceRequest,
            'errors' => [],
            'old' => $this->detailsToFormData($serviceRequest),
        ]);
    }

    public function update(string $id): void
    {
        $model = new ServiceRequest();
        $serviceRequest = $model->findWithDetails((int) $id);

        if (!$serviceRequest) {
            $this->redirect('/service-requests');
        }

        $data = $this->requestData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => 'Edit ' . $serviceRequest['service_request_number'],
                'active' => 'service-requests',
                'content' => 'service-requests/edit',
                'serviceRequest' => $serviceRequest,
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            (new Customer())->updateBasic((int) $serviceRequest['customer_id'], $data['first_name'], $data['last_name'], $data['phone']);
            (new Location())->updateBasic((int) $serviceRequest['location_id'], $data);

            $vehicleId = $serviceRequest['vehicle_id'] ? (int) $serviceRequest['vehicle_id'] : null;
            if ($vehicleId) {
                (new Vehicle())->updateBasic($vehicleId, $data);
            } else {
                $vehicleId = (new Vehicle())->createBasic((int) $serviceRequest['customer_id'], $data);
            }

            $model->updateCore((int) $id, [
                'vehicle_id' => $vehicleId,
                'requested_service' => $data['requested_service'],
                'problem_description' => $data['problem_description'],
                'priority' => $data['priority'],
                'lead_source' => $data['lead_source'],
            ]);

            (new AuditLog())->record('details_updated', 'service_request', (int) $id, $this->auditSnapshot($serviceRequest), $this->auditSnapshot(array_merge($serviceRequest, [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'requested_service' => $data['requested_service'],
                'problem_description' => $data['problem_description'],
                'priority' => $data['priority'],
                'lead_source' => $data['lead_source'],
                'address_line_1' => $data['location_address'],
                'city' => $data['location_city'],
                'state' => $data['location_state'],
                'postal_code' => $data['location_postal_code'],
                'year' => $data['vehicle_year'],
                'make' => $data['vehicle_make'],
                'model' => $data['vehicle_model'],
                'color' => $data['vehicle_color'],
            ])));

            $db->commit();
            $this->redirect('/service-requests/' . (int) $id);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function updateStatus(string $id): void
    {
        $status = $this->input('status', '');
        $serviceRequestId = (int) $id;
        $model = new ServiceRequest();
        $change = $model->updateStatus($serviceRequestId, $status);

        if ($change && $change['old_status'] !== $change['new_status']) {
            (new AuditLog())->record('status_changed', 'service_request', $serviceRequestId, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
            ]);
        }

        $this->redirect('/service-requests/' . $serviceRequestId);
    }

    private function requestData(): array
    {
        return [
            'first_name' => $this->input('first_name', ''),
            'last_name' => $this->input('last_name', ''),
            'phone' => $this->formatPhone($this->input('phone', '')),
            'requested_service' => $this->input('requested_service', ''),
            'problem_description' => $this->input('problem_description', ''),
            'location_address' => $this->input('location_address', ''),
            'location_city' => $this->input('location_city', ''),
            'location_state' => $this->input('location_state', ''),
            'location_postal_code' => $this->input('location_postal_code', ''),
            'vehicle_year' => $this->input('vehicle_year', ''),
            'vehicle_make' => $this->input('vehicle_make', ''),
            'vehicle_model' => $this->input('vehicle_model', ''),
            'vehicle_color' => $this->input('vehicle_color', ''),
            'priority' => $this->input('priority', 'normal'),
            'lead_source' => $this->input('lead_source', 'direct'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        foreach (['first_name', 'last_name', 'phone', 'requested_service', 'location_address'] as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[$field] = 'Required';
            }
        }

        if ($data['phone'] !== '' && !preg_match('/^\(\d{3}\) \d{3}-\d{4}$/', $data['phone'])) {
            $errors['phone'] = 'Use (xxx) xxx-xxxx';
        }

        if (!in_array($data['priority'], ['normal', 'urgent'], true)) {
            $errors['priority'] = 'Choose a valid priority';
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

    private function detailsToFormData(array $serviceRequest): array
    {
        return [
            'first_name' => $serviceRequest['first_name'] ?? '',
            'last_name' => $serviceRequest['last_name'] ?? '',
            'phone' => $serviceRequest['phone'] ?? '',
            'requested_service' => $serviceRequest['requested_service'] ?? '',
            'problem_description' => $serviceRequest['problem_description'] ?? '',
            'location_address' => $serviceRequest['address_line_1'] ?? '',
            'location_city' => $serviceRequest['city'] ?? '',
            'location_state' => $serviceRequest['state'] ?? '',
            'location_postal_code' => $serviceRequest['postal_code'] ?? '',
            'vehicle_year' => $serviceRequest['year'] ?? '',
            'vehicle_make' => $serviceRequest['make'] ?? '',
            'vehicle_model' => $serviceRequest['model'] ?? '',
            'vehicle_color' => $serviceRequest['color'] ?? '',
            'priority' => $serviceRequest['priority'] ?? 'normal',
            'lead_source' => $serviceRequest['lead_source'] ?? 'direct',
        ];
    }

    private function auditSnapshot(array $data): array
    {
        return [
            'customer' => trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')),
            'phone' => $data['phone'] ?? '',
            'requested_service' => $data['requested_service'] ?? '',
            'problem_description' => $data['problem_description'] ?? '',
            'priority' => $data['priority'] ?? '',
            'lead_source' => $data['lead_source'] ?? '',
            'location' => \App\Core\View::address($data),
            'vehicle' => trim(($data['year'] ?? '') . ' ' . ($data['make'] ?? '') . ' ' . ($data['model'] ?? '') . ' ' . ($data['color'] ?? '')),
        ];
    }
}
