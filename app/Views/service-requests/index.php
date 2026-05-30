<?php

use App\Core\View;
?>
<div class="section-actions">
    <a class="primary-action" href="/service-requests/new">New Service Request</a>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Jobs</p>
            <h2>Service Requests</h2>
        </div>
        <span class="status-badge"><?= count($serviceRequests) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/service-requests" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search number, service, status, customer" aria-label="Search service requests">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/service-requests">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$serviceRequests && $q !== ''): ?>
        <div class="empty-state">
            <h3>No service requests match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try the SR number, a status like &ldquo;pending&rdquo;, or the customer name.</p>
            <a class="secondary-action" href="/service-requests">Show all service requests</a>
        </div>
    <?php elseif (!$serviceRequests): ?>
        <div class="empty-state">
            <h3>No service requests yet</h3>
            <p>Create one directly or convert an intake when the job starts as a phone lead.</p>
            <div class="inline-actions">
                <a class="primary-action" href="/service-requests/new">Create Service Request</a>
                <a class="secondary-action" href="/intake/new">New Intake</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($serviceRequests as $request): ?>
                        <tr>
                            <td><a href="/service-requests/<?= (int) $request['id'] ?>"><?= View::e($request['service_request_number']) ?></a></td>
                            <td><?= View::e($request['first_name'] . ' ' . $request['last_name']) ?></td>
                            <td><?= View::e($request['phone']) ?></td>
                            <td><?= View::e($request['requested_service']) ?></td>
                            <td><span class="status-badge status-pending"><?= View::e($request['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
