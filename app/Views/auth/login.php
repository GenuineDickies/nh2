<?php

use App\Core\View;

$showForgotLink = $showForgotLink ?? false;
?>
<form method="post" action="/login" class="auth-form" novalidate>
    <header class="auth-header">
        <p class="eyebrow">Sign in</p>
        <h1>Operator Console</h1>
    </header>

    <?php if ($error !== ''): ?>
        <div class="alert"><?= View::e($error) ?></div>
    <?php endif; ?>

    <label>Email
        <input name="email" type="email" autocomplete="username" value="<?= View::e($email) ?>" required>
    </label>

    <label>Password
        <input name="password" type="password" autocomplete="current-password" required>
    </label>

    <button class="primary-action" type="submit">Sign in</button>
</form>

<?php if ($showForgotLink): ?>
    <form method="post" action="/forgot-password" class="auth-aux" novalidate>
        <input type="hidden" name="email" value="<?= View::e($email) ?>">
        <button type="submit" class="link-button">Forgot password?</button>
    </form>
<?php endif; ?>
