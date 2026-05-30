<?php

use App\Core\View;
?>
<form class="form-grid" method="post" action="/document-intake" enctype="multipart/form-data" novalidate>
    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <?php if (!$extractionEnabled): ?>
        <div class="alert">
            AI extraction is disabled. Set <code>OPENAI_API_KEY</code> in <code>.env</code> and
            <code>OPENAI_DOCUMENT_EXTRACTION_ENABLED=true</code> to enable it. The file will still
            be stored, but no classification will happen until extraction is enabled and you
            reprocess the document.
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Upload</p>
                <h2>Document for AI Intake</h2>
            </div>
        </div>

        <div class="fields two">
            <label>Document file (PDF or image)
                <input name="document_file" type="file"
                       accept="application/pdf,image/jpeg,image/png,image/webp,image/gif"
                       capture="environment"
                       required>
                <?php if (isset($errors['document_file'])): ?>
                    <small class="field-error"><?= View::e($errors['document_file']) ?></small>
                <?php else: ?>
                    <small>
                        On a phone this opens the rear camera; on desktop it opens a file picker.
                        Re-uploading the same file is free — we re-use the prior AI extraction.
                    </small>
                <?php endif; ?>
            </label>

            <label>Source
                <select name="source_type">
                    <?php foreach ($sourceTypes as $type): ?>
                        <option value="<?= View::e($type) ?>" <?= ($values['source_type'] ?? 'unknown') === $type ? 'selected' : '' ?>>
                            <?= View::e(ucwords($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Related vendor (optional)
                <select name="related_vendor_id">
                    <option value="">None</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= (int) $vendor['id'] ?>" <?= (string) ($values['related_vendor_id'] ?? '') === (string) $vendor['id'] ? 'selected' : '' ?>>
                            <?= View::e($vendor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Related customer (optional)
                <select name="related_customer_id">
                    <option value="">None</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= (int) $customer['id'] ?>" <?= (string) ($values['related_customer_id'] ?? '') === (string) $customer['id'] ? 'selected' : '' ?>>
                            <?= View::e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?>
                            <?php if (!empty($customer['phone'])): ?>(<?= View::e($customer['phone']) ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Related vehicle (optional)
                <select name="related_vehicle_id">
                    <option value="">None</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php
                        $label = trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
                        if (!empty($vehicle['vin'])) {
                            $label .= ' — VIN ' . $vehicle['vin'];
                        } elseif (!empty($vehicle['plate_number'])) {
                            $label .= ' — Plate ' . $vehicle['plate_number'];
                        }
                        ?>
                        <option value="<?= (int) $vehicle['id'] ?>" <?= (string) ($values['related_vehicle_id'] ?? '') === (string) $vehicle['id'] ? 'selected' : '' ?>>
                            <?= View::e($label ?: 'Vehicle #' . $vehicle['id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Related service request (optional)
                <select name="related_service_request_id">
                    <option value="">None</option>
                    <?php foreach ($serviceRequests as $sr): ?>
                        <option value="<?= (int) $sr['id'] ?>" <?= (string) ($values['related_service_request_id'] ?? '') === (string) $sr['id'] ? 'selected' : '' ?>>
                            <?= View::e($sr['service_request_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Related invoice (optional)
                <select name="related_invoice_id">
                    <option value="">None</option>
                    <?php foreach ($invoices as $inv): ?>
                        <option value="<?= (int) $inv['id'] ?>" <?= (string) ($values['related_invoice_id'] ?? '') === (string) $inv['id'] ? 'selected' : '' ?>>
                            <?= View::e($inv['invoice_number']) ?> — $<?= View::e(number_format((float) $inv['total'], 2)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <label>Notes
            <textarea name="notes" rows="3"><?= View::e($values['notes'] ?? '') ?></textarea>
        </label>
    </div>

    <div class="sticky-actions">
        <a class="secondary-action" href="/document-intake">Cancel</a>
        <button class="primary-action" type="submit">Upload &amp; Extract</button>
    </div>
</form>
