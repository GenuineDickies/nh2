<?php

use App\Core\View;

$customerName = $invoice['first_name'] . ' ' . $invoice['last_name'];
$vehicle = trim(($invoice['year'] ?? '') . ' ' . ($invoice['make'] ?? '') . ' ' . ($invoice['model'] ?? '') . ' ' . ($invoice['color'] ?? '')) ?: 'Vehicle';
$status = (string) ($invoice['status'] ?? 'draft');
$balance = (float) $invoice['balance_due'];
?>
<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Invoice</p>
            <h2><?= View::e($invoice['invoice_number']) ?></h2>
        </div>
        <span class="status-badge<?= $status === 'paid' ? '' : ' status-pending' ?>"><?= View::e(ucwords(str_replace('_', ' ', $status))) ?></span>
    </div>
    <dl class="details">
        <dt>For</dt>
        <dd><?= View::e($customerName) ?></dd>
        <dt>Service</dt>
        <dd><?= View::e($invoice['requested_service']) ?></dd>
        <dt>Vehicle</dt>
        <dd><?= View::e($vehicle) ?></dd>
        <?php if (!empty($invoice['issued_at'])): ?>
            <dt>Issued</dt>
            <dd><?= View::e($invoice['issued_at']) ?></dd>
        <?php endif; ?>
    </dl>
</article>

<article class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Charges</p>
            <h2>Line Items</h2>
        </div>
        <span class="status-badge"><?= count($lines) ?> lines</span>
    </div>
    <?php if (!$lines): ?>
        <p class="muted">No line items on this invoice.</p>
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
        <dd>$<?= View::e(number_format((float) $invoice['subtotal'], 2)) ?></dd>
        <dt>Tax</dt>
        <dd>$<?= View::e(number_format((float) $invoice['tax_total'], 2)) ?></dd>
        <dt>Total</dt>
        <dd>$<?= View::e(number_format((float) $invoice['total'], 2)) ?></dd>
        <dt>Paid</dt>
        <dd>$<?= View::e(number_format((float) $invoice['amount_paid'], 2)) ?></dd>
        <dt><strong>Balance due</strong></dt>
        <dd><strong>$<?= View::e(number_format($balance, 2)) ?></strong></dd>
    </dl>
</article>

<article class="panel">
    <?php if ($balance > 0): ?>
        <p class="eyebrow">Pay</p>
        <h2>How to pay this invoice</h2>
        <p>Please contact the operator to arrange payment. Online payment is coming soon.</p>
    <?php else: ?>
        <p class="eyebrow">Paid</p>
        <h2>This invoice is paid in full</h2>
        <p>Thank you for your business.</p>
    <?php endif; ?>
</article>
