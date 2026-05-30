<?php

use App\Core\View;
use App\Models\VendorDocument;
?>
<form class="form-grid" method="post" action="/vendor-documents" enctype="multipart/form-data" novalidate>
    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Upload</p>
                <h2>Vendor Document</h2>
            </div>
        </div>
        <div class="fields two">
            <label>Vendor
                <select name="vendor_id">
                    <option value="">No vendor selected</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= (int) $vendor['id'] ?>" <?= ((string) ($values['vendor_id'] ?? '') === (string) $vendor['id']) ? 'selected' : '' ?>><?= View::e($vendor['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Document type
                <select name="document_type">
                    <?php foreach (VendorDocument::TYPES as $type): ?>
                        <option value="<?= $type ?>" <?= ($values['document_type'] ?? 'receipt') === $type ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $type)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['document_type'])): ?><small class="field-error"><?= View::e($errors['document_type']) ?></small><?php endif; ?>
            </label>
            <label>External number
                <input name="external_document_number" value="<?= View::e($values['external_document_number'] ?? '') ?>" placeholder="Vendor invoice or receipt #">
            </label>
            <label>Document date
                <input name="document_date" type="date" value="<?= View::e($values['document_date'] ?? '') ?>">
                <?php if (isset($errors['document_date'])): ?><small class="field-error"><?= View::e($errors['document_date']) ?></small><?php endif; ?>
            </label>
            <label>Total
                <input name="total" inputmode="decimal" value="<?= View::e($values['total'] ?? '0.00') ?>" required>
                <?php if (isset($errors['total'])): ?><small class="field-error"><?= View::e($errors['total']) ?></small><?php endif; ?>
            </label>
            <label>Tax included
                <input name="tax_total" inputmode="decimal" value="<?= View::e($values['tax_total'] ?? '0.00') ?>">
                <?php if (isset($errors['tax_total'])): ?><small class="field-error"><?= View::e($errors['tax_total']) ?></small><?php endif; ?>
            </label>
            <label>Payment method
                <select name="payment_method">
                    <option value="">Not specified</option>
                    <?php foreach (VendorDocument::PAYMENT_METHODS as $method): ?>
                        <option value="<?= $method ?>" <?= ($values['payment_method'] ?? '') === $method ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $method)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['payment_method'])): ?><small class="field-error"><?= View::e($errors['payment_method']) ?></small><?php endif; ?>
            </label>
            <label>Receipt file (PDF or image)
                <input name="receipt_file" type="file" accept="application/pdf,image/*" required>
                <?php if (isset($errors['receipt_file'])): ?><small class="field-error"><?= View::e($errors['receipt_file']) ?></small><?php endif; ?>
            </label>
        </div>
        <label>Notes
            <textarea name="notes" rows="3"><?= View::e($values['notes'] ?? '') ?></textarea>
        </label>
    </div>

    <div class="sticky-actions">
        <a class="secondary-action" href="/vendor-documents">Cancel</a>
        <button class="primary-action" type="submit">Upload Document</button>
    </div>
</form>
