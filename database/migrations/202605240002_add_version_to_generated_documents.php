<?php

return function (PDO $db, string $driver): void {
    $columns = $driver === 'mysql'
        ? $db->query("SHOW COLUMNS FROM generated_documents")->fetchAll(PDO::FETCH_ASSOC)
        : $db->query("PRAGMA table_info(generated_documents)")->fetchAll(PDO::FETCH_ASSOC);

    $existing = [];
    foreach ($columns as $col) {
        $name = $driver === 'mysql' ? $col['Field'] : $col['name'];
        $existing[$name] = true;
    }

    if (!isset($existing['version'])) {
        $db->exec('ALTER TABLE generated_documents ADD COLUMN version INTEGER NOT NULL DEFAULT 1');
    }
    if (!isset($existing['superseded_at'])) {
        $db->exec('ALTER TABLE generated_documents ADD COLUMN superseded_at DATETIME NULL');
    }
};
