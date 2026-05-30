<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Accounting</p>
            <h2>Ledger</h2>
        </div>
        <a class="secondary-action" href="/accounting/accounts">Chart of Accounts</a>
    </div>

    <?php if (!$entries): ?>
        <div class="empty-state">
            <h3>No ledger entries yet</h3>
            <p>Issue an invoice or record a payment to create accounting entries.</p>
            <a class="primary-action" href="/invoices">Open Invoices</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Entry</th>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Memo</th>
                        <th>Debits</th>
                        <th>Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><a href="/accounting/ledger/<?= (int) $entry['id'] ?>"><?= View::e($entry['entry_number']) ?></a></td>
                            <td><?= View::e($entry['entry_date']) ?></td>
                            <td><?= View::e(ucwords(str_replace('_', ' ', $entry['source_type'])) . ' #' . $entry['source_id']) ?></td>
                            <td><?= View::e($entry['memo']) ?></td>
                            <td>$<?= View::e(number_format((float) $entry['debit_total'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $entry['credit_total'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
