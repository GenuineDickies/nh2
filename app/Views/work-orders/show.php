<?php

use App\Core\View;

$address = View::address($workOrder, 'Not captured');
$vehicle = trim(($workOrder['year'] ?? '') . ' ' . ($workOrder['make'] ?? '') . ' ' . ($workOrder['model'] ?? '') . ' ' . ($workOrder['color'] ?? '')) ?: 'Not captured';
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Work Order</p>
                <h2><?= View::e($workOrder['work_order_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($workOrder['status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($workOrder['first_name'] . ' ' . $workOrder['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($workOrder['phone']) ?>"><?= View::e($workOrder['phone']) ?></a></dd>
            <dt>Service</dt>
            <dd><?= View::e($workOrder['requested_service']) ?></dd>
            <dt>Location</dt>
            <dd><?= View::e($address ?: 'Not captured') ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e($vehicle) ?></dd>
            <dt>Estimate</dt>
            <dd><a href="/estimates/<?= (int) $workOrder['estimate_id'] ?>"><?= View::e($workOrder['estimate_number'] ?: 'None') ?></a></dd>
            <dt>Estimate total</dt>
            <dd>$<?= View::e(number_format((float) $workOrder['estimate_total'], 2)) ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Dispatch</p>
                <h2>Field Actions</h2>
            </div>
        </div>
        <div class="status-actions">
            <a class="primary-action" href="/work-orders/<?= (int) $workOrder['id'] ?>/field">Open Field Screen</a>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="dispatched">En Route</button>
            </form>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/arrived">
                <button class="secondary-action" type="submit">Arrived</button>
            </form>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="completed">Complete Work</button>
            </form>
            <form method="post" action="/work-orders/<?= (int) $workOrder['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="cancelled">Cancel</button>
            </form>
            <?php if ($workOrder['status'] === 'completed'): ?>
                <a class="primary-action" href="/service-reports/new?work_order_id=<?= (int) $workOrder['id'] ?>">Create Service Report</a>
            <?php endif; ?>
        </div>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Timestamps</p>
            <h2>Dispatch Record</h2>
        </div>
    </div>
    <dl class="details">
        <dt>Created</dt>
        <dd><?= View::e($workOrder['created_at']) ?></dd>
        <dt>Dispatch started</dt>
        <dd><?= View::e($workOrder['dispatch_started_at'] ?: 'Not started') ?></dd>
        <dt>Arrived</dt>
        <dd><?= View::e($workOrder['arrived_at'] ?: 'Not marked') ?></dd>
        <dt>Completed</dt>
        <dd><?= View::e($workOrder['completed_at'] ?: 'Not completed') ?></dd>
    </dl>
</div>
