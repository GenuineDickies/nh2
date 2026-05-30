<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Payment</p>
                <h2><?= View::e($payment['payment_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($payment['payment_status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($payment['first_name'] . ' ' . $payment['last_name']) ?></dd>
            <dt>Invoice</dt>
            <dd><a href="/invoices/<?= (int) $payment['invoice_id'] ?>"><?= View::e($payment['invoice_number']) ?></a></dd>
            <dt>Method</dt>
            <dd><?= View::e(ucwords(str_replace('_', ' ', $payment['payment_method']))) ?></dd>
            <dt>Amount</dt>
            <dd>$<?= View::e(number_format((float) $payment['amount'], 2)) ?></dd>
            <dt>Reference</dt>
            <dd><?= View::e($payment['transaction_reference'] ?: 'None') ?></dd>
            <dt>Paid At</dt>
            <dd><?= View::e($payment['paid_at']) ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Receipt</p>
                <h2><?= View::e($payment['receipt_number'] ?: 'Missing') ?></h2>
            </div>
        </div>
        <?php if ($payment['receipt_id']): ?>
            <a class="primary-action" href="/receipts/<?= (int) $payment['receipt_id'] ?>">View Receipt</a>
        <?php endif; ?>
    </aside>
</div>
