<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id {$id},
            user_id {$fkUnsigned} NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            requested_ip VARCHAR(45) NULL,
            created_at DATETIME NOT NULL
        )
    ");

    if ($driver === 'sqlite') {
        $db->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user ON password_reset_tokens(user_id)');
    } else {
        $rows = $db->query(
            "SHOW INDEX FROM password_reset_tokens WHERE Key_name = 'idx_password_reset_tokens_user'"
        )->fetchAll();
        if (!$rows) {
            $db->exec('CREATE INDEX idx_password_reset_tokens_user ON password_reset_tokens(user_id)');
        }
    }
};
