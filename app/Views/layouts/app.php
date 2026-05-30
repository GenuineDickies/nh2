<?php

use App\Core\Auth;
use App\Core\View;
use App\Services\SquareConfig;

$currentUser = Auth::user();

// Square runs against its Sandbox (test) environment whenever SQUARE_ENVIRONMENT
// is "sandbox". Surface that app-wide with a scrolling banner so nobody mistakes
// test payments for live ones.
$isSquareSandbox = (new SquareConfig())->activeEnvironment() === 'sandbox';

$nav = [
    'dashboard' => ['Dashboard', '/dashboard'],
    'intake' => ['Intake', '/intake'],
    'service-requests' => ['Service Requests', '/service-requests'],
    'estimates' => ['Estimates', '/estimates'],
    'work-orders' => ['Work Orders', '/work-orders'],
    'invoices' => ['Invoices', '/invoices'],
    'payments' => ['Payments', '/payments'],
    'customers' => ['Customers', '/customers'],
    'vehicles' => ['Vehicles', '/vehicles'],
    'catalog' => ['Service Catalog', '/catalog/services'],
    'vendors' => ['Vendors', '/vendors'],
    'vendor-documents' => ['Vendor Documents', '/vendor-documents'],
    'document-intake' => ['Document Intake (AI)', '/document-intake'],
    'accounting' => ['Accounting', '/accounting/ledger'],
    'reports' => ['Reports', '/reports'],
    'settings' => ['Settings', '/admin/settings/square'],
];

// Inline SVG paths for each nav entry. Keep these small and stroke-based so
// they inherit currentColor and stay sharp on dark backgrounds.
$navIcons = [
    'dashboard' => '<path d="M3 13h7V3H3v10zm0 8h7v-6H3v6zm11 0h7V11h-7v10zm0-18v6h7V3h-7z"/>',
    'intake' => '<path d="M4 4h12l4 4v12H4z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M16 4v4h4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 13h8M8 17h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'service-requests' => '<path d="M3 12a9 9 0 0 1 15.5-6.3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M21 12a9 9 0 0 1-15.5 6.3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M18 3v5h-5M6 21v-5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
    'estimates' => '<path d="M5 3h11l3 3v15H5z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 9h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'work-orders' => '<path d="M14.5 3.5a3.5 3.5 0 1 1-4 4L4 14l3 3 6.5-6.5a3.5 3.5 0 0 0 4-4z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
    'invoices' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'payments' => '<path d="M2 7h20v10H2z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M2 11h20" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="14.5" r="1.4" fill="currentColor"/>',
    'customers' => '<circle cx="9" cy="9" r="3.4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16 11a3 3 0 1 0 0-6M22 19a5.5 5.5 0 0 0-5-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'vehicles' => '<path d="M5 16V11l2-5h10l2 5v5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 16h18v3H3z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="7.5" cy="19" r="1.5" fill="currentColor"/><circle cx="16.5" cy="19" r="1.5" fill="currentColor"/>',
    'catalog' => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" fill="none" stroke="currentColor" stroke-width="1.6"/>',
    'vendors' => '<path d="M3 7h18v4H3zM5 11v9h14v-9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M10 11v9M14 11v9M3 7l2-3h14l2 3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
    'vendor-documents' => '<path d="M6 2h9l4 4v16H6z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M15 2v4h4" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M9 11h7M9 15h7M9 19h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'document-intake' => '<path d="M12 3l9 5-9 5-9-5z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3 13l9 5 9-5M3 17l9 5 9-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>',
    'accounting' => '<path d="M4 4h16v16H4z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4 9h16M9 4v16" stroke="currentColor" stroke-width="1.6"/><path d="M12.5 13l1.8 2.4 2.5-3.2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
    'reports' => '<path d="M4 20V8M10 20V4M16 20v-7M22 20H2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
    'settings' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M19.4 13a7.5 7.5 0 0 0 0-2l2-1.5-2-3.4-2.4.9a7.4 7.4 0 0 0-1.8-1L14.7 3h-3.4l-.5 2.6a7.4 7.4 0 0 0-1.8 1L6.6 5.7l-2 3.4L6.6 11a7.5 7.5 0 0 0 0 2l-2 1.5 2 3.4 2.4-.9a7.4 7.4 0 0 0 1.8 1l.5 2.6h3.4l.5-2.6a7.4 7.4 0 0 0 1.8-1l2.4.9 2-3.4z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Solo Roadside') ?></title>
    <link rel="stylesheet" href="<?= View::e(View::asset('assets/css/app.css')) ?>">
</head>
<body>
<div class="app-shell<?= $isSquareSandbox ? ' has-env-banner' : '' ?>">
    <?php if ($isSquareSandbox): ?>
        <div class="env-banner" role="note" aria-label="Square sandbox mode is active — payments are not real">
            <div class="env-banner-track" aria-hidden="true">
                <?php $envBannerGroup = str_repeat('<span class="env-banner-item">Sandbox Mode</span>', 18); ?>
                <div class="env-banner-group"><?= $envBannerGroup ?></div>
                <div class="env-banner-group"><?= $envBannerGroup ?></div>
            </div>
        </div>
    <?php endif; ?>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="/dashboard">
            <span class="brand-mark">SR</span>
            <div>
                <strong>Solo Roadside</strong>
                <small>Command center</small>
            </div>
        </a>

        <p class="nav-section-title">Operate</p>
        <nav class="nav-list" aria-label="Main navigation">
            <?php foreach ($nav as $key => [$label, $href]): ?>
                <a class="<?= ($active ?? '') === $key ? 'active' : '' ?> <?= $href === '#' ? 'disabled' : '' ?>" href="<?= View::e($href) ?>">
                    <span class="nav-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <?= $navIcons[$key] ?? '<circle cx="12" cy="12" r="3" />' ?>
                        </svg>
                    </span>
                    <?= View::e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button type="button" class="sidebar-toggle" aria-label="Open navigation" aria-controls="sidebar" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
            <div class="topbar-title">
                <p class="eyebrow">Solo operator command center</p>
                <h1><?= View::e($title ?? 'Dashboard') ?></h1>
            </div>

            <form class="topbar-search" method="get" action="/service-requests" role="search">
                <span class="topbar-search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="6.5"/>
                        <path d="m20 20-3.5-3.5"/>
                    </svg>
                </span>
                <input type="search" name="q" placeholder="Search service requests, customers, jobs..." aria-label="Global search">
                <kbd class="kbd-hint">CTRL K</kbd>
            </form>

            <div class="topbar-actions">
                <?php /* TODO: in-app notifications feed not implemented yet */ ?>
                <button type="button" class="icon-button" aria-label="Notifications" aria-disabled="true" title="Notifications (coming soon)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9z"/>
                        <path d="M10 21a2 2 0 0 0 4 0"/>
                    </svg>
                    <span class="icon-dot" aria-hidden="true"></span>
                </button>
                <a class="icon-button" href="/admin/settings/square" aria-label="Settings" title="Settings">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 13a7.5 7.5 0 0 0 0-2l2-1.5-2-3.4-2.4.9a7.4 7.4 0 0 0-1.8-1L14.7 3h-3.4l-.5 2.6a7.4 7.4 0 0 0-1.8 1L6.6 5.7l-2 3.4L6.6 11a7.5 7.5 0 0 0 0 2l-2 1.5 2 3.4 2.4-.9a7.4 7.4 0 0 0 1.8 1l.5 2.6h3.4l.5-2.6a7.4 7.4 0 0 0 1.8-1l2.4.9 2-3.4z"/>
                    </svg>
                </a>

                <?php if ($currentUser): ?>
                    <span class="user-chip">
                        <a href="/profile" title="Open your profile"><?= View::e($currentUser['name']) ?></a>
                        <form method="post" action="/logout">
                            <button type="submit">Sign out</button>
                        </form>
                    </span>
                <?php endif; ?>

                <a class="primary-action" href="/intake/new">+ New Intake</a>
            </div>
        </header>

        <section class="content">
            <?= View::render($content, get_defined_vars()) ?>
        </section>
    </main>
</div>
<script src="<?= View::e(View::asset('assets/js/app.js')) ?>"></script>
</body>
</html>
