<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Reports</p>
            <h2>Payments Collected</h2>
        </div>
        <a class="secondary-action" href="/reports">Reports</a>
    </div>
    <?php if (!$rows): ?>
        <div class="empty-state">
            <h3>No payments yet</h3>
            <p>Completed payments will appear here.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Count</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= View::e($row['report_date']) ?></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $row['payment_method']))) ?></td>
                            <td><?= (int) $row['payment_count'] ?></td>
                            <td>$<?= View::e(number_format((float) $row['total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
