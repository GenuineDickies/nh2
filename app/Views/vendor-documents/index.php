<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Supply</p>
            <h2>Vendor Documents</h2>
        </div>
        <div class="inline-actions">
            <span class="status-badge"><?= count($documents) ?> total</span>
            <a class="primary-action" href="/vendor-documents/upload">Upload Document</a>
        </div>
    </div>

    <?php if (!$documents): ?>
        <div class="empty-state">
            <h3>No vendor documents yet</h3>
            <p>Upload a parts receipt or supplier invoice to start tracking expenses.</p>
            <a class="primary-action" href="/vendor-documents/upload">Upload First Document</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Vendor</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><a href="/vendor-documents/<?= (int) $document['id'] ?>"><?= View::e($document['document_number']) ?></a></td>
                            <td><?= View::e($document['vendor_name'] ?: '--') ?></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $document['document_type']))) ?></td>
                            <td><?= View::e($document['document_date'] ?: $document['uploaded_at']) ?></td>
                            <td>$<?= View::e(number_format((float) $document['total'], 2)) ?></td>
                            <td><span class="status-badge"><?= View::e(ucwords(str_replace('_', ' ', $document['status']))) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
