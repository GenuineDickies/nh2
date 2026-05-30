<?php

use App\Core\View;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Solo Roadside') ?></title>
    <link rel="stylesheet" href="<?= View::e(View::asset('assets/css/app.css')) ?>">
</head>
<body class="auth-body">
<main class="auth-shell">
    <div class="brand auth-brand">
        <span class="brand-mark">SR</span>
        <div>
            <strong>Solo Roadside</strong>
            <small>Job to cash</small>
        </div>
    </div>
    <section class="auth-card">
        <?= View::render($content, get_defined_vars()) ?>
    </section>
</main>
</body>
</html>
