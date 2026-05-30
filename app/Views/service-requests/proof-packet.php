<?php

use App\Core\View;

$sr = $packet['service_request'];
$vehicle = trim(($sr['year'] ?? '') . ' ' . ($sr['make'] ?? '') . ' ' . ($sr['model'] ?? '') . ' ' . ($sr['color'] ?? '')) ?: 'Not captured';
$location = View::address($sr, 'Not captured');
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Proof Packet</p>
                <h2><?= View::e($sr['service_request_number']) ?></h2>
            </div>
            <span class="status-badge"><?= $packet['missing_items'] ? count($packet['missing_items']) . ' missing' : 'complete' ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($sr['first_name'] . ' ' . $sr['last_name']) ?></dd>
            <dt>Phone</dt>
            <dd><?= View::e($sr['phone']) ?></dd>
            <dt>Service</dt>
            <dd><?= View::e($sr['requested_service']) ?></dd>
            <dt>Location</dt>
            <dd><?= View::e($location) ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e($vehicle) ?></dd>
            <dt>VIN</dt>
            <dd><?= View::e($sr['vin'] ?: 'Missing') ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Readiness</p>
                <h2><?= $packet['missing_items'] ? 'Incomplete' : 'Ready' ?></h2>
            </div>
        </div>
        <?php if ($packet['missing_items']): ?>
            <ul class="validation-list">
                <?php foreach ($packet['missing_items'] as $item): ?>
                    <li><?= View::e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Core job, billing, payment, receipt, and accounting records are linked.</p>
        <?php endif; ?>
        <div class="stacked-actions">
            <form method="post" action="/service-requests/<?= (int) $sr['id'] ?>/proof-packet/documents/generate">
                <button class="secondary-action" type="submit">Generate PDF</button>
            </form>
        </div>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Attachments</p>
            <h2>Photos and Signatures</h2>
        </div>
        <span class="status-badge"><?= count($packet['attachments']) ?> total</span>
    </div>
    <?php if (!$packet['attachments']): ?>
        <p class="muted">No proof attachments are linked yet.</p>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($packet['attachments'] as $attachment): ?>
                <div class="record-row">
                    <strong><?= View::e(ucwords(str_replace('_', ' ', $attachment['file_type'])) . ' - ' . $attachment['original_filename']) ?></strong>
                    <span><?= View::e(($attachment['caption'] ?: 'No caption') . ' at ' . $attachment['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Documents</p>
            <h2>Job Record</h2>
        </div>
    </div>
    <div class="record-list">
        <a class="record-row" href="/service-requests/<?= (int) $sr['id'] ?>"><strong>Service Request</strong><span><?= View::e($sr['service_request_number']) ?></span></a>
        <?php if ($packet['estimate']): ?>
            <a class="record-row" href="/estimates/<?= (int) $packet['estimate']['id'] ?>"><strong>Estimate</strong><span><?= View::e($packet['estimate']['estimate_number']) ?> - $<?= View::e(number_format((float) $packet['estimate']['total'], 2)) ?></span></a>
        <?php endif; ?>
        <?php if ($packet['approval']): ?>
            <div class="record-row"><strong>Approval</strong><span><?= View::e($packet['approval']['approval_number']) ?> - <?= View::e($packet['approval']['approval_method']) ?></span></div>
        <?php endif; ?>
        <?php if ($packet['work_order']): ?>
            <a class="record-row" href="/work-orders/<?= (int) $packet['work_order']['id'] ?>"><strong>Work Order</strong><span><?= View::e($packet['work_order']['work_order_number']) ?> - <?= View::e($packet['work_order']['status']) ?></span></a>
        <?php endif; ?>
        <?php if ($packet['service_report']): ?>
            <a class="record-row" href="/service-reports/<?= (int) $packet['service_report']['id'] ?>"><strong>Service Report</strong><span><?= View::e($packet['service_report']['report_number']) ?> - <?= View::e($packet['service_report']['completion_status']) ?></span></a>
        <?php endif; ?>
        <?php if ($packet['invoice']): ?>
            <a class="record-row" href="/invoices/<?= (int) $packet['invoice']['id'] ?>"><strong>Invoice</strong><span><?= View::e($packet['invoice']['invoice_number']) ?> - $<?= View::e(number_format((float) $packet['invoice']['total'], 2)) ?></span></a>
        <?php endif; ?>
        <?php foreach ($packet['payments'] as $payment): ?>
            <a class="record-row" href="/payments/<?= (int) $payment['id'] ?>"><strong>Payment</strong><span><?= View::e($payment['payment_number']) ?> - $<?= View::e(number_format((float) $payment['amount'], 2)) ?></span></a>
        <?php endforeach; ?>
        <?php foreach ($packet['receipts'] as $receipt): ?>
            <a class="record-row" href="/receipts/<?= (int) $receipt['id'] ?>"><strong>Receipt</strong><span><?= View::e($receipt['receipt_number']) ?></span></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Generated</p>
            <h2>Document Records</h2>
        </div>
        <span class="status-badge"><?= count($packet['documents']) ?> total</span>
    </div>
    <?= View::render('partials/document_list', [
        'documents' => $packet['documents'],
        'emptyMessage' => 'No generated document records are linked yet.',
    ]) ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Accounting</p>
            <h2>Linked Ledger Entries</h2>
        </div>
    </div>
    <?php if (!$packet['ledger_entries']): ?>
        <p class="muted">No ledger entries linked yet.</p>
    <?php else: ?>
        <div class="record-list">
            <?php foreach ($packet['ledger_entries'] as $entry): ?>
                <a class="record-row" href="/accounting/ledger/<?= (int) $entry['id'] ?>">
                    <strong><?= View::e($entry['entry_number']) ?></strong>
                    <span>$<?= View::e(number_format((float) $entry['debit_total'], 2)) ?> / $<?= View::e(number_format((float) $entry['credit_total'], 2)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Audit</p>
            <h2>Timeline</h2>
        </div>
    </div>
    <?php if (!$packet['timeline']): ?>
        <p class="muted">No timeline events yet.</p>
    <?php else: ?>
        <ol class="timeline">
            <?php foreach ($packet['timeline'] as $event): ?>
                <li>
                    <strong><?= View::e(str_replace('_', ' ', $event['action'])) ?></strong>
                    <span><?= View::e($event['created_at']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
