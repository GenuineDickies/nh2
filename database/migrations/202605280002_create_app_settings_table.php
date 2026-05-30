<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS app_settings (
            id {$id},
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at DATETIME NOT NULL,
            updated_by {$fkUnsigned} NULL
        )
    ");
};
