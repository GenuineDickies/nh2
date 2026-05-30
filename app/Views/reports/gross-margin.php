<?php

use App\Core\View;

$totals = ['revenue' => 0.0, 'cost' => 0.0, 'margin' => 0.0];
foreach ($rows as $row) {
    $totals['revenue'] += (float) $row['revenue_subtotal'];
    $totals['cost'] += (float) $row['parts_cost'];
    $totals['margin'] += (float) $row['gross_margin'];
}
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Gross Margin by Job</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            <h3>No invoiced jobs yet</h3>
            <p>Issue an invoice and post a vendor document with line items linked to a service request to see margin here.</p>
        </div>
    <?php else: ?>
        <div class="metric-grid">
            <article class="metric-card">
                <span>Revenue (ex tax)</span>
                <strong>$<?= View::e(number_format($totals['revenue'], 2)) ?></strong>
            </article>
            <article class="metric-card">
                <span>Parts & Materials Cost</span>
                <strong>$<?= View::e(number_format($totals['cost'], 2)) ?></strong>
            </article>
            <article class="metric-card">
                <span>Total Gross Margin</span>
                <strong>$<?= View::e(number_format($totals['margin'], 2)) ?></strong>
            </article>
            <article class="metric-card">
                <span>Margin Rate</span>
                <strong><?= $totals['revenue'] > 0 ? View::e(number_format(($totals['margin'] / $totals['revenue']) * 100, 1)) . '%' : '--' ?></strong>
            </article>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Job</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Revenue (ex tax)</th>
                        <th>Parts Cost</th>
                        <th>Margin</th>
                        <th>Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $revenue = (float) $row['revenue_subtotal'];
                            $cost = (float) $row['parts_cost'];
                            $margin = (float) $row['gross_margin'];
                            $rate = $revenue > 0 ? ($margin / $revenue) * 100 : null;
                        ?>
                        <tr>
                            <td><a href="/service-requests/<?= (int) $row['service_request_id'] ?>"><?= View::e($row['service_request_number']) ?></a></td>
                            <td><?= View::e(trim($row['first_name'] . ' ' . $row['last_name'])) ?></td>
                            <td><?= View::e($row['requested_service']) ?></td>
                            <td>$<?= View::e(number_format($revenue, 2)) ?></td>
                            <td>$<?= View::e(number_format($cost, 2)) ?></td>
                            <td>$<?= View::e(number_format($margin, 2)) ?></td>
                            <td><?= $rate === null ? '--' : View::e(number_format($rate, 1)) . '%' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted">Cost is the sum of posted vendor document lines (resold parts, inventory parts, consumables, materials) linked to the service request.</p>
    <?php endif; ?>
</div>
