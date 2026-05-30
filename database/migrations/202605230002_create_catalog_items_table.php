<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS catalog_items (
            id {$id},
            sku VARCHAR(80) NOT NULL,
            item_type VARCHAR(30) NOT NULL,
            name VARCHAR(160) NOT NULL,
            category VARCHAR(100) NOT NULL,
            price DECIMAL(10, 2) NOT NULL DEFAULT 0,
            price_type VARCHAR(40) NOT NULL DEFAULT 'flat_rate',
            taxable {$bool} NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            short_description VARCHAR(255) NULL,
            long_description TEXT NULL,
            income_account_id {$fkUnsigned} NULL,
            cogs_account_id {$fkUnsigned} NULL,
            expense_account_id {$fkUnsigned} NULL,
            warranty_eligible {$bool} NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

    if ($driver === 'mysql') {
        $db->exec('CREATE INDEX idx_catalog_items_item_type ON catalog_items (item_type)');
        $db->exec('CREATE INDEX idx_catalog_items_status ON catalog_items (status)');
    } else {
        $db->exec('CREATE INDEX IF NOT EXISTS idx_catalog_items_item_type ON catalog_items (item_type)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_catalog_items_status ON catalog_items (status)');
    }
};
