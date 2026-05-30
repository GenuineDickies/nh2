<?php

use App\Core\View;
?>
<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Billing</p>
            <h2>Invoices</h2>
        </div>
        <span class="status-badge"><?= count($invoices) ?><?= $q !== '' ? ' matching' : ' total' ?></span>
    </div>

    <form class="list-search" method="get" action="/invoices" role="search">
        <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search invoice, service request, customer, status" aria-label="Search invoices">
        <button class="secondary-action" type="submit">Search</button>
        <?php if ($q !== ''): ?>
            <a class="muted" href="/invoices">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (!$invoices && $q !== ''): ?>
        <div class="empty-state">
            <h3>No invoices match &ldquo;<?= View::e($q) ?>&rdquo;</h3>
            <p>Try an invoice number like &ldquo;INV-&rdquo;, a status like &ldquo;sent&rdquo;, or the customer name.</p>
            <a class="secondary-action" href="/invoices">Show all invoices</a>
        </div>
    <?php elseif (!$invoices): ?>
        <div class="empty-state">
            <h3>No invoices yet</h3>
            <p>Generate invoices from completed service reports.</p>
            <a class="primary-action" href="/work-orders">Open Work Orders</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Service Request</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><a href="/invoices/<?= (int) $invoice['id'] ?>"><?= View::e($invoice['invoice_number']) ?></a></td>
                            <td><?= View::e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></td>
                            <td><?= View::e($invoice['service_request_number']) ?></td>
                            <td><span class="status-badge"><?= View::e($invoice['status']) ?></span></td>
                            <td>$<?= View::e(number_format((float) $invoice['total'], 2)) ?></td>
                            <td>$<?= View::e(number_format((float) $invoice['balance_due'], 2)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
