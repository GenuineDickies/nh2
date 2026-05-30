<?php

use App\Core\View;

$vehicleName = trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?: 'Vehicle';
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Vehicle</p>
                <h2><?= View::e($vehicleName) ?></h2>
            </div>
            <?php if (!$vehicle['vin']): ?>
                <span class="status-badge status-pending">VIN Missing</span>
            <?php endif; ?>
        </div>
        <dl class="details">
            <dt>Color</dt>
            <dd><?= View::e($vehicle['color'] ?: 'Not captured') ?></dd>
            <dt>VIN</dt>
            <dd><?= View::e($vehicle['vin'] ?: 'Missing') ?></dd>
            <dt>Plate</dt>
            <dd><?= View::e(trim(($vehicle['plate_state'] ?? '') . ' ' . ($vehicle['plate_number'] ?? '')) ?: 'Not captured') ?></dd>
            <dt>Customer</dt>
            <dd>
                <?php if ($vehicle['customer_id']): ?>
                    <a href="/customers/<?= (int) $vehicle['customer_id'] ?>"><?= View::e($vehicle['first_name'] . ' ' . $vehicle['last_name']) ?></a>
                <?php else: ?>
                    Unassigned
                <?php endif; ?>
            </dd>
            <dt>Phone</dt>
            <dd><?= View::e($vehicle['phone'] ?: 'Not captured') ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Invoice Rule</p>
                <h2>VIN Status</h2>
            </div>
        </div>
        <p class="muted">This vehicle is considered complete only when VIN is captured. Invoice rules will enforce VIN later unless no vehicle serviced is checked.</p>
    </aside>
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

