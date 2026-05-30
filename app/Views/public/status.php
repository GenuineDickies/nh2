<?php

use App\Core\View;

$customerName = $serviceRequest['first_name'] . ' ' . $serviceRequest['last_name'];
$vehicle = trim(($serviceRequest['year'] ?? '') . ' ' . ($serviceRequest['make'] ?? '') . ' ' . ($serviceRequest['model'] ?? '') . ' ' . ($serviceRequest['color'] ?? '')) ?: 'Vehicle';
$address = View::address($serviceRequest, 'Address not captured');
$status = (string) ($serviceRequest['status'] ?? 'pending');
?>
<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Job</p>
            <h2><?= View::e($serviceRequest['service_request_number']) ?></h2>
        </div>
        <span class="status-badge status-pending"><?= View::e(ucwords(str_replace('_', ' ', $status))) ?></span>
    </div>
    <dl class="details">
        <dt>For</dt>
        <dd><?= View::e($customerName) ?></dd>
        <dt>Service</dt>
        <dd><?= View::e($serviceRequest['requested_service']) ?></dd>
        <dt>Vehicle</dt>
        <dd><?= View::e($vehicle) ?></dd>
        <dt>Location</dt>
        <dd><?= View::e($address) ?></dd>
    </dl>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Timeline</p>
            <h2>Job updates</h2>
        </div>
        <span class="status-badge"><?= count($timeline) ?> updates</span>
    </div>
    <?php if (!$timeline): ?>
        <p class="muted">No updates yet. Check back soon.</p>
    <?php else: ?>
        <ol class="timeline">
            <?php foreach ($timeline as $event): ?>
                <li>
                    <strong><?= View::e($event['label']) ?></strong>
                    <span><?= View::e($event['at']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</article>
