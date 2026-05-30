<?php

use App\Core\View;

$status = (string) ($estimate['status'] ?? 'draft');
$open = !in_array($status, ['approved', 'declined', 'expired', 'converted'], true);
$customerName = $estimate['first_name'] . ' ' . $estimate['last_name'];
$vehicle = trim(($estimate['year'] ?? '') . ' ' . ($estimate['make'] ?? '') . ' ' . ($estimate['model'] ?? '') . ' ' . ($estimate['color'] ?? '')) ?: 'Vehicle';
?>
<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Estimate</p>
            <h2><?= View::e($estimate['estimate_number']) ?></h2>
        </div>
        <span class="status-badge<?= $status === 'approved' ? '' : ' status-pending' ?>"><?= View::e(ucwords(str_replace('_', ' ', $status))) ?></span>
    </div>

    <?php if ($flash !== null): ?>
        <div class="alert public-flash"><?= View::e($flash) ?></div>
    <?php endif; ?>

    <dl class="details">
        <dt>For</dt>
        <dd><?= View::e($customerName) ?></dd>
        <dt>Service</dt>
        <dd><?= View::e($estimate['requested_service']) ?></dd>
        <dt>Vehicle</dt>
        <dd><?= View::e($vehicle) ?></dd>
    </dl>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Line Items</p>
            <h2>Charges</h2>
        </div>
        <span class="status-badge"><?= count($lines) ?> lines</span>
    </div>
    <?php if (!$lines): ?>
        <p class="muted">No line items have been added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td><?= View::e($line['description']) ?></td>
                            <td><?= View::e(number_format((float) $line['quantity'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $line['unit_price'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $line['line_subtotal'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <dl class="details compact-details public-totals">
        <dt>Subtotal</dt>
        <dd>$<?= View::e(number_format((float) $estimate['subtotal'], 2)) ?></dd>
        <dt>Tax</dt>
        <dd>$<?= View::e(number_format((float) $estimate['tax_total'], 2)) ?></dd>
        <dt><strong>Total</strong></dt>
        <dd><strong>$<?= View::e(number_format((float) $estimate['total'], 2)) ?></strong></dd>
    </dl>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Disclaimer</p>
            <h2>Please read</h2>
        </div>
    </div>
    <p><?= View::e($estimate['disclaimer_text']) ?></p>
</article>

<?php if ($open && $token !== ''): ?>
    <form class="panel" method="post" action="/p/estimate/<?= View::e($token) ?>/approve">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Approve</p>
                <h2>Authorize this estimate</h2>
            </div>
        </div>
        <label>Your name
            <input name="customer_name" value="<?= View::e($customerName) ?>" required>
            <?php if (isset($errors['customer_name'])): ?><small class="field-error"><?= View::e($errors['customer_name']) ?></small><?php endif; ?>
        </label>
        <label class="inline-check">
            <input type="checkbox" name="agreed" value="1">
            <span>I have read and agree to the disclaimer above.</span>
        </label>
        <?php if (isset($errors['agreed'])): ?><small class="field-error"><?= View::e($errors['agreed']) ?></small><?php endif; ?>
        <div class="inline-actions">
            <button class="primary-action" type="submit">Approve Estimate</button>
        </div>
    </form>
    <form class="panel" method="post" action="/p/estimate/<?= View::e($token) ?>/decline" onsubmit="return confirm('Decline this estimate?');">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Decline</p>
                <h2>Not the right time</h2>
            </div>
        </div>
        <p class="muted">If this doesn't look right, you can decline and the operator will follow up.</p>
        <div class="inline-actions">
            <button class="secondary-action" type="submit">Decline Estimate</button>
        </div>
    </form>
<?php elseif (!$open): ?>
    <article class="panel">
        <p class="muted">This estimate is currently <?= View::e($status) ?>. If you need a change, please contact the operator.</p>
    </article>
<?php endif; ?>
