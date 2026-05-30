<?php

use App\Core\View;

$vehicle = trim(($invoice['year'] ?? '') . ' ' . ($invoice['make'] ?? '') . ' ' . ($invoice['model'] ?? '') . ' ' . ($invoice['color'] ?? '')) ?: 'Not captured';
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Invoice</p>
                <h2><?= View::e($invoice['invoice_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($invoice['status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($invoice['phone']) ?>"><?= View::e($invoice['phone']) ?></a></dd>
            <dt>Service Request</dt>
            <dd><?= View::e($invoice['service_request_number']) ?></dd>
            <dt>Service Report</dt>
            <dd><a href="/service-reports/<?= (int) $invoice['service_completion_report_id'] ?>"><?= View::e($invoice['report_number']) ?></a></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e($vehicle) ?></dd>
            <dt>VIN</dt>
            <dd><?= View::e($invoice['vin'] ?: 'Missing') ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Balance</p>
                <h2>$<?= View::e(number_format((float) $invoice['balance_due'], 2)) ?></h2>
            </div>
        </div>
        <dl class="details compact-details">
            <dt>Subtotal</dt>
            <dd>$<?= View::e(number_format((float) $invoice['subtotal'], 2)) ?></dd>
            <dt>Tax</dt>
            <dd>$<?= View::e(number_format((float) $invoice['tax_total'], 2)) ?></dd>
            <dt>Total</dt>
            <dd>$<?= View::e(number_format((float) $invoice['total'], 2)) ?></dd>
            <dt>Paid</dt>
            <dd>$<?= View::e(number_format((float) $invoice['amount_paid'], 2)) ?></dd>
            <dt>Issued</dt>
            <dd><?= View::e($invoice['issued_at'] ?: 'Not sent') ?></dd>
        </dl>
        <div class="stacked-actions">
            <?php if (!$validationErrors && $invoice['status'] === 'draft'): ?>
                <form method="post" action="/invoices/<?= (int) $invoice['id'] ?>/issue">
                    <button class="primary-action" type="submit">Issue Invoice</button>
                </form>
            <?php endif; ?>
            <?php if (in_array($invoice['status'], ['sent', 'partially_paid'], true) && (float) $invoice['balance_due'] > 0): ?>
                <a class="primary-action" href="/payments/new?invoice_id=<?= (int) $invoice['id'] ?>">Take Payment</a>
            <?php endif; ?>
            <form method="post" action="/invoices/<?= (int) $invoice['id'] ?>/documents/generate">
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

<?php if ($validationErrors): ?>
    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Validation</p>
                <h2>Before Sending</h2>
            </div>
            <span class="status-badge status-pending"><?= count($validationErrors) ?> issues</span>
        </div>
        <ul class="validation-list">
            <?php foreach ($validationErrors as $error): ?>
                <li><?= View::e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Customer Portal</p>
            <h2>Public Invoice Link</h2>
        </div>
    </div>
    <?php
        $portalScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $portalBase = $portalScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $portalUrl = $publicToken ? $portalBase . '/p/invoice/' . $publicToken['token'] : null;
        $expiresAt = $publicToken['expires_at'] ?? null;
    ?>
    <?php if ($portalUrl): ?>
        <p class="muted">Send this link to the customer. They can view the invoice and balance without logging in.</p>
        <label>Link
            <input type="text" readonly value="<?= View::e($portalUrl) ?>" onclick="this.select()">
        </label>
        <p class="muted">
            <?php if ($expiresAt && strtotime((string) $expiresAt) < time()): ?>
                Expired <?= View::e($expiresAt) ?> &mdash; generate a new one.
            <?php elseif ($expiresAt): ?>
                Expires <?= View::e($expiresAt) ?>. This link stays active across multiple visits.
            <?php endif; ?>
        </p>
        <form method="post" action="/invoices/<?= (int) $invoice['id'] ?>/public-link">
            <button class="secondary-action" type="submit">Generate New Link</button>
        </form>
    <?php else: ?>
        <p class="muted">No public link generated yet. Generate one to text or email the invoice to the customer.</p>
        <form method="post" action="/invoices/<?= (int) $invoice['id'] ?>/public-link">
            <button class="primary-action" type="submit">Generate Public Link</button>
        </form>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Line Items</p>
            <h2>Invoice Lines</h2>
        </div>
        <span class="status-badge"><?= count($lines) ?> lines</span>
    </div>
    <?php if (!$lines): ?>
        <p class="muted">No invoice line items yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Taxable</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td><?= View::e($line['description']) ?></td>
                            <td><?= View::e(ucwords($line['line_type'])) ?></td>
                            <td><?= View::e(number_format((float) $line['quantity'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $line['unit_price'], 2)) ?></td>
                            <td><?= ((int) $line['taxable']) === 1 ? 'Yes' : 'No' ?></td>
                            <td>$<?= View::e(number_format((float) $line['line_subtotal'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
