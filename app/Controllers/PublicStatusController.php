<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CustomerLinkToken;
use App\Models\ServiceRequest;

final class PublicStatusController extends Controller
{
    /** Audit-log actions the customer is allowed to see on the public status timeline. */
    private const PUBLIC_TIMELINE_ACTIONS = [
        'created_from_intake' => 'Job opened',
        'created_directly' => 'Job opened',
        'status_changed' => 'Status updated',
        'estimate_approved' => 'Estimate approved',
        'estimate_declined' => 'Estimate declined',
        'work_order_created' => 'Work order created',
        'work_order_arrived' => 'Technician arrived',
        'work_order_status_changed' => 'Work updated',
        'invoice_created' => 'Invoice prepared',
        'invoice_issued' => 'Invoice sent',
        'payment_recorded' => 'Payment received',
    ];

    public function show(string $token): void
    {
        $tokenRow = (new CustomerLinkToken())->lookup(
            $token,
            'service_request',
            CustomerLinkToken::PURPOSE_STATUS
        );

        if (!$tokenRow) {
            $this->renderInvalid();
            return;
        }

        $model = new ServiceRequest();
        $serviceRequest = $model->findWithDetails((int) $tokenRow['related_id']);
        if (!$serviceRequest) {
            $this->renderInvalid();
            return;
        }

        $timeline = [];
        foreach ($model->timeline((int) $serviceRequest['id']) as $event) {
            $label = self::PUBLIC_TIMELINE_ACTIONS[$event['action']] ?? null;
            if ($label === null) {
                continue;
            }
            $timeline[] = [
                'label' => $label,
                'at' => $event['created_at'],
            ];
        }

        $this->view('layouts/public', [
            'title' => 'Status ' . $serviceRequest['service_request_number'],
            'content' => 'public/status',
            'serviceRequest' => $serviceRequest,
            'timeline' => $timeline,
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
