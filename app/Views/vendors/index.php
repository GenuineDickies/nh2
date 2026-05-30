<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Supply</p>
            <h2>Vendors</h2>
        </div>
        <div class="inline-actions">
            <span class="status-badge"><?= count($vendors) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
            <a class="primary-action" href="/vendors/new">New Vendor</a>
        </div>
    </div>

    <form class="list-search" method="get" action="/vendors" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search name, phone, email, address" aria-label="Search vendors">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/vendors">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$vendors && $q !== ''): ?>
        <div class="empty-state">
            <h3>No vendors match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try a partial vendor name or a city.</p>
            <a class="secondary-action" href="/vendors">Show all vendors</a>
        </div>
    <?php elseif (!$vendors): ?>
        <div class="empty-state">
            <h3>No vendors yet</h3>
            <p>Add the suppliers you buy parts and materials from so vendor receipts can be linked to a vendor.</p>
            <div class="inline-actions">
                <a class="primary-action" href="/vendors/new">Add First Vendor</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td><a href="/vendors/<?= (int) $vendor['id'] ?>"><?= View::e($vendor['name']) ?></a></td>
                            <td><?= $vendor['phone'] ? '<a href="tel:' . View::e($vendor['phone']) . '">' . View::e($vendor['phone']) . '</a>' : '<span class="muted">--</span>' ?></td>
                            <td><?= $vendor['email'] ? '<a href="mailto:' . View::e($vendor['email']) . '">' . View::e($vendor['email']) . '</a>' : '<span class="muted">--</span>' ?></td>
                            <td><span class="status-badge<?= $vendor['status'] === 'inactive' ? ' status-pending' : '' ?>"><?= View::e(ucwords($vendor['status'])) ?></span></td>
                            <td><?= View::e($vendor['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
