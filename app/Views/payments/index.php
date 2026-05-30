<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Money In</p>
            <h2>Payments</h2>
        </div>
        <span class="status-badge"><?= count($payments) ?> total</span>
    </div>

    <?php if (!$payments): ?>
        <div class="empty-state">
            <h3>No payments yet</h3>
            <p>Issue an invoice, then record the customer payment here.</p>
            <a class="primary-action" href="/invoices">Open Invoices</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><a href="/payments/<?= (int) $payment['id'] ?>"><?= View::e($payment['payment_number']) ?></a></td>
                            <td><?= View::e($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
                            <td><a href="/invoices/<?= (int) $payment['invoice_id'] ?>"><?= View::e($payment['invoice_number']) ?></a></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $payment['payment_method']))) ?></td>
                            <td>$<?= View::e(number_format((float) $payment['amount'], 2)) ?></td>
                            <td>
                                <?php if ($payment['receipt_id']): ?>
                                    <a href="/receipts/<?= (int) $payment['receipt_id'] ?>"><?= View::e($payment['receipt_number']) ?></a>
                                <?php else: ?>
                                    Missing
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
