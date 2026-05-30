<?php

use App\Core\View;

$totals = ['collected' => 0.0, 'paid' => 0.0, 'net' => 0.0];
foreach ($rows as $row) {
    $totals['collected'] += (float) $row['tax_collected'];
    $totals['paid'] += (float) $row['tax_paid'];
    $totals['net'] += (float) $row['net'];
}
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Tax Summary</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            <h3>No tax activity yet</h3>
            <p>Issued invoices and posted vendor documents with sales tax will appear here.</p>
        </div>
    <?php else: ?>
        <div class="metric-grid">
            <article class="metric-card">
                <span>Tax Collected</span>
                <strong>$<?= View::e(number_format($totals['collected'], 2)) ?></strong>
            </article>
            <article class="metric-card">
                <span>Tax Paid to Vendors</span>
                <strong>$<?= View::e(number_format($totals['paid'], 2)) ?></strong>
            </article>
            <article class="metric-card">
                <span>Net</span>
                <strong>$<?= View::e(number_format($totals['net'], 2)) ?></strong>
            </article>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Invoices</th>
                        <th>Tax Collected</th>
                        <th>Vendor Docs</th>
                        <th>Tax Paid</th>
                        <th>Net</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= View::e($row['month_key']) ?></td>
                            <td><?= (int) $row['invoice_count'] ?></td>
                            <td>$<?= View::e(number_format((float) $row['tax_collected'], 2)) ?></td>
                            <td><?= (int) $row['document_count'] ?></td>
                            <td>$<?= View::e(number_format((float) $row['tax_paid'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $row['net'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted">Tax collected is the sales tax on issued invoices. Tax paid is sales tax on posted vendor documents (currently routed to 6050 Office/Admin in the ledger).</p>
    <?php endif; ?>
</div>
