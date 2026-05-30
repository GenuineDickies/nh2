<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS service_completion_reports (
            id {$id},
            report_number VARCHAR(32) NOT NULL UNIQUE,
            service_request_id {$fkUnsigned} NOT NULL,
            work_order_id {$fkUnsigned} NOT NULL,
            customer_id {$fkUnsigned} NOT NULL,
            vehicle_id {$fkUnsigned} NULL,
            actual_work_performed TEXT NOT NULL,
            technician_notes TEXT NULL,
            customer_notes TEXT NULL,
            odometer VARCHAR(30) NULL,
            vin_captured VARCHAR(32) NULL,
            no_vehicle_serviced_flag {$bool} NOT NULL DEFAULT 0,
            completion_status VARCHAR(30) NOT NULL DEFAULT 'completed',
            completed_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )
    ");
};

