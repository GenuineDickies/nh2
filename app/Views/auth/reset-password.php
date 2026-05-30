<?php

use App\Core\View;

$error = $error ?? '';
$errors = $errors ?? [];
$token = $token ?? '';
?>
<form method="post" action="/reset-password" class="auth-form" novalidate>
    <header class="auth-header">
        <p class="eyebrow">Password reset</p>
        <h1>Choose a new password</h1>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= View::e($error) ?></div>
    <?php elseif ($errors): ?>
        <div class="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <input type="hidden" name="token" value="<?= View::e($token) ?>">

    <label>New password (8 characters minimum)
        <input name="password" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password'])): ?>
            <small class="field-error"><?= View::e($errors['password']) ?></small>
        <?php endif; ?>
    </label>

    <label>Confirm new password
        <input name="password_confirm" type="password" autocomplete="new-password" required>
        <?php if (isset($errors['password_confirm'])): ?>
            <small class="field-error"><?= View::e($errors['password_confirm']) ?></small>
        <?php endif; ?>
    </label>

    <button class="primary-action" type="submit">Set new password</button>
</form>
