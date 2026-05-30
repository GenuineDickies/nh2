<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Customer</p>
                <h2><?= View::e($customer['first_name'] . ' ' . $customer['last_name']) ?></h2>
            </div>
        </div>
        <dl class="details">
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($customer['phone']) ?>"><?= View::e($customer['phone']) ?></a></dd>
            <dt>Email</dt>
            <dd><?= View::e($customer['email'] ?: 'Not captured') ?></dd>
            <dt>Notes</dt>
            <dd><?= View::e($customer['notes'] ?: 'None') ?></dd>
            <dt>Created</dt>
            <dd><?= View::e($customer['created_at']) ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Actions</p>
                <h2>Next Step</h2>
            </div>
        </div>
        <a class="primary-action" href="/service-requests/new">New Service Request</a>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Garage</p>
            <h2>Vehicles</h2>
        </div>
        <span class="status-badge"><?= count($vehicles) ?> total</span>
    </div>
    <?php if (!$vehicles): ?>
        <p class="muted">No vehicles captured yet.</p>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($vehicles as $vehicle): ?>
                <a class="record-row" href="/vehicles/<?= (int) $vehicle['id'] ?>">
                    <strong><?= View::e(trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?: 'Vehicle') ?></strong>
                    <span><?= View::e($vehicle['vin'] ?: 'VIN missing') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">History</p>
            <h2>Service Requests</h2>
        </div>
        <span class="status-badge"><?= count($serviceRequests) ?> total</span>
    </div>
    <?php if (!$serviceRequests): ?>
        <p class="muted">No service requests yet.</p>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($serviceRequests as $request): ?>
                <a class="record-row" href="/service-requests/<?= (int) $request['id'] ?>">
                    <strong><?= View::e($request['service_request_number'] . ' - ' . $request['requested_service']) ?></strong>
                    <span><?= View::e(ucwords($request['status']) . ' at ' . View::address($request, 'Unknown location')) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

