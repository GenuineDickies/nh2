<?php

use App\Core\View;

$address = View::address($workOrder, 'Not captured');
$vehicle = trim(($workOrder['year'] ?? '') . ' ' . ($workOrder['make'] ?? '') . ' ' . ($workOrder['model'] ?? '') . ' ' . ($workOrder['color'] ?? '')) ?: 'Not captured';
$mapQuery = urlencode($address);
?>
<div class="field-screen">
    <section class="panel field-hero">
        <div>
            <p class="eyebrow">Field Work</p>
            <h2><?= View::e($workOrder['first_name'] . ' ' . $workOrder['last_name']) ?></h2>
            <p class="muted"><?= View::e($workOrder['requested_service']) ?></p>
        </div>
        <span class="status-badge"><?= View::e($workOrder['status']) ?></span>
    </section>

    <div class="field-actions">
        <a class="primary-action" href="tel:<?= View::e($workOrder['phone']) ?>">Call</a>
        <a class="secondary-action" href="https://www.google.com/maps/search/?api=1&query=<?= View::e($mapQuery) ?>">Map</a>
    </div>

    <section class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Job</p>
                <h2>Field Details</h2>
            </div>
        </div>
        <dl class="details">
            <dt>Location</dt>
            <dd><?= View::e($address ?: 'Not captured') ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e($vehicle) ?></dd>
            <dt>VIN</dt>
            <dd><?= View::e($workOrder['vin'] ?: 'Missing') ?></dd>
            <dt>Estimate</dt>
            <dd>$<?= View::e(number_format((float) $workOrder['estimate_total'], 2)) ?></dd>
        </dl>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Dispatch</p>
                <h2>Quick Actions</h2>
            </div>
        </div>
        <div class="status-actions">
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/status">
                <button class="primary-action" type="submit" name="status" value="dispatched">En Route</button>
            </form>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/arrived">
                <button class="secondary-action" type="submit">Arrived</button>
            </form>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="completed">Complete Work</button>
            </form>
            <a class="primary-action" href="/service-reports/new?work_order_id=<?= (int) $workOrder['id'] ?>">Complete Service Report</a>
        </div>
    </section>
</div>

