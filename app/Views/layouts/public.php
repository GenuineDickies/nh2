<?php

use App\Core\View;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= View::e($title ?? 'Solo Roadside') ?></title>
    <link rel="stylesheet" href="<?= View::e(View::asset('assets/css/app.css')) ?>">
</head>
<body class="public-body">
<main class="public-shell">
    <header class="public-header">
        <div class="brand">
            <span class="brand-mark">SR</span>
            <div>
                <strong>Solo Roadside</strong>
                <small>Customer portal</small>
            </div>
        </div>
    </header>
    <section class="public-content">
        <?= View::render($content, get_defined_vars()) ?>
    </section>
    <footer class="public-footer">
        <p class="muted">This link is unique to you. Do not share it.</p>
    </footer>
</main>
</body>
</html>
