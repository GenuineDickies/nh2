<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Accounting</p>
            <h2>Chart of Accounts</h2>
        </div>
        <a class="secondary-action" href="/accounting/ledger">Ledger</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?= View::e($account['account_code']) ?></td>
                        <td><?= View::e($account['account_name']) ?></td>
                        <td><?= View::e(ucwords($account['account_type'])) ?></td>
                        <td><span class="status-badge"><?= ((int) $account['active']) === 1 ? 'active' : 'inactive' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
