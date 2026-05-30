<?php

use App\Core\View;

$vehicle = trim(($report['year'] ?? '') . ' ' . ($report['make'] ?? '') . ' ' . ($report['model'] ?? '') . ' ' . ($report['color'] ?? '')) ?: 'Not captured';
$uploadErrors = $uploadErrors ?? [];
$attachments = $attachments ?? [];
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Service Report</p>
                <h2><?= View::e($report['report_number']) ?></h2>
            </div>
            <span class="status-badge"><?= View::e($report['completion_status']) ?></span>
        </div>
        <dl class="details">
            <dt>Customer</dt>
            <dd><?= View::e($report['first_name'] . ' ' . $report['last_name']) ?></dd>
            <dt>Work Order</dt>
            <dd><a href="/work-orders/<?= (int) $report['work_order_id'] ?>"><?= View::e($report['work_order_number']) ?></a></dd>
            <dt>Service</dt>
            <dd><?= View::e($report['requested_service']) ?></dd>
            <dt>Vehicle</dt>
            <dd><?= View::e($vehicle) ?></dd>
            <dt>VIN Captured</dt>
            <dd><?= View::e($report['vin_captured'] ?: 'Not captured') ?></dd>
            <dt>No vehicle serviced</dt>
            <dd><?= ((int) $report['no_vehicle_serviced_flag']) === 1 ? 'Yes' : 'No' ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Next</p>
                <h2>Invoice Prep</h2>
            </div>
        </div>
        <p class="muted">This report is the factual source for the invoice.</p>
        <form method="post" action="/invoices/from-service-report/<?= (int) $report['id'] ?>">
            <button class="primary-action" type="submit">Generate Invoice</button>
        </form>
    </aside>
</div>

<div class="detail-grid">
    <form class="panel" method="post" action="/service-reports/<?= (int) $report['id'] ?>/attachments" enctype="multipart/form-data">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Proof</p>
                <h2>Add Photo or Signature</h2>
            </div>
        </div>
        <?php if ($uploadErrors): ?>
            <div class="alert"><?= View::e(implode(' ', $uploadErrors)) ?></div>
        <?php endif; ?>
        <label>Type
            <select name="file_type">
                <option value="photo">Photo</option>
                <option value="signature">Signature</option>
            </select>
        </label>
        <label>File
            <input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,image/gif" required>
        </label>
        <label>Caption
            <input name="caption" placeholder="Before photo, completed work, customer signature">
        </label>
        <button class="primary-action" type="submit">Upload Proof</button>
    </form>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Attachments</p>
                <h2>Proof Files</h2>
            </div>
            <span class="status-badge"><?= count($attachments) ?> total</span>
        </div>
        <?php if (!$attachments): ?>
            <p class="muted">No photos or signatures uploaded yet.</p>
        <?php else: ?>
            <div class="record-list">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="record-row">
                        <strong><?= View::e(ucwords(str_replace('_', ' ', $attachment['file_type'])) . ' - ' . $attachment['original_filename']) ?></strong>
                        <span><?= View::e(($attachment['caption'] ?: 'No caption') . ' at ' . $attachment['created_at']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Actual Work</p>
            <h2>Performed</h2>
        </div>
    </div>
    <p><?= nl2br(View::e($report['actual_work_performed'])) ?></p>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Notes</p>
            <h2>Field Record</h2>
        </div>
    </div>
    <dl class="details">
        <dt>Technician</dt>
        <dd><?= View::e($report['technician_notes'] ?: 'None') ?></dd>
        <dt>Customer</dt>
        <dd><?= View::e($report['customer_notes'] ?: 'None') ?></dd>
        <dt>Odometer</dt>
        <dd><?= View::e($report['odometer'] ?: 'Not captured') ?></dd>
        <dt>Completed</dt>
        <dd><?= View::e($report['completed_at']) ?></dd>
    </dl>
</div>
