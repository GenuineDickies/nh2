<?php

use App\Core\View;
?>
<div class="metric-grid">
    <article class="metric-card">
        <span>Issued Revenue</span>
        <strong>$<?= View::e(number_format((float) $summary['revenue'], 2)) ?></strong>
    </article>
    <article class="metric-card">
        <span>Payments Collected</span>
        <strong>$<?= View::e(number_format((float) $summary['payments'], 2)) ?></strong>
    </article>
    <article class="metric-card">
        <span>Unpaid Balance</span>
        <strong>$<?= View::e(number_format((float) $summary['unpaid'], 2)) ?></strong>
    </article>
    <article class="metric-card">
        <span>Open Invoices</span>
        <strong><?= (int) $summary['open_invoices'] ?></strong>
    </article>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Business Views</h2>
        </div>
        <span class="status-badge"><?= (int) $summary['jobs_missing_records'] ?> jobs missing records</span>
    </div>
    <div class="quick-actions">
        <a href="/reports/revenue">Revenue</a>
        <a href="/reports/payments">Payments</a>
        <a href="/reports/unpaid">Unpaid Invoices</a>
        <a href="/reports/gross-margin">Gross Margin</a>
        <a href="/reports/lead-sources">Lead Sources</a>
        <a href="/reports/tax-summary">Tax Summary</a>
        <a href="/reports/missing-records">Missing Records</a>
    </div>
</div>
