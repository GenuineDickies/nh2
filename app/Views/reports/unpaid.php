<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Unpaid Invoices</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$invoices): ?>
        <div class="empty-state">
            <h3>No unpaid invoices</h3>
            <p>Sent invoices with open balances will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Service Request</th>
                        <th>Issued</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><a href="/invoices/<?= (int) $invoice['id'] ?>"><?= View::e($invoice['invoice_number']) ?></a></td>
                            <td><?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></td>
                            <td><?= View::e($invoice['phone']) ?></td>
                            <td><?= View::e($invoice['service_request_number']) ?></td>
                            <td><?= View::e($invoice['issued_at'] ?: $invoice['created_at']) ?></td>
                            <td>$<?= View::e(number_format((float) $invoice['balance_due'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
