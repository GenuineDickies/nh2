<?php

use App\Core\View;
?>
<form method="post" action="/setup" class="auth-form" novalidate>
    <header class="auth-header">
        <p class="eyebrow">First-run setup</p>
        <h1>Create the operator account</h1>
        <p class="muted">No accounts exist yet. Create the first one to start using the app. This account will have admin permissions.</p>
    </header>

    <?php if ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <label>Your name
        <input name="name" value="<?= View::e($values['name'] ?? '') ?>" required>
        <?php if (isset($errors['name'])): ?><small class="field-error"><?= View::e($errors['name']) ?></small><?php endif; ?>
    </label>

    <label>Email
        <input name="email" type="email" value="<?= View::e($values['email'] ?? '') ?>" autocomplete="username" required>
        <?php if (isset($errors['email'])): ?><small class="field-error"><?= View::e($errors['email']) ?></small><?php endif; ?>
    </label>

    <label>Password (8 characters minimum)
        <input name="password" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?><small class="field-error"><?= View::e($errors['password']) ?></small><?php endif; ?>
    </label>

    <label>Confirm password
        <input name="password_confirm" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password_confirm'])): ?><small class="field-error"><?= View::e($errors['password_confirm']) ?></small><?php endif; ?>
    </label>

    <button class="primary-action" type="submit">Create Account</button>
</form>
