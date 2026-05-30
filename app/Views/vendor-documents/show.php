<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Vendor Document</p>
                <h2><?= View::e($document['document_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e(ucwords(str_replace('_', ' ', $document['status']))) ?></span>
        </div>
        <dl class="details">
            <dt>Vendor</dt>
            <dd><?= View::e($document['vendor_name'] ?: 'Not linked') ?></dd>
            <dt>Type</dt>
            <dd><?= View::e(ucwords(str_replace('_', ' ', $document['document_type']))) ?></dd>
            <dt>External number</dt>
            <dd><?= View::e($document['external_document_number'] ?: '--') ?></dd>
            <dt>Document date</dt>
            <dd><?= View::e($document['document_date'] ?: $document['uploaded_at']) ?></dd>
            <dt>Payment method</dt>
            <dd><?= View::e($document['payment_method'] ? ucwords(str_replace('_', ' ', $document['payment_method'])) : 'Not specified') ?></dd>
            <dt>File</dt>
            <dd>
                <?php if (!empty($document['file_path'])): ?>
                    <a href="/<?= View::e($document['file_path']) ?>" target="_blank" rel="noopener"><?= View::e($document['original_filename'] ?? 'Open file') ?></a>
                <?php else: ?>
                    <span class="muted">No file attached</span>
                <?php endif; ?>
            </dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Totals</p>
                <h2>$<?= View::e(number_format((float) $document['total'], 2)) ?></h2>
            </div>
        </div>
        <dl class="details compact-details">
            <dt>Subtotal</dt>
            <dd>$<?= View::e(number_format((float) $document['subtotal'], 2)) ?></dd>
            <dt>Tax</dt>
            <dd>$<?= View::e(number_format((float) $document['tax_total'], 2)) ?></dd>
            <dt>Total</dt>
            <dd>$<?= View::e(number_format((float) $document['total'], 2)) ?></dd>
            <dt>Lines reviewed</dt>
            <dd><?= (int) $reviewedCount ?> of <?= count($lines) ?></dd>
        </dl>
        <?php if (isset($lineErrors['__approval'])): ?>
            <div class="alert"><?= View::e($lineErrors['__approval']) ?></div>
        <?php endif; ?>
        <?php if (isset($lineErrors['__post'])): ?>
            <div class="alert"><?= View::e($lineErrors['__post']) ?></div>
        <?php endif; ?>
        <div class="stacked-actions">
            <?php if ($document['status'] === 'uploaded'): ?>
                <form method="post" action="/vendor-documents/<?= (int) $document['id'] ?>/review">
                    <button class="primary-action" type="submit">Mark Needs Review</button>
                </form>
            <?php elseif ($document['status'] === 'needs_review'): ?>
                <form method="post" action="/vendor-documents/<?= (int) $document['id'] ?>/approve">
                    <button class="primary-action" type="submit">Approve Document</button>
                </form>
            <?php elseif ($document['status'] === 'approved'): ?>
                <form method="post" action="/vendor-documents/<?= (int) $document['id'] ?>/post">
                    <button class="primary-action" type="submit">Post to Ledger</button>
                </form>
            <?php elseif ($document['status'] === 'posted'): ?>
                <?php
                    $ledger = \App\Core\Database::connection()->prepare('SELECT id, entry_number FROM ledger_entries WHERE source_type = :t AND source_id = :id LIMIT 1');
                    $ledger->execute(['t' => 'vendor_document', 'id' => $document['id']]);
                    $ledgerRow = $ledger->fetch();
                ?>
                <?php if ($ledgerRow): ?>
                    <a class="secondary-action" href="/accounting/ledger/<?= (int) $ledgerRow['id'] ?>">View Ledger Entry <?= View::e($ledgerRow['entry_number']) ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php if (!empty($document['notes'])): ?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Notes</p>
            <h2>Document Notes</h2>
        </div>
    </div>
    <p><?= nl2br(View::e($document['notes'])) ?></p>
</div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Line Items</p>
            <h2>Categorize Each Line</h2>
        </div>
        <span class="status-badge"><?= count($lines) ?> lines</span>
    </div>

    <?php if (!$lines): ?>
        <p class="muted">No lines recorded yet. Add the first line below.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Part #</th>
                        <th>Category</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Total</th>
                        <th>Reviewed</th>
                        <?php if ($canEditLines): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                        <tr>
                            <td><?= View::e($line['item_name']) ?></td>
                            <td><?= View::e($line['part_number'] ?: '--') ?></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $line['category']))) ?></td>
                            <td><?= View::e(number_format((float) $line['quantity'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $line['unit_cost'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $line['line_total'], 2)) ?></td>
                            <td><?= ((int) $line['reviewed_flag']) === 1 ? 'Yes' : 'No' ?></td>
                            <?php if ($canEditLines): ?>
                                <td>
                                    <form method="post" action="/vendor-documents/<?= (int) $document['id'] ?>/lines/<?= (int) $line['id'] ?>/delete" onsubmit="return confirm('Delete this line?');">
                                        <button class="secondary-action" type="submit">Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($canEditLines): ?>
<form class="panel" method="post" action="/vendor-documents/<?= (int) $document['id'] ?>/lines">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Add Line</p>
            <h2>New Line Item</h2>
        </div>
    </div>
    <?php if ($lineErrors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>
    <div class="fields two">
        <label>Item name
            <input name="item_name" value="<?= View::e($lineValues['item_name'] ?? '') ?>" required>
            <?php if (isset($lineErrors['item_name'])): ?><small class="field-error"><?= View::e($lineErrors['item_name']) ?></small><?php endif; ?>
        </label>
        <label>Part number
            <input name="part_number" value="<?= View::e($lineValues['part_number'] ?? '') ?>">
        </label>
        <label>Category
            <select name="category">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category ?>" <?= ($lineValues['category'] ?? 'other') === $category ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $category)) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($lineErrors['category'])): ?><small class="field-error"><?= View::e($lineErrors['category']) ?></small><?php endif; ?>
        </label>
        <label>Service request link (optional)
            <select name="service_request_id">
                <option value="">Not linked</option>
                <?php foreach ($serviceRequests as $sr): ?>
                    <option value="<?= (int) $sr['id'] ?>" <?= ((string) ($lineValues['service_request_id'] ?? '') === (string) $sr['id']) ? 'selected' : '' ?>><?= View::e($sr['service_request_number'] . ' - ' . $sr['requested_service']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantity
            <input name="quantity" inputmode="decimal" value="<?= View::e($lineValues['quantity'] ?? '1') ?>" required>
            <?php if (isset($lineErrors['quantity'])): ?><small class="field-error"><?= View::e($lineErrors['quantity']) ?></small><?php endif; ?>
        </label>
        <label>Unit cost
            <input name="unit_cost" inputmode="decimal" value="<?= View::e($lineValues['unit_cost'] ?? '0.00') ?>" required>
            <?php if (isset($lineErrors['unit_cost'])): ?><small class="field-error"><?= View::e($lineErrors['unit_cost']) ?></small><?php endif; ?>
        </label>
        <label class="checkbox-row">
            <input name="reviewed_flag" type="checkbox" value="1" <?= !empty($lineValues['reviewed_flag']) ? 'checked' : '' ?>>
            Mark reviewed
        </label>
    </div>
    <div class="stacked-actions">
        <button class="primary-action" type="submit">Add Line</button>
    </div>
</form>
<?php endif; ?>
