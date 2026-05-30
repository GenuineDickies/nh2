<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Intake</p>
                <h2><?= View::e($intake['intake_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($intake['status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($intake['first_name'] . ' ' . $intake['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($intake['phone']) ?>"><?= View::e($intake['phone']) ?></a></dd>
            <dt>Service</dt>
            <dd><?= View::e($intake['service_requested']) ?></dd>
            <dt>Lead source</dt>
            <dd><?= View::e(ucwords(str_replace('_', ' ', $intake['lead_source']))) ?></dd>
            <dt>Location</dt>
            <dd><?= View::e(trim(($intake['location_address'] ?? '') . ' ' . ($intake['location_city'] ?? '') . ' ' . ($intake['location_state'] ?? '') . ' ' . ($intake['location_postal_code'] ?? ''))) ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e(trim(($intake['vehicle_year'] ?? '') . ' ' . ($intake['vehicle_make'] ?? '') . ' ' . ($intake['vehicle_model'] ?? '') . ' ' . ($intake['vehicle_color'] ?? ''))) ?></dd>
        </dl>
        <?php if ($intake['notes']): ?>
            <p class="notes"><?= View::e($intake['notes']) ?></p>
        <?php endif; ?>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Next step</p>
                <h2>Convert</h2>
            </div>
        </div>
        <?php if ($intake['status'] === 'converted'): ?>
            <p class="muted">This intake has already been converted.</p>
            <a class="primary-action" href="/service-requests/<?= (int) $intake['converted_service_request_id'] ?>">Open Service Request</a>
        <?php else: ?>
            <p class="muted">Create the customer if needed, preserve the location and vehicle basics, then open a pending service request.</p>
            <div class="stacked-actions">
                <a class="secondary-action" href="/intake/<?= (int) $intake['id'] ?>/edit">Edit Intake</a>
            </div>
            <form method="post" action="/intake/<?= (int) $intake['id'] ?>/convert">
                <button class="primary-action" type="submit">Convert to Service Request</button>
            </form>
        <?php endif; ?>
    </aside>
</div>
