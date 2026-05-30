<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">People</p>
            <h2>Customers</h2>
        </div>
        <span class="status-badge"><?= count($customers) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/customers" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search name, phone, email" aria-label="Search customers">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/customers">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$customers && $q !== ''): ?>
        <div class="empty-state">
            <h3>No customers match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try a partial name or the last few digits of a phone number.</p>
            <a class="secondary-action" href="/customers">Show all customers</a>
        </div>
    <?php elseif (!$customers): ?>
        <div class="empty-state">
            <h3>No customers yet</h3>
            <p>Customers are created when an intake is converted or a service request is created.</p>
            <div class="inline-actions">
                <a class="primary-action" href="/intake/new">New Intake</a>
                <a class="secondary-action" href="/service-requests/new">New Service Request</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Vehicles</th>
                        <th>Requests</th>
                        <th>Last Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><a href="/customers/<?= (int) $customer['id'] ?>"><?= View::e($customer['first_name'] . ' ' . $customer['last_name']) ?></a></td>
                            <td><a href="tel:<?= View::e($customer['phone']) ?>"><?= View::e($customer['phone']) ?></a></td>
                            <td><?= (int) $customer['vehicle_count'] ?></td>
                            <td><?= (int) $customer['service_request_count'] ?></td>
                            <td><?= View::e($customer['last_service_at'] ?: 'None yet') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
