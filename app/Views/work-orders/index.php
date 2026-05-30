<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Dispatch</p>
            <h2>Work Orders</h2>
        </div>
        <span class="status-badge"><?= count($workOrders) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/work-orders" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search work order, SR, estimate, customer, status" aria-label="Search work orders">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/work-orders">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$workOrders && $q !== ''): ?>
        <div class="empty-state">
            <h3>No work orders match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try a WO number, a status like &ldquo;dispatched&rdquo;, or the customer name.</p>
            <a class="secondary-action" href="/work-orders">Show all work orders</a>
        </div>
    <?php elseif (!$workOrders): ?>
        <div class="empty-state">
            <h3>No work orders yet</h3>
            <p>Approve an estimate, then create the work order from that estimate.</p>
            <a class="primary-action" href="/estimates">Open Estimates</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Work Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Estimate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workOrders as $workOrder): ?>
                        <tr>
                            <td><a href="/work-orders/<?= (int) $workOrder['id'] ?>"><?= View::e($workOrder['work_order_number']) ?></a></td>
                            <td><?= View::e($workOrder['first_name'] . ' ' . $workOrder['last_name']) ?></td>
                            <td><?= View::e($workOrder['phone']) ?></td>
                            <td><?= View::e($workOrder['requested_service']) ?></td>
                            <td><?= View::e($workOrder['estimate_number'] ?: 'None') ?></td>
                            <td><span class="status-badge"><?= View::e($workOrder['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
