<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Jobs Missing Records</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$jobs): ?>
        <div class="empty-state">
            <h3>No missing records</h3>
            <p>Every service request has the core estimate, work order, service report, and invoice records.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service Request</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Missing</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $missing = [];
                        if (!$job['estimate_id']) { $missing[] = 'Estimate'; }
                        if (!$job['work_order_id']) { $missing[] = 'Work order'; }
                        if (!$job['service_report_id']) { $missing[] = 'Service report'; }
                        if (!$job['invoice_id']) { $missing[] = 'Invoice'; }
                        ?>
                        <tr>
                            <td><a href="/service-requests/<?= (int) $job['id'] ?>"><?= View::e($job['service_request_number']) ?></a></td>
                            <td><?= View::e($job['first_name'] . ' ' . $job['last_name']) ?></td>
                            <td><?= View::e($job['requested_service']) ?></td>
                            <td><?= View::e(implode(', ', $missing)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
