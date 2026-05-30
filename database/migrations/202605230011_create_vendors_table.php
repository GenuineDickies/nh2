<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $db->exec("
        CREATE TABLE IF NOT EXISTS vendors (
            id {$id},
            name VARCHAR(180) NOT NULL,
            phone VARCHAR(40) NULL,
            email VARCHAR(180) NULL,
            website VARCHAR(255) NULL,
            address VARCHAR(255) NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");
};
