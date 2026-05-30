<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';

    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id {$id},
            email VARCHAR(180) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(180) NOT NULL,
            role VARCHAR(40) NOT NULL DEFAULT 'operator',
            roles_json TEXT NULL,
            active TINYINT NOT NULL DEFAULT 1,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");
};
