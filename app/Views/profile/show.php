<?php

use App\Core\View;
use App\Models\User;

$nameValue = $values['name'] ?? $profile['name'];
$emailValue = $values['email'] ?? $profile['email'];
$selectedRoles = User::normalizeRoles($values['roles'] ?? User::rolesFromUser($profile));
$roleLabel = User::roleLabels($selectedRoles);
$roleStatus = implode(' ', $selectedRoles);
$isOwnProfile = (int) $actor['id'] === (int) $profile['id'];
$profilePath = $isOwnProfile ? '/profile' : '/users/' . (int) $profile['id'] . '/profile';
$passwordPath = $isOwnProfile ? '/profile/password' : '/users/' . (int) $profile['id'] . '/profile/password';
?>

<div class="detail-grid">
    <article class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Account</p>
                <h2>User Profile</h2>
            </div>
            <span class="status-badge" data-status="<?= View::e($roleStatus) ?>">
                <?= View::e($roleLabel) ?>
            </span>
        </div>

        <?php if ($flash !== ''): ?>
            <p class="alert alert-success"><?= View::e($flash) ?></p>
        <?php endif; ?>

        <?php if (isset($errors['form'])): ?>
            <p class="alert"><?= View::e((string) $errors['form']) ?></p>
        <?php endif; ?>

        <form class="form-grid" method="post" action="<?= View::e($profilePath) ?>">
            <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">

            <label>
                Full name
                <input name="name" value="<?= View::e((string) $nameValue) ?>" required>
                <?php if (isset($errors['name'])): ?>
                    <small class="field-error"><?= View::e((string) $errors['name']) ?></small>
                <?php endif; ?>
            </label>

            <label>
                Email
                <input name="email" type="email" value="<?= View::e((string) $emailValue) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <small class="field-error"><?= View::e((string) $errors['email']) ?></small>
                <?php endif; ?>
            </label>

            <fieldset class="role-choice-group">
                <legend>Roles</legend>
                <p class="muted">Choose every role this user should have.</p>
                <?php foreach ($availableRoles as $role => $label): ?>
                    <label class="role-choice">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="<?= View::e((string) $role) ?>"
                            <?= in_array($role, $selectedRoles, true) ? 'checked' : '' ?>
                            <?= $canManageUsers ? '' : 'disabled' ?>
                        >
                        <span><?= View::e((string) $label) ?></span>
                    </label>
                <?php endforeach; ?>
                <?php if (!$canManageUsers): ?>
                    <?php foreach ($selectedRoles as $role): ?>
                        <input type="hidden" name="roles[]" value="<?= View::e((string) $role) ?>">
                    <?php endforeach; ?>
                    <small class="muted">Only an admin can change account roles.</small>
                <?php endif; ?>
                <?php if (isset($errors['roles'])): ?>
                    <small class="field-error"><?= View::e((string) $errors['roles']) ?></small>
                <?php endif; ?>
            </fieldset>

            <div class="stacked-actions">
                <button class="primary-action" type="submit">Save Profile</button>
            </div>
        </form>
    </article>

    <aside class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Security</p>
                <h2>Change Password</h2>
            </div>
        </div>

        <?php if (isset($errors['password_form'])): ?>
            <p class="alert"><?= View::e((string) $errors['password_form']) ?></p>
        <?php endif; ?>

        <form class="form-grid" method="post" action="<?= View::e($passwordPath) ?>">
            <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">

            <?php if ($isOwnProfile): ?>
                <label>
                    Current password
                    <input name="current_password" type="password" autocomplete="current-password" required>
                    <?php if (isset($errors['current_password'])): ?>
                        <small class="field-error"><?= View::e((string) $errors['current_password']) ?></small>
                    <?php endif; ?>
                </label>
            <?php else: ?>
                <p class="muted">Admin reset for <?= View::e((string) $profile['email']) ?>.</p>
            <?php endif; ?>

            <label>
                New password
                <input name="new_password" type="password" minlength="8" autocomplete="new-password" required>
                <?php if (isset($errors['new_password'])): ?>
                    <small class="field-error"><?= View::e((string) $errors['new_password']) ?></small>
                <?php endif; ?>
            </label>

            <label>
                Confirm password
                <input name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <small class="field-error"><?= View::e((string) $errors['confirm_password']) ?></small>
                <?php endif; ?>
            </label>

            <div class="stacked-actions">
                <button class="secondary-action" type="submit">Update Password</button>
            </div>
        </form>
    </aside>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Profile Details</p>
            <h2>Account Snapshot</h2>
        </div>
    </div>

    <dl class="details">
        <dt>User ID</dt>
        <dd><?= (int) $profile['id'] ?></dd>
        <dt>Email</dt>
        <dd><?= View::e((string) $profile['email']) ?></dd>
        <dt>Name</dt>
        <dd><?= View::e((string) $profile['name']) ?></dd>
        <dt>Roles</dt>
        <dd><?= View::e($roleLabel) ?></dd>
        <dt>Last Login</dt>
        <dd><?= View::e((string) ($profile['last_login_at'] ?: 'Never')) ?></dd>
        <dt>Account Status</dt>
        <dd><?= ((int) $profile['active'] === 1) ? 'Active' : 'Inactive' ?></dd>
    </dl>

    <?php if ($canManageUsers): ?>
        <p class="muted">Admin access is enabled for this account.</p>
    <?php endif; ?>
</div>
