<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Take Payment</p>
                <h2><?= View::e($invoice['invoice_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($invoice['status']) ?></span>
        </div>

        <?php if ($errors): ?>
            <div class="alert">
                <?= View::e(implode(' ', $errors)) ?>
            </div>
        <?php endif; ?>

        <form class="form-grid" method="post" action="/payments">
            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
            <label>
                Payment method
                <select name="payment_method" required>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?= View::e($method) ?>" <?= $values['payment_method'] === $method ? 'selected' : '' ?>>
                            <?= View::e(ucwords(str_replace('_', ' ', $method))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Amount
                <input name="amount" type="number" step="0.01" min="0.01" max="<?= View::e(number_format((float) $invoice['balance_due'], 2, '.', '')) ?>" value="<?= View::e((string) $values['amount']) ?>" required>
            </label>
            <label>
                Reference
                <input name="transaction_reference" value="<?= View::e((string) $values['transaction_reference']) ?>" placeholder="Check number, card note, Square ID">
            </label>
            <div class="sticky-actions">
                <a class="secondary-action" href="/invoices/<?= (int) $invoice['id'] ?>">Cancel</a>
                <button class="primary-action" type="submit">Record Payment</button>
            </div>
        </form>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Balance Due</p>
                <h2>$<?= View::e(number_format((float) $invoice['balance_due'], 2)) ?></h2>
            </div>
        </div>
        <dl class="details compact-details">
            <dt>Customer</dt>
            <dd><?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></dd>
            <dt>Total</dt>
            <dd>$<?= View::e(number_format((float) $invoice['total'], 2)) ?></dd>
            <dt>Paid</dt>
            <dd>$<?= View::e(number_format((float) $invoice['amount_paid'], 2)) ?></dd>
            <dt>Status</dt>
            <dd><?= View::e($invoice['status']) ?></dd>
        </dl>
    </aside>
</div>
