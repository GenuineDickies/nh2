<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS customer_approvals (
            id {$id},
            approval_number VARCHAR(32) NOT NULL UNIQUE,
            service_request_id {$fkUnsigned} NOT NULL,
            estimate_id {$fkUnsigned} NULL,
            invoice_id {$fkUnsigned} NULL,
            change_of_service_id {$fkUnsigned} NULL,
            approval_type VARCHAR(40) NOT NULL,
            customer_name VARCHAR(200) NOT NULL,
            approval_method VARCHAR(40) NOT NULL,
            signature_file_id {$fkUnsigned} NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            latitude DECIMAL(10, 7) NULL,
            longitude DECIMAL(10, 7) NULL,
            approved_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (estimate_id) REFERENCES estimates(id)
        )
    ");
};

