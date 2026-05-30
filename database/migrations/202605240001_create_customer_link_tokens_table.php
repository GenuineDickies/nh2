<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS customer_link_tokens (
            id {$id},
            token VARCHAR(80) NOT NULL UNIQUE,
            related_type VARCHAR(40) NOT NULL,
            related_id {$fkUnsigned} NOT NULL,
            purpose VARCHAR(40) NOT NULL,
            single_use TINYINT NOT NULL DEFAULT 1,
            expires_at DATETIME NULL,
            used_at DATETIME NULL,
            created_by {$fkUnsigned} NULL,
            created_at DATETIME NOT NULL
        )
    ");

    if ($driver === 'sqlite') {
        $db->exec('CREATE INDEX IF NOT EXISTS idx_customer_link_tokens_related ON customer_link_tokens(related_type, related_id)');
    } else {
        $rows = $db->query("SHOW INDEX FROM customer_link_tokens WHERE Key_name = 'idx_customer_link_tokens_related'")->fetchAll();
        if (!$rows) {
            $db->exec('CREATE INDEX idx_customer_link_tokens_related ON customer_link_tokens(related_type, related_id)');
        }
    }
};
