<?php

use App\Core\View;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Error') ?></title>
    <link rel="stylesheet" href="<?= View::e(View::asset('assets/css/app.css')) ?>">
</head>
<body>
<main class="error-page">
    <div class="panel">
        <p class="eyebrow">Something needs attention</p>
        <h1><?= View::e($title ?? 'Error') ?></h1>
        <p><?= View::e($message ?? 'The requested page could not be loaded.') ?></p>
        <a class="primary-action" href="/dashboard">Back to Dashboard</a>
    </div>
</main>
</body>
</html>
