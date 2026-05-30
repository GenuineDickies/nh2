<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Core\MigrationRunner;

require dirname(__DIR__) . '/app/bootstrap.php';

Env::load(dirname(__DIR__) . '/.env');

$ran = (new MigrationRunner(Database::connection()))->run(dirname(__DIR__) . '/database/migrations');

if (!$ran) {
    echo "No migrations to run.\n";
    exit;
}

foreach ($ran as $migration) {
    echo "Migrated: {$migration}\n";
}

