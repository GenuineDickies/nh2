<?php

use App\Core\View;

$isServices = $mode === 'services';
$newPath = $isServices ? '/catalog/services/new' : '/catalog/items/new';
$otherPath = $isServices ? '/catalog/items' : '/catalog/services';
$otherLabel = $isServices ? 'Parts & Materials' : 'Services';
?>
<div class="section-actions split-actions">
    <a class="primary-action" href="<?= View::e($newPath) ?>"><?= $isServices ? 'New Service' : 'New Item' ?></a>
    <a class="secondary-action" href="<?= View::e($otherPath) ?>"><?= View::e($otherLabel) ?></a>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Catalog</p>
            <h2><?= $isServices ? 'Services' : 'Parts & Materials' ?></h2>
        </div>
        <span class="status-badge"><?= count($items) ?> total</span>
    </div>

    <?php if (!$items): ?>
        <div class="empty-state">
            <h3>No catalog items yet</h3>
            <p><?= $isServices ? 'Add services, labor, and fees used for estimates.' : 'Add parts and materials used for estimates and invoices.' ?></p>
            <a class="primary-action" href="<?= View::e($newPath) ?>"><?= $isServices ? 'Create First Service' : 'Create First Item' ?></a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Part #</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Price Type</th>
                        <th>Taxable</th>
                        <th>Status</th>
                        <th>Short Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><a href="<?= View::e(($isServices ? '/catalog/services/' : '/catalog/items/') . (int) $item['id'] . '/edit') ?>"><?= View::e($item['name']) ?></a></td>
                            <td><?= View::e($item['sku']) ?></td>
                            <td><?= View::e($item['category']) ?></td>
                            <td>$<?= View::e(number_format((float) $item['price'], 2)) ?></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $item['price_type']))) ?></td>
                            <td><?= ((int) $item['taxable']) === 1 ? 'Yes' : 'No' ?></td>
                            <td><span class="status-badge <?= $item['status'] === 'inactive' ? 'status-muted' : '' ?>"><?= View::e($item['status']) ?></span></td>
                            <td><?= View::e($item['short_description'] ?: 'None') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

