<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Service Request</p>
                <h2><?= View::e($serviceRequest['service_request_number']) ?></h2>
            </div>
            <span class="status-badge status-pending"><?= View::e($serviceRequest['status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($serviceRequest['first_name'] . ' ' . $serviceRequest['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($serviceRequest['phone']) ?>"><?= View::e($serviceRequest['phone']) ?></a></dd>
            <dt>Requested service</dt>
            <dd><?= View::e($serviceRequest['requested_service']) ?></dd>
            <dt>Location</dt>
            <dd><?= View::e(View::address($serviceRequest, 'Not captured')) ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e(trim(($serviceRequest['year'] ?? '') . ' ' . ($serviceRequest['make'] ?? '') . ' ' . ($serviceRequest['model'] ?? '') . ' ' . ($serviceRequest['color'] ?? '')) ?: 'Not captured') ?></dd>
            <dt>VIN</dt>
            <dd><?= View::e($serviceRequest['vin'] ?: 'Missing') ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Details</p>
                <h2>Actions</h2>
            </div>
        </div>
        <div class="stacked-actions">
            <a class="primary-action" href="/estimates/new?service_request_id=<?= (int) $serviceRequest['id'] ?>">Create Estimate</a>
            <a class="secondary-action" href="/service-requests/<?= (int) $serviceRequest['id'] ?>/proof-packet">Proof Packet</a>
            <a class="secondary-action" href="/service-requests/<?= (int) $serviceRequest['id'] ?>/edit">Edit Service Request</a>
        </div>
    </aside>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Status</p>
                <h2>Next Move</h2>
            </div>
        </div>
        <form class="status-actions" method="post" action="/service-requests/<?= (int) $serviceRequest['id'] ?>/status">
            <?php foreach ($statuses as $status): ?>
                <button class="<?= $serviceRequest['status'] === $status ? 'primary-action' : 'secondary-action' ?>" type="submit" name="status" value="<?= View::e($status) ?>">
                    <?= View::e(ucwords($status)) ?>
                </button>
            <?php endforeach; ?>
        </form>
    </aside>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Timeline</p>
                <h2>Audit Trail</h2>
            </div>
        </div>
        <?php if (!$timeline): ?>
            <p class="muted">No timeline events yet.</p>
        <?php else: ?>
            <ol class="timeline">
                <?php foreach ($timeline as $event): ?>
                    <li>
                        <strong><?= View::e(str_replace('_', ' ', $event['action'])) ?></strong>
                        <span><?= View::e($event['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </aside>
</div>

<?php
    $portalScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $portalBase = $portalScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
?>
<div class="detail-grid">
    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Customer Portal</p>
                <h2>Status Link</h2>
            </div>
        </div>
        <?php $statusUrl = $statusToken ? $portalBase . '/p/status/' . $statusToken['token'] : null; ?>
        <?php if ($statusUrl): ?>
            <p class="muted">Share this link so the customer can check job updates without logging in. Stays active until <?= View::e($statusToken['expires_at'] ?? 'forever') ?>.</p>
            <label>Link
                <input type="text" readonly value="<?= View::e($statusUrl) ?>" onclick="this.select()">
            </label>
            <form method="post" action="/service-requests/<?= (int) $serviceRequest['id'] ?>/status-link">
                <button class="secondary-action" type="submit">Generate New Status Link</button>
            </form>
        <?php else: ?>
            <p class="muted">No status link yet. Generate one to share a read-only status page.</p>
            <form method="post" action="/service-requests/<?= (int) $serviceRequest['id'] ?>/status-link">
                <button class="primary-action" type="submit">Generate Status Link</button>
            </form>
        <?php endif; ?>
    </aside>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Customer Portal</p>
                <h2>Location Confirmation Link</h2>
            </div>
        </div>
        <?php $locationUrl = $locationToken ? $portalBase . '/p/location/' . $locationToken['token'] : null; ?>
        <?php if ($locationUrl): ?>
            <?php if (!empty($locationToken['used_at'])): ?>
                <p class="muted">Used <?= View::e($locationToken['used_at']) ?> &mdash; generate a new link if the address needs to change again.</p>
            <?php else: ?>
                <p class="muted">Send to the customer so they can confirm or correct the address. Expires <?= View::e($locationToken['expires_at'] ?? '') ?>.</p>
                <label>Link
                    <input type="text" readonly value="<?= View::e($locationUrl) ?>" onclick="this.select()">
                </label>
            <?php endif; ?>
            <form method="post" action="/service-requests/<?= (int) $serviceRequest['id'] ?>/location-link">
                <button class="secondary-action" type="submit">Generate New Location Link</button>
            </form>
        <?php else: ?>
            <p class="muted">No location confirmation link yet. Generate one and text it so the customer can confirm where to meet.</p>
            <form method="post" action="/service-requests/<?= (int) $serviceRequest['id'] ?>/location-link">
                <button class="primary-action" type="submit">Generate Location Link</button>
            </form>
        <?php endif; ?>
    </aside>
</div>
