<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CatalogItem;
use App\Models\AuditLog;
use App\Models\CustomerApproval;
use App\Models\CustomerLinkToken;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\GeneratedDocument;
use App\Models\ServiceRequest;

final class EstimateController extends Controller
{
    public function index(): void
    {
        $q = $this->query('q', '') ?? '';
        $this->view('layouts/app', [
            'title' => 'Estimates',
            'active' => 'estimates',
            'content' => 'estimates/index',
            'estimates' => (new Estimate())->search($q),
            'q' => $q,
        ]);
    }

    public function new(): void
    {
        $serviceRequestId = (int) ($_GET['service_request_id'] ?? 0);
        $serviceRequest = $serviceRequestId > 0 ? (new ServiceRequest())->findWithDetails($serviceRequestId) : null;

        $this->view('layouts/app', [
            'title' => 'New Estimate',
            'active' => 'estimates',
            'content' => 'estimates/new',
            'serviceRequest' => $serviceRequest,
            'serviceRequestId' => $serviceRequestId,
            'errors' => [],
        ]);
    }

    public function create(): void
    {
        $serviceRequestId = (int) $this->input('service_request_id', '0');
        $serviceRequest = $serviceRequestId > 0 ? (new ServiceRequest())->findWithDetails($serviceRequestId) : null;

        if (!$serviceRequest) {
            $this->view('layouts/app', [
                'title' => 'New Estimate',
                'active' => 'estimates',
                'content' => 'estimates/new',
                'serviceRequest' => null,
                'serviceRequestId' => $serviceRequestId,
                'errors' => ['service_request_id' => 'Choose a valid service request'],
            ]);
            return;
        }

        $estimateId = (new Estimate())->createFromServiceRequest($serviceRequest);
        $this->redirect('/estimates/' . $estimateId);
    }

    public function show(string $id): void
    {
        $estimateModel = new Estimate();
        $estimate = $estimateModel->findWithDetails((int) $id);

        if (!$estimate) {
            http_response_code(404);
            $this->view('layouts/error', [
                'title' => 'Estimate not found',
                'message' => 'That estimate could not be found.',
            ]);
            return;
        }

        $this->view('layouts/app', [
            'title' => $estimate['estimate_number'],
            'active' => 'estimates',
            'content' => 'estimates/show',
            'estimate' => $estimate,
            'lines' => (new EstimateLineItem())->forEstimate((int) $id),
            'catalogItems' => (new CatalogItem())->all(CatalogItem::ITEM_TYPES),
            'approvals' => (new CustomerApproval())->forEstimate((int) $id),
            'documents' => (new GeneratedDocument())->forRelated('estimate', (int) $id),
            'publicToken' => (new CustomerLinkToken())->latestForRelated('estimate', (int) $id, CustomerLinkToken::PURPOSE_ESTIMATE_APPROVAL),
            'errors' => [],
            'approvalErrors' => [],
            'approvalRequired' => $estimateModel->approvalRequired($estimate),
        ]);
    }

    public function mintPublicLink(string $id): void
    {
        $estimateId = (int) $id;
        $estimate = (new Estimate())->findWithDetails($estimateId);
        if (!$estimate) {
            $this->redirect('/estimates');
        }

        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $token = (new CustomerLinkToken())->mint(
            'estimate',
            $estimateId,
            CustomerLinkToken::PURPOSE_ESTIMATE_APPROVAL,
            true,
            $expires,
            Auth::userId()
        );

        (new AuditLog())->record('customer_link_minted', 'estimate', $estimateId, null, [
            'purpose' => CustomerLinkToken::PURPOSE_ESTIMATE_APPROVAL,
            'expires_at' => $expires,
            'token_suffix' => substr($token, -8),
        ]);

        $this->redirect('/estimates/' . $estimateId);
    }

    public function updateStatus(string $id): void
    {
        $status = $this->input('status', '');
        $estimateId = (int) $id;
        $model = new Estimate();
        $change = $model->updateStatus($estimateId, $status);

        if ($change && $change['old_status'] !== $change['new_status']) {
            (new AuditLog())->record('estimate_status_changed', 'estimate', $estimateId, [
                'status' => $change['old_status'],
            ], [
                'status' => $change['new_status'],
            ]);
        }

        $this->redirect('/estimates/' . $estimateId);
    }

    public function approve(string $id): void
    {
        $estimateId = (int) $id;
        $estimateModel = new Estimate();
        $estimate = $estimateModel->findWithDetails($estimateId);

        if (!$estimate) {
            $this->redirect('/estimates');
        }

        $customerName = $this->input('customer_name', '');
        $method = $this->input('approval_method', 'phone_confirmed');
        $errors = [];

        if ($customerName === '') {
            $errors['customer_name'] = 'Required';
        }

        if (!in_array($method, CustomerApproval::METHODS, true)) {
            $errors['approval_method'] = 'Choose a valid approval method';
        }

        if ($errors) {
            $this->view('layouts/app', [
                'title' => $estimate['estimate_number'],
                'active' => 'estimates',
                'content' => 'estimates/show',
                'estimate' => $estimate,
                'lines' => (new EstimateLineItem())->forEstimate($estimateId),
                'catalogItems' => (new CatalogItem())->all(CatalogItem::ITEM_TYPES),
                'approvals' => (new CustomerApproval())->forEstimate($estimateId),
                'documents' => (new GeneratedDocument())->forRelated('estimate', $estimateId),
                'errors' => [],
                'approvalErrors' => $errors,
                'approvalRequired' => $estimateModel->approvalRequired($estimate),
            ]);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $approvalId = (new CustomerApproval())->createForEstimate($estimate, $customerName, $method);
            $estimateModel->updateStatus($estimateId, 'approved');
            (new AuditLog())->record('estimate_approved', 'estimate', $estimateId, null, [
                'approval_id' => $approvalId,
                'customer_name' => $customerName,
                'approval_method' => $method,
            ]);
            (new AuditLog())->record('estimate_approved', 'service_request', (int) $estimate['service_request_id'], null, [
                'estimate_id' => $estimateId,
                'approval_id' => $approvalId,
            ]);
            $db->commit();
            $this->redirect('/estimates/' . $estimateId);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function addLine(string $id): void
    {
        $estimateModel = new Estimate();
        $estimate = $estimateModel->findWithDetails((int) $id);

        if (!$estimate) {
            $this->redirect('/estimates');
        }

        $data = $this->lineData();
        $errors = $this->validateLine($data);

        if ($errors) {
            $this->view('layouts/app', [
                'title' => $estimate['estimate_number'],
                'active' => 'estimates',
                'content' => 'estimates/show',
                'estimate' => $estimate,
                'lines' => (new EstimateLineItem())->forEstimate((int) $id),
                'catalogItems' => (new CatalogItem())->all(CatalogItem::ITEM_TYPES),
                'approvals' => (new CustomerApproval())->forEstimate((int) $id),
                'documents' => (new GeneratedDocument())->forRelated('estimate', (int) $id),
                'errors' => $errors,
                'approvalErrors' => [],
                'approvalRequired' => $estimateModel->approvalRequired($estimate),
            ]);
            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            (new EstimateLineItem())->create((int) $id, $data);
            $estimateModel->recalculate((int) $id);
            $db->commit();
            $this->redirect('/estimates/' . (int) $id);
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private function lineData(): array
    {
        $catalogItemId = (int) $this->input('catalog_item_id', '0');
        $catalogItem = $catalogItemId > 0 ? (new CatalogItem())->find($catalogItemId) : null;
        $isCatalogAttempt = array_key_exists('catalog_item_id', $_POST);

        return [
            'catalog_item_id' => $catalogItemId > 0 ? $catalogItemId : null,
            'line_type' => $catalogItem['item_type'] ?? $this->input('line_type', 'custom'),
            'description' => $catalogItem ? $catalogItem['name'] : $this->input('description', ''),
            'quantity' => $this->input('quantity', '1'),
            'unit_price' => $catalogItem ? $catalogItem['price'] : $this->input('unit_price', '0'),
            'taxable' => $catalogItem ? (int) $catalogItem['taxable'] === 1 : $this->input('taxable', '') === '1',
            'catalog_attempt' => $isCatalogAttempt,
        ];
    }

    private function validateLine(array $data): array
    {
        $errors = [];

        if (!empty($data['catalog_attempt']) && empty($data['catalog_item_id'])) {
            $errors['catalog_item_id'] = 'Choose a catalog item';
        }

        if (($data['description'] ?? '') === '') {
            $errors['description'] = 'Required';
        }

        if (!in_array($data['line_type'], ['service', 'part', 'material', 'fee', 'labor', 'custom'], true)) {
            $errors['line_type'] = 'Choose a valid line type';
        }

        if (!is_numeric($data['quantity']) || (float) $data['quantity'] <= 0) {
            $errors['quantity'] = 'Use a valid quantity';
        }

        if (!is_numeric($data['unit_price']) || (float) $data['unit_price'] < 0) {
            $errors['unit_price'] = 'Use a valid price';
        }

        return $errors;
    }
}
