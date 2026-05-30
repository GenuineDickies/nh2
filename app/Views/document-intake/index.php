<?php

use App\Core\View;
use App\Models\DocumentIntake;

$statusLabels = [
    'uploaded' => 'Uploaded',
    'processing' => 'Processing',
    'needs_review' => 'Needs Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'failed' => 'Failed',
    'posted' => 'Posted',
];

// Cumulative AI spend across all paid extractions (reused extractions excluded).
$totalHundredthsOfCent = (int) ($usage['cost_hundredths_cent'] ?? 0);
$totalCostUsd = $totalHundredthsOfCent / 10_000;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">AI Intake</p>
            <h2>Document Intake Queue</h2>
        </div>
        <div class="inline-actions">
            <a class="primary-action" href="/document-intake/upload">Upload Document</a>
        </div>
    </div>

    <p style="margin: 0 0 1rem;">
        <small>
            AI usage to date:
            <strong><?= number_format((int) ($usage['extraction_count'] ?? 0)) ?> extraction(s)</strong>,
            <?= number_format((int) ($usage['total_tokens'] ?? 0)) ?> tokens,
            est. <strong>$<?= number_format($totalCostUsd, 4) ?></strong>.
            Re-uploaded files reuse the prior result and cost nothing.
        </small>
    </p>

    <div class="inline-actions" style="flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem;">
        <a class="status-badge<?= $currentStatus === null ? ' active' : '' ?>" href="/document-intake">All
            <strong><?= array_sum($counts) ?></strong>
        </a>
        <?php foreach ($statusLabels as $key => $label): ?>
            <a class="status-badge<?= $currentStatus === $key ? ' active' : '' ?>"
               href="/document-intake?status=<?= View::e($key) ?>">
                <?= View::e($label) ?>
                <strong><?= (int) ($counts[$key] ?? 0) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$documents): ?>
        <div class="empty-state">
            <h3>No documents yet</h3>
            <p>Upload a receipt, invoice, or other document to let the AI extract the data.</p>
            <a class="primary-action" href="/document-intake/upload">Upload First Document</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Document #</th>
                        <th>Detected Type</th>
                        <th>Vendor / Customer</th>
                        <th>Uploaded</th>
                        <th>Confidence</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <?php
                        $detected = $doc['detected_document_type'] ?: 'unclassified';
                        $confidence = $doc['document_type_confidence'] !== null
                            ? round((float) $doc['document_type_confidence'] * 100) . '%'
                            : '—';
                        $party = $doc['related_vendor_name']
                            ?: trim(($doc['related_customer_first_name'] ?? '') . ' ' . ($doc['related_customer_last_name'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <a href="/document-intake/<?= (int) $doc['id'] ?>/review">
                                    <?= View::e($doc['document_number']) ?>
                                </a>
                                <br>
                                <small><?= View::e($doc['original_filename']) ?></small>
                            </td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $detected))) ?></td>
                            <td><?= View::e($party ?: '—') ?></td>
                            <td><?= View::e($doc['uploaded_at'] ?: $doc['created_at']) ?></td>
                            <td><?= View::e($confidence) ?></td>
                            <td>
                                <span class="status-badge">
                                    <?= View::e($statusLabels[$doc['status']] ?? ucwords(str_replace('_', ' ', $doc['status']))) ?>
                                </span>
                            </td>
                            <td><a href="/document-intake/<?= (int) $doc['id'] ?>/review">Review →</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
