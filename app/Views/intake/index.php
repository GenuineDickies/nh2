<?php

use App\Core\View;
?>
<div class="section-actions">
    <a class="primary-action" href="/intake/new">New Intake</a>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Call capture</p>
            <h2>Intake Queue</h2>
        </div>
        <span class="status-badge"><?= count($intakes) ?> total</span>
    </div>

    <?php if (!$intakes): ?>
        <div class="empty-state">
            <h3>No intake records yet</h3>
            <p>Start with a phone call, save the basics, then convert it into a service request.</p>
            <a class="primary-action" href="/intake/new">Capture First Intake</a>
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
                    <?php foreach ($intakes as $intake): ?>
                        <tr>
                            <td><a href="/intake/<?= (int) $intake['id'] ?>"><?= View::e($intake['intake_number']) ?></a></td>
                            <td><?= View::e($intake['first_name'] . ' ' . $intake['last_name']) ?></td>
                            <td><?= View::e($intake['phone']) ?></td>
                            <td><?= View::e($intake['service_requested']) ?></td>
                            <td><span class="status-badge"><?= View::e($intake['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

