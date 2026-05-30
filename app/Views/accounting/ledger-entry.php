<?php

use App\Core\View;

$debitTotal = 0.0;
$creditTotal = 0.0;
foreach ($entry['lines'] as $line) {
    $debitTotal += (float) $line['debit'];
    $creditTotal += (float) $line['credit'];
}
?>
<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Ledger Entry</p>
                <h2><?= View::e($entry['entry_number']) ?></h2>
            </div>
            <span class="status-badge"><?= ((int) $entry['posted']) === 1 ? 'posted' : 'draft' ?></span>
        </div>
        <dl class="details">
            <dt>Date</dt>
            <dd><?= View::e($entry['entry_date']) ?></dd>
            <dt>Source</dt>
            <dd><?= View::e(ucwords(str_replace('_', ' ', $entry['source_type'])) . ' #' . $entry['source_id']) ?></dd>
            <dt>Memo</dt>
            <dd><?= View::e($entry['memo']) ?></dd>
            <dt>Posted At</dt>
            <dd><?= View::e($entry['posted_at'] ?: 'Not posted') ?></dd>
        </dl>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Balance Check</p>
                <h2><?= round($debitTotal, 2) === round($creditTotal, 2) ? 'Balanced' : 'Out of Balance' ?></h2>
            </div>
        </div>
        <dl class="details compact-details">
            <dt>Debits</dt>
            <dd>$<?= View::e(number_format($debitTotal, 2)) ?></dd>
            <dt>Credits</dt>
            <dd>$<?= View::e(number_format($creditTotal, 2)) ?></dd>
        </dl>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Lines</p>
            <h2>Debits and Credits</h2>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Type</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Memo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entry['lines'] as $line): ?>
                    <tr>
                        <td><?= View::e($line['account_code'] . ' ' . $line['account_name']) ?></td>
                        <td><?= View::e(ucwords($line['account_type'])) ?></td>
                        <td>$<?= View::e(number_format((float) $line['debit'], 2)) ?></td>
                        <td>$<?= View::e(number_format((float) $line['credit'], 2)) ?></td>
                        <td><?= View::e($line['memo'] ?: '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
