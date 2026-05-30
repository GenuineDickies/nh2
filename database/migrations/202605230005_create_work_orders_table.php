<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS work_orders (
            id {$id},
            work_order_number VARCHAR(32) NOT NULL UNIQUE,
            service_request_id {$fkUnsigned} NOT NULL,
            estimate_id {$fkUnsigned} NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            assigned_user_id {$fkUnsigned} NULL,
            dispatch_started_at DATETIME NULL,
            arrived_at DATETIME NULL,
            completed_at DATETIME NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (estimate_id) REFERENCES estimates(id)
        )
    ");
};

