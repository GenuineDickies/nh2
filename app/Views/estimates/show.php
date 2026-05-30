<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Estimate</p>
                <h2><?= View::e($estimate['estimate_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($estimate['status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($estimate['first_name'] . ' ' . $estimate['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><a href="tel:<?= View::e($estimate['phone']) ?>"><?= View::e($estimate['phone']) ?></a></dd>
            <dt>Service Request</dt>
            <dd><a href="/service-requests/<?= (int) $estimate['service_request_id'] ?>"><?= View::e($estimate['service_request_number']) ?></a></dd>
            <dt>Service</dt>
            <dd><?= View::e($estimate['requested_service']) ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e(trim(($estimate['year'] ?? '') . ' ' . ($estimate['make'] ?? '') . ' ' . ($estimate['model'] ?? '') . ' ' . ($estimate['color'] ?? '')) ?: 'Not captured') ?></dd>
        </dl>
        <div class="stacked-actions">
            <form method="post" action="/estimates/<?= (int) $estimate['id'] ?>/documents/generate">
                <button class="secondary-action" type="submit">Generate PDF</button>
            </form>
        </div>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Totals</p>
                <h2>$<?= View::e(number_format((float) $estimate['total'], 2)) ?></h2>
            </div>
            <?php if ($approvalRequired): ?>
                <span class="status-badge status-pending">Approval Required</span>
            <?php endif; ?>
        </div>
        <dl class="details compact-details">
            <dt>Subtotal</dt>
            <dd>$<?= View::e(number_format((float) $estimate['subtotal'], 2)) ?></dd>
            <dt>Tax</dt>
            <dd>$<?= View::e(number_format((float) $estimate['tax_total'], 2)) ?></dd>
            <dt>Total</dt>
            <dd>$<?= View::e(number_format((float) $estimate['total'], 2)) ?></dd>
        </dl>
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

<div class="detail-grid">
    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Status</p>
                <h2>Estimate Actions</h2>
            </div>
        </div>
        <div class="status-actions">
            <form method="post" action="/estimates/<?= (int) $estimate['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="sent">Mark Sent</button>
            </form>
            <form method="post" action="/estimates/<?= (int) $estimate['id'] ?>/status">
                <button class="secondary-action" type="submit" name="status" value="declined">Mark Declined</button>
            </form>
            <?php if ($estimate['status'] === 'approved'): ?>
                <form method="post" action="/work-orders/from-estimate/<?= (int) $estimate['id'] ?>">
                    <button class="primary-action" type="submit">Create Work Order</button>
                </form>
            <?php endif; ?>
        </div>
    </aside>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Customer Portal</p>
                <h2>Public Approval Link</h2>
            </div>
        </div>
        <?php
            $portalScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $portalHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $portalBase = $portalScheme . '://' . $portalHost;
            $portalUrl = $publicToken ? $portalBase . '/p/estimate/' . $publicToken['token'] : null;
            $expiresAt = $publicToken['expires_at'] ?? null;
            $usedAt = $publicToken['used_at'] ?? null;
        ?>
        <?php if ($portalUrl): ?>
            <p class="muted">Send this link to the customer. They can approve or decline without logging in.</p>
            <label>Link
                <input type="text" readonly value="<?= View::e($portalUrl) ?>" onclick="this.select()">
            </label>
            <p class="muted">
                <?php if ($usedAt): ?>
                    Used <?= View::e($usedAt) ?> &mdash; this link is no longer active. Generate a new one to send a fresh link.
                <?php elseif ($expiresAt && strtotime((string) $expiresAt) < time()): ?>
                    Expired <?= View::e($expiresAt) ?> &mdash; generate a new one.
                <?php elseif ($expiresAt): ?>
                    Expires <?= View::e($expiresAt) ?>.
                <?php endif; ?>
            </p>
            <form method="post" action="/estimates/<?= (int) $estimate['id'] ?>/public-link">
                <button class="secondary-action" type="submit">Generate New Link</button>
            </form>
        <?php else: ?>
            <p class="muted">No public link generated yet. Once you generate one, the URL will appear here so you can text or email it.</p>
            <form method="post" action="/estimates/<?= (int) $estimate['id'] ?>/public-link">
                <button class="primary-action" type="submit">Generate Public Link</button>
            </form>
        <?php endif; ?>
    </aside>

    <form class="panel" method="post" action="/estimates/<?= (int) $estimate['id'] ?>/approve">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Approval</p>
                <h2>Record Customer Approval</h2>
            </div>
        </div>
        <?php if ($approvalErrors): ?>
            <div class="alert">Please complete the approval fields.</div>
        <?php endif; ?>
        <label>Customer name
            <input name="customer_name" value="<?= View::e($estimate['first_name'] . ' ' . $estimate['last_name']) ?>">
            <?php if (isset($approvalErrors['customer_name'])): ?><small class="field-error"><?= View::e($approvalErrors['customer_name']) ?></small><?php endif; ?>
        </label>
        <label>Approval method
            <select name="approval_method">
                <option value="phone_confirmed">Phone Confirmed</option>
                <option value="onsite_signature">Onsite Signature</option>
                <option value="sms_link">SMS Link</option>
                <option value="email_link">Email Link</option>
            </select>
            <?php if (isset($approvalErrors['approval_method'])): ?><small class="field-error"><?= View::e($approvalErrors['approval_method']) ?></small><?php endif; ?>
        </label>
        <button class="primary-action" type="submit">Approve Estimate</button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Line Items</p>
            <h2>Estimate Lines</h2>
        </div>
        <span class="status-badge"><?= count($lines) ?> lines</span>
    </div>

    <?php if (!$lines): ?>
        <p class="muted">No line items yet.</p>
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

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Authorization</p>
            <h2>Approval Records</h2>
        </div>
        <span class="status-badge"><?= count($approvals) ?> total</span>
    </div>
    <?php if (!$approvals): ?>
        <p class="muted">No approval records yet.</p>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($approvals as $approval): ?>
                <div class="record-row">
                    <strong><?= View::e($approval['approval_number'] . ' - ' . $approval['customer_name']) ?></strong>
                    <span><?= View::e(ucwords(str_replace('_', ' ', $approval['approval_method'])) . ' at ' . $approval['approved_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="detail-grid">
    <form class="panel" method="post" action="/estimates/<?= (int) $estimate['id'] ?>/lines">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Catalog</p>
                <h2>Add Catalog Line</h2>
            </div>
        </div>
        <?php if (isset($errors['catalog_item_id'])): ?><div class="alert"><?= View::e($errors['catalog_item_id']) ?></div><?php endif; ?>
        <label>Catalog item
            <select name="catalog_item_id">
                <option value="">Choose item</option>
                <?php foreach ($catalogItems as $item): ?>
                    <?php if ($item['status'] === 'active'): ?>
                        <option value="<?= (int) $item['id'] ?>"><?= View::e($item['name'] . ' - $' . number_format((float) $item['price'], 2)) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity
            <input name="quantity" inputmode="decimal" value="1">
            <?php if (isset($errors['quantity'])): ?><small class="field-error"><?= View::e($errors['quantity']) ?></small><?php endif; ?>
        </label>
        <button class="primary-action" type="submit">Add Catalog Line</button>
    </form>

    <form class="panel" method="post" action="/estimates/<?= (int) $estimate['id'] ?>/lines">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Custom</p>
                <h2>Add Custom Line</h2>
            </div>
        </div>
        <input type="hidden" name="line_type" value="custom">
        <label>Description
            <input name="description">
            <?php if (isset($errors['description'])): ?><small class="field-error"><?= View::e($errors['description']) ?></small><?php endif; ?>
        </label>
        <div class="fields three">
            <label>Quantity
                <input name="quantity" inputmode="decimal" value="1">
            </label>
            <label>Unit price
                <input name="unit_price" inputmode="decimal" value="0.00">
                <?php if (isset($errors['unit_price'])): ?><small class="field-error"><?= View::e($errors['unit_price']) ?></small><?php endif; ?>
            </label>
            <label>Taxable
                <select name="taxable">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </label>
        </div>
        <button class="primary-action" type="submit">Add Custom Line</button>
    </form>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Disclaimer</p>
            <h2>Customer Notice</h2>
        </div>
    </div>
    <p class="muted"><?= View::e($estimate['disclaimer_text']) ?></p>
</div>
