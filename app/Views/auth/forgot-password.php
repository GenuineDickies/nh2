<?php

use App\Core\View;

$email = $email ?? '';
?>
<header class="auth-header">
    <p class="eyebrow">Password reset</p>
    <h1>Check your email</h1>
</header>

<p>
    If an account exists for
    <strong><?= View::e($email !== '' ? $email : 'that address') ?></strong>,
    we've sent a link you can use to choose a new password. The link is good for one hour
    and can only be used once.
</p>

<p>
    Didn't get anything? Check your spam folder, then try the
    <a href="/login">sign-in page</a> again — make sure you typed the email exactly as it
    was registered.
</p>

<p class="auth-aux">
    <a class="link-button" href="/login">Back to sign in</a>
</p>
