<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Revenue by Date</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            <h3>No issued revenue yet</h3>
            <p>Issued invoices will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoices</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= View::e($row['report_date']) ?></td>
                            <td><?= (int) $row['invoice_count'] ?></td>
                            <td>$<?= View::e(number_format((float) $row['subtotal'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $row['tax_total'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $row['total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
