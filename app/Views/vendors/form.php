<?php

use App\Core\View;

$action = $vendor ? '/vendors/' . (int) $vendor['id'] : '/vendors';
$isEdit = $vendor !== null;
$cancelHref = $isEdit ? '/vendors/' . (int) $vendor['id'] : '/vendors';
?>
<form class="form-grid" method="post" action="<?= $action ?>" novalidate>
    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Vendor</p>
                <h2><?= $isEdit ? 'Edit Vendor' : 'New Vendor' ?></h2>
            </div>
        </div>
        <div class="fields two">
            <label>Name
                <input name="name" value="<?= View::e($data['name'] ?? '') ?>" required>
                <?php if (isset($errors['name'])): ?><small class="field-error"><?= View::e($errors['name']) ?></small><?php endif; ?>
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (['active', 'inactive'] as $status): ?>
                        <option value="<?= $status ?>" <?= ($data['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= ucwords($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><small class="field-error"><?= View::e($errors['status']) ?></small><?php endif; ?>
            </label>
            <label>Phone
                <input name="phone" data-phone-mask inputmode="tel" value="<?= View::e($data['phone'] ?? '') ?>" placeholder="(555) 123-4567">
            </label>
            <label>Email
                <input name="email" type="email" value="<?= View::e($data['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?><small class="field-error"><?= View::e($errors['email']) ?></small><?php endif; ?>
            </label>
        </div>
        <div class="fields">
            <label>Website
                <input name="website" type="url" value="<?= View::e($data['website'] ?? '') ?>" placeholder="https://">
                <?php if (isset($errors['website'])): ?><small class="field-error"><?= View::e($errors['website']) ?></small><?php endif; ?>
            </label>
            <label>Address
                <input name="address" value="<?= View::e($data['address'] ?? '') ?>" placeholder="Street, City, State Postal Code">
            </label>
            <label>Notes
                <textarea name="notes" rows="4"><?= View::e($data['notes'] ?? '') ?></textarea>
            </label>
        </div>
    </div>

    <div class="sticky-actions">
        <a class="secondary-action" href="<?= $cancelHref ?>">Cancel</a>
        <button class="primary-action" type="submit"><?= $isEdit ? 'Save Changes' : 'Create Vendor' ?></button>
    </div>
</form>
