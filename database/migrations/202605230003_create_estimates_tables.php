<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS estimates (
            id {$id},
            estimate_number VARCHAR(32) NOT NULL UNIQUE,
            service_request_id {$fkUnsigned} NOT NULL,
            customer_id {$fkUnsigned} NOT NULL,
            vehicle_id {$fkUnsigned} NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
            total DECIMAL(10, 2) NOT NULL DEFAULT 0,
            disclaimer_text TEXT NOT NULL,
            approved_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS estimate_line_items (
            id {$id},
            estimate_id {$fkUnsigned} NOT NULL,
            catalog_item_id {$fkUnsigned} NULL,
            line_type VARCHAR(30) NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity DECIMAL(10, 2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
            taxable {$bool} NOT NULL DEFAULT 0,
            line_subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (estimate_id) REFERENCES estimates(id),
            FOREIGN KEY (catalog_item_id) REFERENCES catalog_items(id)
        )
    ");
};

