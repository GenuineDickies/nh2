<?php

use App\Core\View;

$leadSources = ['direct', 'google_ads', 'repeat_customer', 'referral', 'broker', 'other'];
$old = $old ?? [];
$errors = $errors ?? [];
?>
<form class="form-grid" method="post" action="/service-requests" novalidate>
    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Customer</p>
                <h2>Customer Details</h2>
            </div>
        </div>
        <div class="fields two">
            <label>First name
                <input name="first_name" value="<?= View::e($old['first_name'] ?? '') ?>" required>
                <?php if (isset($errors['first_name'])): ?><small class="field-error"><?= View::e($errors['first_name']) ?></small><?php endif; ?>
            </label>
            <label>Last name
                <input name="last_name" value="<?= View::e($old['last_name'] ?? '') ?>" required>
                <?php if (isset($errors['last_name'])): ?><small class="field-error"><?= View::e($errors['last_name']) ?></small><?php endif; ?>
            </label>
            <label>Phone
                <input name="phone" data-phone-mask inputmode="tel" value="<?= View::e($old['phone'] ?? '') ?>" placeholder="(555) 123-4567" required>
                <?php if (isset($errors['phone'])): ?><small class="field-error"><?= View::e($errors['phone']) ?></small><?php endif; ?>
            </label>
            <label>Lead source
                <select name="lead_source">
                    <?php foreach ($leadSources as $source): ?>
                        <option value="<?= View::e($source) ?>" <?= ($old['lead_source'] ?? 'direct') === $source ? 'selected' : '' ?>><?= View::e(ucwords(str_replace('_', ' ', $source))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Job</p>
                <h2>Service Request</h2>
            </div>
        </div>
        <div class="fields two">
            <label>Requested service
                <input name="requested_service" value="<?= View::e($old['requested_service'] ?? '') ?>" required>
                <?php if (isset($errors['requested_service'])): ?><small class="field-error"><?= View::e($errors['requested_service']) ?></small><?php endif; ?>
            </label>
            <label>Priority
                <select name="priority">
                    <option value="normal" <?= ($old['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="urgent" <?= ($old['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                </select>
                <?php if (isset($errors['priority'])): ?><small class="field-error"><?= View::e($errors['priority']) ?></small><?php endif; ?>
            </label>
        </div>
        <label>Problem description
            <textarea name="problem_description" rows="4"><?= View::e($old['problem_description'] ?? '') ?></textarea>
        </label>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Location</p>
                <h2>Where to Go</h2>
            </div>
        </div>
        <label>Address
            <input name="location_address" value="<?= View::e($old['location_address'] ?? '') ?>" required>
            <?php if (isset($errors['location_address'])): ?><small class="field-error"><?= View::e($errors['location_address']) ?></small><?php endif; ?>
        </label>
        <div class="fields three">
            <label>City
                <input name="location_city" value="<?= View::e($old['location_city'] ?? '') ?>">
            </label>
            <label>State
                <input name="location_state" value="<?= View::e($old['location_state'] ?? '') ?>">
            </label>
            <label>Postal code
                <input name="location_postal_code" value="<?= View::e($old['location_postal_code'] ?? '') ?>">
            </label>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Vehicle</p>
                <h2>Basic Vehicle Info</h2>
            </div>
        </div>
        <div class="fields four">
            <label>Year
                <input name="vehicle_year" inputmode="numeric" value="<?= View::e($old['vehicle_year'] ?? '') ?>">
            </label>
            <label>Make
                <input name="vehicle_make" value="<?= View::e($old['vehicle_make'] ?? '') ?>">
            </label>
            <label>Model
                <input name="vehicle_model" value="<?= View::e($old['vehicle_model'] ?? '') ?>">
            </label>
            <label>Color
                <input name="vehicle_color" value="<?= View::e($old['vehicle_color'] ?? '') ?>">
            </label>
        </div>
    </div>

    <div class="sticky-actions">
        <a class="secondary-action" href="/service-requests">Cancel</a>
        <button class="primary-action" type="submit">Create Service Request</button>
    </div>
</form>
