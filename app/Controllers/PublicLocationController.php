<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\CustomerLinkToken;
use App\Models\Location;
use App\Models\ServiceRequest;

final class PublicLocationController extends Controller
{
    public function show(string $token): void
    {
        [$tokenRow, $serviceRequest] = $this->load($token);
        if (!$tokenRow) {
            $this->renderInvalid();
            return;
        }

        $this->render($tokenRow, $serviceRequest, [], null);
    }

    public function confirm(string $token): void
    {
        [$tokenRow, $serviceRequest] = $this->load($token);
        if (!$tokenRow) {
            $this->renderInvalid();
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            (new AuditLog())->record('location_confirmed', 'service_request', (int) $serviceRequest['id'], null, [
                'address_line_1' => $serviceRequest['address_line_1'],
                'city' => $serviceRequest['city'],
                'state' => $serviceRequest['state'],
                'postal_code' => $serviceRequest['postal_code'],
                'via' => 'customer_portal',
            ]);
            (new CustomerLinkToken())->markUsed((int) $tokenRow['id']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $serviceRequest = (new ServiceRequest())->findWithDetails((int) $serviceRequest['id']);
        $this->render(null, $serviceRequest, [], 'Thanks - we have the right address.');
    }

    public function update(string $token): void
    {
        [$tokenRow, $serviceRequest] = $this->load($token);
        if (!$tokenRow) {
            $this->renderInvalid();
            return;
        }

        $data = [
            'location_address' => trim((string) ($_POST['location_address'] ?? '')),
            'location_city' => trim((string) ($_POST['location_city'] ?? '')),
            'location_state' => trim((string) ($_POST['location_state'] ?? '')),
            'location_postal_code' => trim((string) ($_POST['location_postal_code'] ?? '')),
        ];

        $errors = [];
        if ($data['location_address'] === '') {
            $errors['location_address'] = 'Street address is required.';
        }
        if ($data['location_city'] === '') {
            $errors['location_city'] = 'City is required.';
        }

        if ($errors) {
            $this->render($tokenRow, $serviceRequest, $errors, null, $data);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            (new Location())->updateBasic((int) $serviceRequest['location_id'], $data);
            (new AuditLog())->record('location_updated', 'service_request', (int) $serviceRequest['id'], [
                'address_line_1' => $serviceRequest['address_line_1'],
                'city' => $serviceRequest['city'],
                'state' => $serviceRequest['state'],
                'postal_code' => $serviceRequest['postal_code'],
            ], [
                'address_line_1' => $data['location_address'],
                'city' => $data['location_city'],
                'state' => $data['location_state'],
                'postal_code' => $data['location_postal_code'],
                'via' => 'customer_portal',
            ]);
            (new CustomerLinkToken())->markUsed((int) $tokenRow['id']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $serviceRequest = (new ServiceRequest())->findWithDetails((int) $serviceRequest['id']);
        $this->render(null, $serviceRequest, [], 'Got it - we updated the address.');
    }

    private function load(string $token): array
    {
        $tokenRow = (new CustomerLinkToken())->lookup(
            $token,
            'service_request',
            CustomerLinkToken::PURPOSE_LOCATION_CONFIRMATION
        );
        if (!$tokenRow) {
            return [null, null];
        }
        $serviceRequest = (new ServiceRequest())->findWithDetails((int) $tokenRow['related_id']);
        if (!$serviceRequest) {
            return [null, null];
        }
        return [$tokenRow, $serviceRequest];
    }

    private function render(?array $tokenRow, array $serviceRequest, array $errors, ?string $flash, ?array $formData = null): void
    {
        $this->view('layouts/public', [
            'title' => 'Confirm Location',
            'content' => 'public/location',
            'serviceRequest' => $serviceRequest,
            'token' => $tokenRow['token'] ?? '',
            'errors' => $errors,
            'flash' => $flash,
            'formData' => $formData,
        ]);
    }

    private function renderInvalid(): void
    {
        http_response_code(404);
        $this->view('layouts/public', [
            'title' => 'Link no longer valid',
            'content' => 'public/invalid',
        ]);
    }
}
