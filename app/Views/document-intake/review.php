<?php

use App\Core\View;

$documentNumber = $intake['document_number'] ?? '';
$status = $intake['status'] ?? 'uploaded';
$detectedType = $intake['detected_document_type'] ?? '';
$mimeType = $intake['file_mime_type'] ?? '';
$isImage = str_starts_with((string) $mimeType, 'image/');
$isPdf = $mimeType === 'application/pdf';
// Preview the staged JPEG when it exists (faster to render and the AI saw
// this exact bytes); fall back to the original. The original is always
// available via the "Download original" link in the header.
$hasStaged = !empty($intake['staged_file_path']);
$previewHref = $hasStaged
    ? '/document-intake/' . (int) $intake['id'] . '/file?staged=1'
    : '/document-intake/' . (int) $intake['id'] . '/file';
$stagingWarnings = !empty($intake['staging_warnings'])
    ? (json_decode((string) $intake['staging_warnings'], true) ?: [])
    : [];

$financial = $normalized['financial_summary'] ?? [];
$source = $normalized['source_party'] ?? [];
$target = $normalized['target_party'] ?? [];
$vehicle = $normalized['vehicle'] ?? [];
$payment = $normalized['payment'] ?? [];
$warranty = $normalized['warranty'] ?? [];
$coreDeposit = $normalized['core_deposit'] ?? [];
$matchingHints = $normalized['matching_hints'] ?? [];

$canEdit = in_array($status, ['uploaded', 'processing', 'needs_review', 'failed'], true);
$canApprove = in_array($status, ['needs_review', 'uploaded', 'processing'], true);

$statusLabel = ucwords(str_replace('_', ' ', $status));
?>
<?php if ($flashError): ?>
    <div class="alert"><?= View::e($flashError) ?></div>
<?php endif; ?>

<?php if (!empty($savedFlash)): ?>
    <div class="alert" style="background:#e7f6e7;color:#1a571a;border-color:#9bd9a3;">
        Draft saved. The document is still in your queue awaiting approval.
    </div>
<?php endif; ?>

<?php if (!empty($intake['error_message'])): ?>
    <div class="alert"><strong>Error:</strong> <?= View::e($intake['error_message']) ?></div>
<?php endif; ?>

<?php
// Duplicate-warning logic: only show banner when there's a same-hash intake
// that is NOT this one. If any matching intake is already posted, prevent
// posting unless the operator has overridden.
$postedDup = null;
$pendingDups = [];
foreach (($duplicates ?? []) as $d) {
    if ($d['status'] === 'posted') {
        $postedDup = $d;
        break;
    }
    $pendingDups[] = $d;
}
$overridden = (int) ($intake['duplicate_override'] ?? 0) === 1;
?>

<?php if ($postedDup): ?>
    <div class="alert" style="background:#fdecea;color:#7a1f15;border-color:#f5c0bb;">
        <strong>Possible duplicate.</strong>
        File matches
        <a href="/document-intake/<?= (int) $postedDup['id'] ?>/review">
            <?= View::e($postedDup['document_number']) ?>
        </a>
        (already posted on <?= View::e((string) $postedDup['posted_at']) ?>).
        <?php if ($overridden): ?>
            <em>Override confirmed — you can post anyway.</em>
        <?php else: ?>
            <form method="post"
                  action="/document-intake/<?= (int) $intake['id'] ?>/confirm-duplicate"
                  style="display:inline; margin-left:.5rem;"
                  onsubmit="return confirm('Confirm this is a legitimate second posting and proceed?');">
                <button type="submit" class="secondary-action">Post anyway (confirmed duplicate)</button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif ($pendingDups): ?>
    <div class="alert" style="background:#fff7e6;color:#7a5400;border-color:#f3d28a;">
        <strong>Heads up:</strong> file content matches
        <?php foreach ($pendingDups as $i => $d): ?>
            <a href="/document-intake/<?= (int) $d['id'] ?>/review"><?= View::e($d['document_number']) ?></a>
            (<?= View::e(str_replace('_', ' ', $d['status'])) ?>)<?= $i < count($pendingDups) - 1 ? ', ' : '' ?>
        <?php endforeach; ?>.
        Both copies reused the same AI extraction at no extra cost.
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Document Intake</p>
            <h2><?= View::e($documentNumber) ?>
                <small style="font-weight: normal;">&middot; <?= View::e($statusLabel) ?></small>
            </h2>
            <p>
                <?= View::e($intake['original_filename'] ?? '') ?>
                · <?= View::e($mimeType) ?>
                · <?= number_format((int) ($intake['file_size'] ?? 0) / 1024, 1) ?> KB
            </p>
        </div>
        <div class="inline-actions">
            <a class="secondary-action" href="<?= View::e($previewHref) ?>?download=1">Download original</a>
            <a class="secondary-action" href="/document-intake">Back to queue</a>
        </div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 1.5rem; align-items: flex-start;">
    <!-- LEFT: document preview -->
    <div class="panel" style="position: sticky; top: 1rem;">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Document Preview</p>
                <h3><?= $hasStaged ? 'Staged file (AI-ready)' : 'Original file' ?></h3>
            </div>
            <?php if (!empty($intake['staging_driver'])): ?>
                <span class="status-badge">
                    Staging: <?= View::e($intake['staging_driver']) ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($hasStaged): ?>
            <p style="margin-top: 0;">
                <small>
                    <?= View::e(strtoupper((string) $intake['file_mime_type'])) ?> →
                    JPEG via <?= View::e($intake['staging_driver']) ?> ·
                    <a href="/document-intake/<?= (int) $intake['id'] ?>/file?download=1">download original</a>
                </small>
            </p>
        <?php endif; ?>

        <?php if ($stagingWarnings): ?>
            <div class="alert" style="margin-bottom: 1rem;">
                <strong>Staging warnings:</strong>
                <ul style="margin: .25rem 0 0 1rem;">
                    <?php foreach ($stagingWarnings as $w): ?>
                        <li><?= View::e((string) $w) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($isImage || $hasStaged): ?>
            <img src="<?= View::e($previewHref) ?>" alt="Document preview"
                 style="max-width: 100%; border: 1px solid #ddd; border-radius: .5rem;">
        <?php elseif ($isPdf): ?>
            <iframe src="<?= View::e($previewHref) ?>" style="width: 100%; height: 720px; border: 1px solid #ddd; border-radius: .5rem;"></iframe>
        <?php else: ?>
            <p>No inline preview for this file type.
                <a href="<?= View::e($previewHref) ?>?download=1">Download to view.</a>
            </p>
        <?php endif; ?>

        <?php if (!empty($normalized['raw_text_summary'])): ?>
            <div style="margin-top: 1rem;">
                <p class="eyebrow">AI text summary</p>
                <p><?= View::e($normalized['raw_text_summary']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: review form -->
    <form method="post" action="/document-intake/<?= (int) $intake['id'] ?>/approve" novalidate>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">AI Summary</p>
                    <h3>Classification</h3>
                    <?php if ($extraction): ?>
                        <?php
                        $cost = (int) ($extraction['estimated_cost_cents'] ?? 0);
                        $reused = !empty($extraction['reused_from_extraction_id']);
                        ?>
                        <small>
                            Model: <code><?= View::e((string) $extraction['openai_model']) ?></code> ·
                            <?php if ($reused): ?>
                                <em>Reused prior extraction — $0.00</em>
                            <?php else: ?>
                                <?= number_format((int) ($extraction['total_tokens'] ?? 0)) ?> tokens
                                · est. $<?= number_format($cost / 10_000, 4) ?>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
                <span class="status-badge">
                    Confidence:
                    <?= $intake['document_type_confidence'] !== null
                        ? round((float) $intake['document_type_confidence'] * 100) . '%'
                        : '—' ?>
                </span>
            </div>
            <div class="fields two">
                <label>Document type
                    <select name="detected_document_type">
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?= View::e($type) ?>" <?= ($detectedType ?: 'unknown') === $type ? 'selected' : '' ?>>
                                <?= View::e(ucwords(str_replace('_', ' ', $type))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Document number (from file)
                    <input value="<?= View::e($normalized['document_number'] ?? '') ?>" disabled>
                </label>
                <label>Document date
                    <input value="<?= View::e($normalized['document_date'] ?? '') ?>" disabled>
                </label>
                <label>Currency
                    <input value="<?= View::e($normalized['currency'] ?? '') ?>" disabled>
                </label>
            </div>
        </div>

        <?php if ($warnings): ?>
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Warnings</p>
                        <h3>Items to review</h3>
                    </div>
                </div>
                <ul>
                    <?php foreach ($warnings as $warning): ?>
                        <li><?= View::e((string) $warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Document Details</p>
                    <h3>Extracted parties &amp; totals</h3>
                </div>
            </div>

            <h4 style="margin-bottom: .5rem;">Source (vendor / sender)</h4>
            <div class="fields two">
                <label>Name<input value="<?= View::e($source['name'] ?? '') ?>" disabled></label>
                <label>Phone<input value="<?= View::e($source['phone'] ?? '') ?>" disabled></label>
                <label>Email<input value="<?= View::e($source['email'] ?? '') ?>" disabled></label>
                <label>Address<input value="<?= View::e($source['address'] ?? '') ?>" disabled></label>
            </div>

            <h4 style="margin: 1rem 0 .5rem;">Target (customer / business)</h4>
            <div class="fields two">
                <label>Name<input value="<?= View::e($target['name'] ?? '') ?>" disabled></label>
                <label>Phone<input value="<?= View::e($target['phone'] ?? '') ?>" disabled></label>
                <label>Email<input value="<?= View::e($target['email'] ?? '') ?>" disabled></label>
                <label>Address<input value="<?= View::e($target['address'] ?? '') ?>" disabled></label>
            </div>

            <h4 style="margin: 1rem 0 .5rem;">Vehicle</h4>
            <div class="fields two">
                <label>VIN<input value="<?= View::e($vehicle['vin'] ?? '') ?>" disabled></label>
                <label>Plate<input value="<?= View::e($vehicle['plate'] ?? '') ?>" disabled></label>
                <label>Year / Make / Model<input value="<?= View::e(trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''))) ?>" disabled></label>
                <label>Color<input value="<?= View::e($vehicle['color'] ?? '') ?>" disabled></label>
            </div>

            <h4 style="margin: 1rem 0 .5rem;">Financial summary</h4>
            <div class="fields two">
                <label>Subtotal<input value="<?= View::e(number_format((float) ($financial['subtotal'] ?? 0), 2)) ?>" disabled></label>
                <label>Tax<input value="<?= View::e(number_format((float) ($financial['tax'] ?? 0), 2)) ?>" disabled></label>
                <label>Fees<input value="<?= View::e(number_format((float) ($financial['fees'] ?? 0), 2)) ?>" disabled></label>
                <label>Discounts<input value="<?= View::e(number_format((float) ($financial['discounts'] ?? 0), 2)) ?>" disabled></label>
                <label>Shipping<input value="<?= View::e(number_format((float) ($financial['shipping'] ?? 0), 2)) ?>" disabled></label>
                <label>Total<input value="<?= View::e(number_format((float) ($financial['total'] ?? 0), 2)) ?>" disabled></label>
                <label>Amount paid<input value="<?= View::e(number_format((float) ($financial['amount_paid'] ?? 0), 2)) ?>" disabled></label>
                <label>Balance due<input value="<?= View::e(number_format((float) ($financial['balance_due'] ?? 0), 2)) ?>" disabled></label>
            </div>

            <?php if (!empty($payment)): ?>
                <h4 style="margin: 1rem 0 .5rem;">Payment</h4>
                <div class="fields two">
                    <label>Method<input value="<?= View::e($payment['payment_method'] ?? '') ?>" disabled></label>
                    <label>Last four<input value="<?= View::e($payment['last_four'] ?? '') ?>" disabled></label>
                    <label>Authorization<input value="<?= View::e($payment['authorization_code'] ?? '') ?>" disabled></label>
                    <label>Transaction ID<input value="<?= View::e($payment['transaction_id'] ?? '') ?>" disabled></label>
                </div>
            <?php endif; ?>

            <?php if (!empty($warranty) && !empty($warranty['has_warranty'])): ?>
                <h4 style="margin: 1rem 0 .5rem;">Warranty</h4>
                <div class="fields two">
                    <label>Period<input value="<?= View::e($warranty['warranty_period'] ?? '') ?>" disabled></label>
                    <label>Expires<input value="<?= View::e($warranty['warranty_expiration_date'] ?? '') ?>" disabled></label>
                </div>
                <?php if (!empty($warranty['warranty_terms'])): ?>
                    <p><strong>Terms:</strong> <?= View::e($warranty['warranty_terms']) ?></p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($coreDeposit) && !empty($coreDeposit['has_core_charge'])): ?>
                <h4 style="margin: 1rem 0 .5rem;">Core deposit</h4>
                <div class="fields two">
                    <label>Amount<input value="<?= View::e(number_format((float) ($coreDeposit['core_amount'] ?? 0), 2)) ?>" disabled></label>
                    <label>Return deadline<input value="<?= View::e($coreDeposit['core_return_deadline'] ?? '') ?>" disabled></label>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Matched Records</p>
                    <h3>Suggested links</h3>
                </div>
            </div>

            <?php if (!$matches): ?>
                <p>No automatic matches found. Pick records manually below.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Match</th>
                                <th>Confidence</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $match): ?>
                                <?php $key = $match['matched_table'] . ':' . $match['matched_record_id']; ?>
                                <tr>
                                    <td><?= View::e(ucwords(str_replace('_', ' ', $match['match_type']))) ?></td>
                                    <td><?= View::e($matchSummaries[$key] ?? ($match['matched_table'] . ' #' . $match['matched_record_id'])) ?></td>
                                    <td><?= View::e($match['match_confidence'] !== null ? round((float) $match['match_confidence'] * 100) . '%' : '—') ?></td>
                                    <td><?= View::e($match['match_reason'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h4 style="margin-top: 1rem;">Pick the records to link</h4>
            <div class="fields two">
                <label>Vendor
                    <select name="related_vendor_id" <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="">None</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?= (int) $vendor['id'] ?>" <?= (string) ($intake['related_vendor_id'] ?? '') === (string) $vendor['id'] ? 'selected' : '' ?>>
                                <?= View::e($vendor['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Customer
                    <select name="related_customer_id" <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="">None</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer['id'] ?>" <?= (string) ($intake['related_customer_id'] ?? '') === (string) $customer['id'] ? 'selected' : '' ?>>
                                <?= View::e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Vehicle
                    <select name="related_vehicle_id" <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="">None</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?= (int) $vehicle['id'] ?>" <?= (string) ($intake['related_vehicle_id'] ?? '') === (string) $vehicle['id'] ? 'selected' : '' ?>>
                                <?= View::e(trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Service request
                    <select name="related_service_request_id" <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="">None</option>
                        <?php foreach ($serviceRequests as $sr): ?>
                            <option value="<?= (int) $sr['id'] ?>" <?= (string) ($intake['related_service_request_id'] ?? '') === (string) $sr['id'] ? 'selected' : '' ?>>
                                <?= View::e($sr['service_request_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Invoice
                    <select name="related_invoice_id" <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="">None</option>
                        <?php foreach ($invoices as $inv): ?>
                            <option value="<?= (int) $inv['id'] ?>" <?= (string) ($intake['related_invoice_id'] ?? '') === (string) $inv['id'] ? 'selected' : '' ?>>
                                <?= View::e($inv['invoice_number']) ?> — $<?= View::e(number_format((float) $inv['total'], 2)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <?php if (!empty($matchingHints)): ?>
                <div style="margin-top: 1rem;">
                    <p class="eyebrow">AI matching hints</p>
                    <ul>
                        <?php foreach ($matchingHints as $hintKey => $hintValue): ?>
                            <?php if ($hintValue): ?>
                                <li><strong><?= View::e(str_replace('_', ' ', (string) $hintKey)) ?>:</strong>
                                    <?= View::e((string) $hintValue) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Line Items</p>
                    <h3>Extracted items &amp; categorization</h3>
                </div>
                <span class="status-badge"><?= count($lines) ?> line(s)</span>
            </div>

            <?php if (!$lines): ?>
                <p>No line items were extracted.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th>SKU / Part</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Subtotal</th>
                                <th>Category</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="line_id[]" value="<?= (int) $line['id'] ?>">
                                        <?= (int) $line['line_number'] ?>
                                    </td>
                                    <td>
                                        <input name="line_description[]" value="<?= View::e($line['description']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <input name="line_sku[]" placeholder="SKU" value="<?= View::e($line['sku']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                                        <input name="line_mpn[]" placeholder="MPN" value="<?= View::e($line['manufacturer_part_number']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                                        <input name="line_vpn[]" placeholder="Vendor #" value="<?= View::e($line['vendor_part_number']) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                                    </td>
                                    <td>
                                        <input name="line_quantity[]" inputmode="decimal" value="<?= View::e(rtrim(rtrim(number_format((float) $line['quantity'], 4, '.', ''), '0'), '.')) ?>" <?= $canEdit ? '' : 'disabled' ?> style="width: 5rem;">
                                    </td>
                                    <td>
                                        <input name="line_unit_price[]" inputmode="decimal" value="<?= View::e(number_format((float) $line['unit_price'], 2, '.', '')) ?>" <?= $canEdit ? '' : 'disabled' ?> style="width: 6rem;">
                                    </td>
                                    <td>$<?= View::e(number_format((float) $line['line_subtotal'], 2)) ?></td>
                                    <td>
                                        <select name="line_reviewed_category[]" <?= $canEdit ? '' : 'disabled' ?>>
                                            <option value="">(use AI guess)</option>
                                            <?php $picked = $line['reviewed_category'] ?: $line['category_guess']; ?>
                                            <?php foreach ($lineCategories as $cat): ?>
                                                <option value="<?= View::e($cat) ?>" <?= $picked === $cat ? 'selected' : '' ?>>
                                                    <?= View::e(ucwords(str_replace('_', ' ', $cat))) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($line['category_guess'])): ?>
                                            <br><small>AI: <?= View::e(ucwords(str_replace('_', ' ', $line['category_guess']))) ?></small>
                                        <?php endif; ?>
                                        <?php $flags = []; ?>
                                        <?php if ((int) $line['resale_candidate'] === 1) { $flags[] = 'resale'; } ?>
                                        <?php if ((int) $line['inventory_candidate'] === 1) { $flags[] = 'inventory'; } ?>
                                        <?php if ((int) $line['warranty_candidate'] === 1) { $flags[] = 'warranty'; } ?>
                                        <?php if ($flags): ?>
                                            <br><small>Flags: <?= View::e(implode(', ', $flags)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= View::e($line['confidence'] !== null ? round((float) $line['confidence'] * 100) . '%' : '—') ?></td>
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
                    <p class="eyebrow">Notes</p>
                    <h3>Operator notes</h3>
                </div>
            </div>
            <label>Notes (saved on approve)
                <textarea name="notes" rows="3" <?= $canEdit ? '' : 'disabled' ?>><?= View::e($intake['notes'] ?? '') ?></textarea>
            </label>
        </div>

        <?php if ($postingLogs): ?>
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <p class="eyebrow">Audit Trail</p>
                        <h3>Posting log</h3>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>Action</th>
                                <th>Posted record</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($postingLogs as $log): ?>
                                <tr>
                                    <td><?= View::e($log['posted_at'] ?: $log['created_at']) ?></td>
                                    <td><?= View::e($log['action_taken']) ?></td>
                                    <td>
                                        <?= View::e($log['posted_record_type']) ?>
                                        <?php if (!empty($log['posted_record_id'])): ?>
                                            #<?= (int) $log['posted_record_id'] ?>
                                        <?php endif; ?>
                                        <?php if ($log['posted_record_type'] === 'vendor_document' && !empty($log['posted_record_id'])): ?>
                                            — <a href="/vendor-documents/<?= (int) $log['posted_record_id'] ?>">open</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <pre style="white-space: pre-wrap; margin: 0; font-size: .8rem;"><?= View::e($log['after_json'] ?? '') ?></pre>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="sticky-actions">
            <?php if ($canApprove): ?>
                <button type="submit" formaction="/document-intake/<?= (int) $intake['id'] ?>/save-draft" formmethod="post" class="secondary-action">Save Draft</button>
                <button type="submit" class="primary-action">Approve &amp; Post</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<form method="post" action="/document-intake/<?= (int) $intake['id'] ?>/reprocess" style="margin-top: 1rem;">
    <button type="submit" class="secondary-action">Reprocess with AI</button>
</form>

<?php if (!in_array($status, ['rejected', 'posted'], true)): ?>
    <form method="post" action="/document-intake/<?= (int) $intake['id'] ?>/reject" style="margin-top: .5rem;"
          onsubmit="return confirm('Reject this document? It will stay on file but will not be posted.');">
        <label>Reason
            <input name="reason" placeholder="Why is this being rejected?">
        </label>
        <button type="submit" class="secondary-action">Reject</button>
    </form>
<?php endif; ?>
