<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Quotes</p>
            <h2>Estimates</h2>
        </div>
        <span class="status-badge"><?= count($estimates) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/estimates" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search estimate, SR, service, customer, status" aria-label="Search estimates">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/estimates">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$estimates && $q !== ''): ?>
        <div class="empty-state">
            <h3>No estimates match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try the estimate number, a status like &ldquo;draft&rdquo;, or the customer name.</p>
            <a class="secondary-action" href="/estimates">Show all estimates</a>
        </div>
    <?php elseif (!$estimates): ?>
        <div class="empty-state">
            <h3>No estimates yet</h3>
            <p>Create an estimate from a service request.</p>
            <a class="primary-action" href="/service-requests">Open Service Requests</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Estimate</th>
                        <th>Customer</th>
                        <th>Service Request</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estimates as $estimate): ?>
                        <tr>
                            <td><a href="/estimates/<?= (int) $estimate['id'] ?>"><?= View::e($estimate['estimate_number']) ?></a></td>
                            <td><?= View::e($estimate['first_name'] . ' ' . $estimate['last_name']) ?></td>
                            <td><?= View::e($estimate['service_request_number']) ?></td>
                            <td><?= View::e($estimate['requested_service']) ?></td>
                            <td><span class="status-badge"><?= View::e($estimate['status']) ?></span></td>
                            <td>$<?= View::e(number_format((float) $estimate['total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
