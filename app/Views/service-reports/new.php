<?php

use App\Core\View;
use App\Models\ServiceCompletionReport;

$old = $old ?? [];
$errors = $errors ?? [];
?>
<?php if (!$workOrder): ?>
    <div class="panel">
        <div class="empty-state">
            <h3>Choose a work order</h3>
            <p>A service report must start from a work order.</p>
            <a class="primary-action" href="/work-orders">Open Work Orders</a>
        </div>
    </div>
<?php else: ?>
    <form class="form-grid" method="post" action="/service-reports" novalidate>
        <input type="hidden" name="work_order_id" value="<?= (int) $workOrder['id'] ?>">
        <?php if ($errors): ?>
            <div class="alert">Please fix the highlighted fields.</div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Service Report</p>
                    <h2><?= View::e($workOrder['work_order_number']) ?></h2>
                </div>
                <span class="status-badge"><?= View::e($workOrder['status']) ?></span>
            </div>
            <dl class="details">
                <dt>Customer</dt>
                <dd><?= View::e($workOrder['first_name'] . ' ' . $workOrder['last_name']) ?></dd>
                <dt>Service</dt>
                <dd><?= View::e($workOrder['requested_service']) ?></dd>
                <dt>Vehicle</dt>
                <dd><?= View::e(trim(($workOrder['year'] ?? '') . ' ' . ($workOrder['make'] ?? '') . ' ' . ($workOrder['model'] ?? '')) ?: 'Not captured') ?></dd>
            </dl>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Completion</p>
                    <h2>Actual Work</h2>
                </div>
            </div>
            <label>Actual work performed
                <textarea name="actual_work_performed" rows="5" required><?= View::e($old['actual_work_performed'] ?? '') ?></textarea>
                <?php if (isset($errors['actual_work_performed'])): ?><small class="field-error"><?= View::e($errors['actual_work_performed']) ?></small><?php endif; ?>
            </label>
            <label>Completion status
                <select name="completion_status">
                    <?php foreach (ServiceCompletionReport::STATUSES as $status): ?>
                        <option value="<?= View::e($status) ?>" <?= ($old['completion_status'] ?? 'completed') === $status ? 'selected' : '' ?>><?= View::e(ucwords($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['completion_status'])): ?><small class="field-error"><?= View::e($errors['completion_status']) ?></small><?php endif; ?>
            </label>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Vehicle</p>
                    <h2>VIN and Odometer</h2>
                </div>
            </div>
            <div class="fields three">
                <label>VIN captured
                    <input name="vin_captured" value="<?= View::e($old['vin_captured'] ?? '') ?>">
                    <?php if (isset($errors['vin_captured'])): ?><small class="field-error"><?= View::e($errors['vin_captured']) ?></small><?php endif; ?>
                </label>
                <label>Odometer
                    <input name="odometer" inputmode="numeric" value="<?= View::e($old['odometer'] ?? '') ?>">
                </label>
                <label>No vehicle serviced
                    <select name="no_vehicle_serviced_flag">
                        <option value="0" <?= empty($old['no_vehicle_serviced_flag']) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= !empty($old['no_vehicle_serviced_flag']) ? 'selected' : '' ?>>Yes</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Notes</p>
                    <h2>Field Notes</h2>
                </div>
            </div>
            <label>Technician notes
                <textarea name="technician_notes" rows="4"><?= View::e($old['technician_notes'] ?? '') ?></textarea>
            </label>
            <label>Customer notes
                <textarea name="customer_notes" rows="3"><?= View::e($old['customer_notes'] ?? '') ?></textarea>
            </label>
        </div>

        <div class="sticky-actions">
            <a class="secondary-action" href="/work-orders/<?= (int) $workOrder['id'] ?>">Cancel</a>
            <button class="primary-action" type="submit">Create Service Report</button>
        </div>
    </form>
<?php endif; ?>

