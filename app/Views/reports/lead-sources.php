<?php

use App\Core\View;

$totals = ['jobs' => 0, 'invoices' => 0, 'revenue' => 0.0];
foreach ($rows as $row) {
    $totals['jobs'] += (int) $row['job_count'];
    $totals['invoices'] += (int) $row['invoice_count'];
    $totals['revenue'] += (float) $row['revenue_total'];
}
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Lead Source Revenue</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            <h3>No jobs recorded yet</h3>
            <p>Create a service request and issue an invoice to see lead source revenue here.</p>
        </div>
    <?php else: ?>
        <div class="metric-grid">
            <article class="metric-card">
                <span>Jobs</span>
                <strong><?= (int) $totals['jobs'] ?></strong>
            </article>
            <article class="metric-card">
                <span>Invoiced Jobs</span>
                <strong><?= (int) $totals['invoices'] ?></strong>
            </article>
            <article class="metric-card">
                <span>Total Revenue</span>
                <strong>$<?= View::e(number_format($totals['revenue'], 2)) ?></strong>
            </article>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Lead Source</th>
                        <th>Jobs</th>
                        <th>Invoiced</th>
                        <th>Revenue (ex tax)</th>
                        <th>Tax</th>
                        <th>Revenue (incl tax)</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $rev = (float) $row['revenue_total'];
                            $share = $totals['revenue'] > 0 ? ($rev / $totals['revenue']) * 100 : null;
                        ?>
                        <tr>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $row['lead_source']))) ?></td>
                            <td><?= (int) $row['job_count'] ?></td>
                            <td><?= (int) $row['invoice_count'] ?></td>
                            <td>$<?= View::e(number_format((float) $row['revenue_subtotal'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $row['revenue_tax'], 2)) ?></td>
                            <td>$<?= View::e(number_format($rev, 2)) ?></td>
                            <td><?= $share === null ? '--' : View::e(number_format($share, 1)) . '%' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
