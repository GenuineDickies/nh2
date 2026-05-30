<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Receipt</p>
                <h2><?= View::e($receipt['receipt_number']) ?></h2>
            </div>
            <span class="status-badge">completed</span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($receipt['first_name'] . ' ' . $receipt['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><?= View::e($receipt['phone']) ?></dd>
            <dt>Invoice</dt>
            <dd><a href="/invoices/<?= (int) $receipt['invoice_id'] ?>"><?= View::e($receipt['invoice_number']) ?></a></dd>
            <dt>Payment</dt>
            <dd><?= View::e($receipt['payment_number']) ?></dd>
            <dt>Method</dt>
            <dd><?= View::e(ucwords(str_replace('_', ' ', $receipt['payment_method']))) ?></dd>
            <dt>Amount Paid</dt>
            <dd>$<?= View::e(number_format((float) $receipt['amount'], 2)) ?></dd>
            <dt>Paid At</dt>
            <dd><?= View::e($receipt['paid_at']) ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Invoice Balance</p>
                <h2>$<?= View::e(number_format((float) $receipt['balance_due'], 2)) ?></h2>
            </div>
        </div>
        <dl class="details compact-details">
            <dt>Total</dt>
            <dd>$<?= View::e(number_format((float) $receipt['total'], 2)) ?></dd>
            <dt>Paid</dt>
            <dd>$<?= View::e(number_format((float) $receipt['amount_paid'], 2)) ?></dd>
        </dl>
        <div class="stacked-actions">
            <form method="post" action="/receipts/<?= (int) $receipt['id'] ?>/documents/generate">
                <button class="secondary-action" type="submit">Generate PDF</button>
            </form>
        </div>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Documents</p>
            <h2>Generated Records</h2>
        </div>
        <span class="status-badge"><?= count($documents) ?> total</span>
    </div>
    <?= View::render('partials/document_list', ['documents' => $documents]) ?>
</div>
