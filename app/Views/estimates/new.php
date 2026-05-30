<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Estimate</p>
                <h2>Create From Service Request</h2>
            </div>
        </div>

        <?php if (!$serviceRequest): ?>
            <div class="alert">Choose a valid service request before creating an estimate.</div>
            <p class="muted">Open a service request and use Create Estimate.</p>
            <a class="primary-action" href="/service-requests">Open Service Requests</a>
        <?php else: ?>
            <dl class="details">
                <dt>Service Request</dt>
                <dd><?= View::e($serviceRequest['service_request_number']) ?></dd>
                <dt>Customer</dt>
                <dd><?= View::e($serviceRequest['first_name'] . ' ' . $serviceRequest['last_name']) ?></dd>
                <dt>Service</dt>
                <dd><?= View::e($serviceRequest['requested_service']) ?></dd>
                <dt>Vehicle</dt>
                <dd><?= View::e(trim(($serviceRequest['year'] ?? '') . ' ' . ($serviceRequest['make'] ?? '') . ' ' . ($serviceRequest['model'] ?? '')) ?: 'Not captured') ?></dd>
            </dl>
            <form method="post" action="/estimates">
                <input type="hidden" name="service_request_id" value="<?= (int) $serviceRequest['id'] ?>">
                <button class="primary-action" type="submit">Create Draft Estimate</button>
            </form>
        <?php endif; ?>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Rule</p>
                <h2>Approval</h2>
            </div>
        </div>
        <p class="muted">Customer approval is required when the estimate total is over $200.</p>
    </aside>
</div>

