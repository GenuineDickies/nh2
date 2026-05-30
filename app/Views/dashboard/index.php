<?php

use App\Core\View;

// Time-of-day greeting (renders against server local time).
$hour = (int) date('G');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

$operatorName = trim((string) ($currentUser['name'] ?? 'Operator'));
$operatorFirst = $operatorName === '' ? 'Operator' : explode(' ', $operatorName)[0];

// Per-card icon + accent. Keyed by the labels emitted by DashboardController.
// SVGs are inline so they inherit currentColor and stay sharp.
$cardMeta = [
    'Active Jobs' => [
        'tone' => 'is-purple',
        'icon' => '<path d="M5 16V11l2-5h10l2 5v5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M3 16h18v3H3z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="7.5" cy="19" r="1.6" fill="currentColor"/><circle cx="16.5" cy="19" r="1.6" fill="currentColor"/>',
    ],
    'New Intake' => [
        'tone' => '',
        'icon' => '<path d="M4 4h12l4 4v12H4z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M16 4v4h4" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M8 13h8M8 17h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
    ],
    'Converted Intake' => [
        'tone' => 'is-success',
        'icon' => '<path d="M5 12l4 4 10-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ],
    'Pending Requests' => [
        'tone' => 'is-warning',
        'icon' => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
    ],
    'Customers' => [
        'tone' => '',
        'icon' => '<circle cx="9" cy="9" r="3.4" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M16 11a3 3 0 1 0 0-6M22 19a5.5 5.5 0 0 0-5-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
    ],
    'Vehicles' => [
        'tone' => 'is-purple',
        'icon' => '<path d="M5 16V11l2-5h10l2 5v5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M3 16h18v3H3z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="7.5" cy="19" r="1.6" fill="currentColor"/><circle cx="16.5" cy="19" r="1.6" fill="currentColor"/>',
    ],
    'Missing VIN' => [
        'tone' => 'is-danger',
        'icon' => '<path d="M12 3l10 18H2z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M12 10v4M12 17v.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>',
    ],
    'Accepted Jobs' => [
        'tone' => 'is-success',
        'icon' => '<path d="M9 11l3 3 7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12a8 8 0 1 1-3.5-6.6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
    ],
];

// TODO: replace with real per-card historical data when sparkline storage exists.
// These polylines are intentionally decorative placeholders — no claim of actual values.
$sparkPaths = [
    'M0,18 L10,15 L22,17 L34,11 L46,13 L58,9 L70,12 L82,7 L94,9 L106,5 L118,7',
    'M0,12 L10,14 L22,10 L34,13 L46,8  L58,11 L70,6 L82,9 L94,5 L106,8 L118,4',
    'M0,16 L10,13 L22,15 L34,9 L46,12 L58,7 L70,10 L82,6 L94,8 L106,4 L118,6',
    'M0,10 L10,13 L22,9 L34,12 L46,7  L58,10 L70,5 L82,8 L94,4 L106,7 L118,3',
];
$sparkIndex = 0;
?>

<section class="dashboard-greet">
    <div>
        <h2><?= View::e($greeting) ?>, <?= View::e($operatorFirst) ?>.</h2>
        <p>Here is what is happening across your shop today.</p>
    </div>
    <a class="primary-action" href="/service-requests/new">+ New Service Request</a>
</section>

<div class="quick-actions">
    <a href="/intake/new">New Intake</a>
    <a href="/service-requests/new">New Service Request</a>
    <a href="/customers">Customers</a>
    <a href="/vehicles">Vehicles</a>
</div>

<div class="quick-actions secondary-quick-actions">
    <a href="/service-requests">Service Requests</a>
    <a href="/estimates">Estimates</a>
    <a href="/payments">Payments</a>
    <a href="/vendor-documents">Vendor Documents</a>
</div>

<div class="metric-grid">
    <?php foreach ($cards as $label => $value):
        $meta = $cardMeta[$label] ?? ['tone' => '', 'icon' => '<circle cx="12" cy="12" r="3" fill="currentColor"/>'];
        $spark = $sparkPaths[$sparkIndex++ % count($sparkPaths)];
    ?>
        <article class="metric-card">
            <div class="metric-card-top">
                <span class="metric-card-label"><?= View::e($label) ?></span>
                <div class="metric-card-icon <?= View::e($meta['tone']) ?>" aria-hidden="true">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <?= $meta['icon'] ?>
                    </svg>
                </div>
            </div>
            <strong><?= View::e((string) $value) ?></strong>
            <?php /* TODO: render a real sparkline when historical metrics are stored. */ ?>
            <div class="metric-card-spark" aria-hidden="true">
                <svg viewBox="0 0 118 24" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="sparkLine<?= $sparkIndex ?>" x1="0" x2="1" y1="0" y2="0">
                            <stop offset="0" stop-color="currentColor" stop-opacity="0.15"/>
                            <stop offset="0.5" stop-color="currentColor" stop-opacity="0.9"/>
                            <stop offset="1" stop-color="currentColor" stop-opacity="0.4"/>
                        </linearGradient>
                    </defs>
                    <path d="<?= View::e($spark) ?>" fill="none" stroke="url(#sparkLine<?= $sparkIndex ?>)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<div class="dashboard-columns">
    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Latest</p>
                <h2>Recent Intake</h2>
            </div>
            <a class="secondary-action" href="/intake">View all</a>
        </div>
        <?php if (!$latestIntakes): ?>
            <div class="empty-state">
                <h3>No intake yet</h3>
                <p>New phone calls and leads will show here.</p>
                <a class="primary-action" href="/intake/new">+ New Intake</a>
            </div>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($latestIntakes as $intake): ?>
                    <a class="record-row" href="/intake/<?= (int) $intake['id'] ?>">
                        <div class="record-row-main">
                            <strong><?= View::e($intake['intake_number']) ?></strong>
                            <span class="record-row-sub"><?= View::e($intake['service_requested']) ?></span>
                        </div>
                        <span class="record-row-meta">
                            <?= View::e($intake['first_name'] . ' ' . $intake['last_name']) ?>
                            <span class="status-badge"><?= View::e($intake['status']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Latest</p>
                <h2>Service Requests</h2>
            </div>
            <a class="secondary-action" href="/service-requests">View all</a>
        </div>
        <?php if (!$latestRequests): ?>
            <div class="empty-state">
                <h3>No service requests yet</h3>
                <p>Converted intake and direct job records will show here.</p>
                <a class="primary-action" href="/service-requests/new">+ New Service Request</a>
            </div>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($latestRequests as $request): ?>
                    <a class="record-row" href="/service-requests/<?= (int) $request['id'] ?>">
                        <div class="record-row-main">
                            <strong><?= View::e($request['service_request_number']) ?></strong>
                            <span class="record-row-sub"><?= View::e($request['requested_service']) ?></span>
                        </div>
                        <span class="record-row-meta">
                            <?= View::e($request['first_name'] . ' ' . $request['last_name']) ?>
                            <span class="status-badge"><?= View::e($request['status']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel summary-strip">
    <div class="summary-cell">
        <div class="summary-cell-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="9" r="3.4"/>
                <path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
                <path d="M16 11a3 3 0 1 0 0-6M22 19a5.5 5.5 0 0 0-5-4"/>
            </svg>
        </div>
        <div class="summary-cell-text">
            <small>Total customers</small>
            <strong><?= View::e((string) ($cards['Customers'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="summary-cell">
        <div class="summary-cell-icon is-purple" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.5 3.5a3.5 3.5 0 1 1-4 4L4 14l3 3 6.5-6.5a3.5 3.5 0 0 0 4-4z"/>
            </svg>
        </div>
        <div class="summary-cell-text">
            <small>Active jobs</small>
            <strong><?= View::e((string) ($cards['Active Jobs'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="summary-cell">
        <div class="summary-cell-icon is-success" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3 7-7"/>
                <path d="M20 12a8 8 0 1 1-3.5-6.6"/>
            </svg>
        </div>
        <div class="summary-cell-text">
            <small>Accepted jobs</small>
            <strong><?= View::e((string) ($cards['Accepted Jobs'] ?? 0)) ?></strong>
        </div>
    </div>
</div>
