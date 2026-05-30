<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditLog;
use App\Models\CustomerApproval;
use App\Models\CustomerLinkToken;
use App\Models\Estimate;
use App\Models\EstimateLineItem;

final class PublicEstimateController extends Controller
{
    public function show(string $token): void
    {
        [$estimate, $lines, $tokenRow] = $this->load($token);

        if (!$estimate) {
            $this->renderInvalid();
            return;
        }

        $this->renderEstimate($estimate, $lines, $tokenRow, [], null);
    }

    public function approve(string $token): void
    {
        [$estimate, $lines, $tokenRow] = $this->load($token);

        if (!$estimate || !$tokenRow) {
            $this->renderInvalid();
            return;
        }

        if ($estimate['status'] === 'approved' || $estimate['status'] === 'declined') {
            $this->renderEstimate($estimate, $lines, $tokenRow, [], 'This estimate is no longer open for changes.');
            return;
        }

        $name = trim((string) ($_POST['customer_name'] ?? ''));
        $agreed = ($_POST['agreed'] ?? '') === '1';
        $errors = [];

        if ($name === '') {
            $errors['customer_name'] = 'Please enter the name on the estimate.';
        }
        if (!$agreed) {
            $errors['agreed'] = 'You must agree to the disclaimer to approve.';
        }

        if ($errors) {
            $this->renderEstimate($estimate, $lines, $tokenRow, $errors, null);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $approvalId = (new CustomerApproval())->createForEstimate($estimate, $name, 'sms_link');
            (new Estimate())->updateStatus((int) $estimate['id'], 'approved');
            (new AuditLog())->record('estimate_approved', 'estimate', (int) $estimate['id'], null, [
                'approval_id' => $approvalId,
                'customer_name' => $name,
                'approval_method' => 'sms_link',
                'via' => 'customer_portal',
            ]);
            (new AuditLog())->record('estimate_approved', 'service_request', (int) $estimate['service_request_id'], null, [
                'estimate_id' => (int) $estimate['id'],
                'approval_id' => $approvalId,
                'via' => 'customer_portal',
            ]);
            (new CustomerLinkToken())->markUsed((int) $tokenRow['id']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $estimate = (new Estimate())->findWithDetails((int) $estimate['id']);
        $lines = (new EstimateLineItem())->forEstimate((int) $estimate['id']);
        $this->renderEstimate($estimate, $lines, $tokenRow, [], 'Thank you. Your approval has been recorded.');
    }

    public function decline(string $token): void
    {
        [$estimate, $lines, $tokenRow] = $this->load($token);

        if (!$estimate || !$tokenRow) {
            $this->renderInvalid();
            return;
        }

        if ($estimate['status'] === 'approved' || $estimate['status'] === 'declined') {
            $this->renderEstimate($estimate, $lines, $tokenRow, [], 'This estimate is no longer open for changes.');
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            (new Estimate())->updateStatus((int) $estimate['id'], 'declined');
            (new AuditLog())->record('estimate_declined', 'estimate', (int) $estimate['id'], null, [
                'via' => 'customer_portal',
            ]);
            (new AuditLog())->record('estimate_declined', 'service_request', (int) $estimate['service_request_id'], null, [
                'estimate_id' => (int) $estimate['id'],
                'via' => 'customer_portal',
            ]);
            (new CustomerLinkToken())->markUsed((int) $tokenRow['id']);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        $estimate = (new Estimate())->findWithDetails((int) $estimate['id']);
        $lines = (new EstimateLineItem())->forEstimate((int) $estimate['id']);
        $this->renderEstimate($estimate, $lines, $tokenRow, [], 'Got it. The estimate has been marked declined.');
    }

    private function load(string $token): array
    {
        $tokenRow = (new CustomerLinkToken())->lookup(
            $token,
            'estimate',
            CustomerLinkToken::PURPOSE_ESTIMATE_APPROVAL
        );

        if (!$tokenRow) {
            return [null, [], null];
        }

        $estimate = (new Estimate())->findWithDetails((int) $tokenRow['related_id']);
        if (!$estimate) {
            return [null, [], null];
        }

        $lines = (new EstimateLineItem())->forEstimate((int) $estimate['id']);
        return [$estimate, $lines, $tokenRow];
    }

    private function renderEstimate(array $estimate, array $lines, ?array $tokenRow, array $errors, ?string $flash): void
    {
        $this->view('layouts/public', [
            'title' => 'Estimate ' . $estimate['estimate_number'],
            'content' => 'public/estimate',
            'estimate' => $estimate,
            'lines' => $lines,
            'token' => $tokenRow['token'] ?? '',
            'errors' => $errors,
            'flash' => $flash,
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
