<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Vehicles</p>
            <h2>Known Vehicles</h2>
        </div>
        <span class="status-badge"><?= count($vehicles) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/vehicles" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search VIN, plate, make, model, customer" aria-label="Search vehicles">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/vehicles">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$vehicles && $q !== ''): ?>
        <div class="empty-state">
            <h3>No vehicles match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try the last six digits of a VIN, a plate, or the customer name.</p>
            <a class="secondary-action" href="/vehicles">Show all vehicles</a>
        </div>
    <?php elseif (!$vehicles): ?>
        <div class="empty-state">
            <h3>No vehicles yet</h3>
            <p>Vehicles are captured from intake conversion and service request creation.</p>
            <a class="primary-action" href="/intake/new">New Intake</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>VIN</th>
                        <th>Requests</th>
                        <th>Last Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><a href="/vehicles/<?= (int) $vehicle['id'] ?>"><?= View::e(trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?: 'Vehicle') ?></a></td>
                            <td><?= View::e(trim(($vehicle['first_name'] ?? '') . ' ' . ($vehicle['last_name'] ?? '')) ?: 'Unassigned') ?></td>
                            <td><?= View::e($vehicle['phone'] ?: 'Not captured') ?></td>
                            <td><?= View::e($vehicle['vin'] ?: 'Missing') ?></td>
                            <td><?= (int) $vehicle['service_request_count'] ?></td>
                            <td><?= View::e($vehicle['last_service_at'] ?: 'None yet') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
