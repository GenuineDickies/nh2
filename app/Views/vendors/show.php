<?php

use App\Core\View;
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Vendor</p>
                <h2><?= View::e($vendor['name']) ?></h2>
            </div>
            <span class="status-badge<?= $vendor['status'] === 'inactive' ? ' status-pending' : '' ?>"><?= View::e(ucwords($vendor['status'])) ?></span>
        </div>
        <dl class="details">
            <dt>Phone</dt>
            <dd><?= $vendor['phone'] ? '<a href="tel:' . View::e($vendor['phone']) . '">' . View::e($vendor['phone']) . '</a>' : '<span class="muted">Not on file</span>' ?></dd>
            <dt>Email</dt>
            <dd><?= $vendor['email'] ? '<a href="mailto:' . View::e($vendor['email']) . '">' . View::e($vendor['email']) . '</a>' : '<span class="muted">Not on file</span>' ?></dd>
            <dt>Website</dt>
            <dd><?= $vendor['website'] ? '<a href="' . View::e($vendor['website']) . '" target="_blank" rel="noopener">' . View::e($vendor['website']) . '</a>' : '<span class="muted">Not on file</span>' ?></dd>
            <dt>Address</dt>
            <dd><?= View::e($vendor['address'] ?: 'Not on file') ?></dd>
            <dt>Added</dt>
            <dd><?= View::e($vendor['created_at']) ?></dd>
        </dl>
        <div class="stacked-actions">
            <a class="secondary-action" href="/vendors/<?= (int) $vendor['id'] ?>/edit">Edit Vendor</a>
        </div>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Notes</p>
                <h2>Vendor Notes</h2>
            </div>
        </div>
        <?php if (trim((string) ($vendor['notes'] ?? '')) === ''): ?>
            <p class="muted">No notes recorded yet.</p>
        <?php else: ?>
            <p><?= nl2br(View::e($vendor['notes'])) ?></p>
        <?php endif; ?>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Documents</p>
            <h2>Vendor Documents</h2>
        </div>
    </div>
    <p class="muted">Vendor receipt and invoice uploads will appear here once the vendor document module ships.</p>
</div>
