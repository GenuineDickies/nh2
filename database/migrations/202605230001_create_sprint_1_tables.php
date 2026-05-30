<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id {$id},
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL UNIQUE,
            email VARCHAR(150) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS vehicles (
            id {$id},
            customer_id {$fkUnsigned} NULL,
            vin VARCHAR(32) NULL,
            year VARCHAR(4) NULL,
            make VARCHAR(80) NULL,
            model VARCHAR(80) NULL,
            color VARCHAR(50) NULL,
            plate_number VARCHAR(20) NULL,
            plate_state VARCHAR(2) NULL,
            no_plate_flag {$bool} NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS locations (
            id {$id},
            label VARCHAR(100) NULL,
            address_line_1 VARCHAR(255) NULL,
            address_line_2 VARCHAR(255) NULL,
            city VARCHAR(100) NULL,
            state VARCHAR(50) NULL,
            postal_code VARCHAR(20) NULL,
            latitude DECIMAL(10, 7) NULL,
            longitude DECIMAL(10, 7) NULL,
            location_source VARCHAR(30) NOT NULL DEFAULT 'manual',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS intakes (
            id {$id},
            intake_number VARCHAR(32) NOT NULL UNIQUE,
            customer_id {$fkUnsigned} NULL,
            location_id {$fkUnsigned} NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            service_requested VARCHAR(255) NOT NULL,
            location_address VARCHAR(255) NULL,
            location_city VARCHAR(100) NULL,
            location_state VARCHAR(50) NULL,
            location_postal_code VARCHAR(20) NULL,
            vehicle_year VARCHAR(4) NULL,
            vehicle_make VARCHAR(80) NULL,
            vehicle_model VARCHAR(80) NULL,
            vehicle_color VARCHAR(50) NULL,
            lead_source VARCHAR(40) NOT NULL DEFAULT 'direct',
            notes TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'saved',
            converted_service_request_id {$fkUnsigned} NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (location_id) REFERENCES locations(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS service_requests (
            id {$id},
            service_request_number VARCHAR(32) NOT NULL UNIQUE,
            customer_id {$fkUnsigned} NOT NULL,
            vehicle_id {$fkUnsigned} NULL,
            location_id {$fkUnsigned} NOT NULL,
            intake_id {$fkUnsigned} NULL,
            requested_service VARCHAR(255) NOT NULL,
            problem_description TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            soft_quote_min DECIMAL(10, 2) NULL,
            soft_quote_max DECIMAL(10, 2) NULL,
            priority VARCHAR(20) NOT NULL DEFAULT 'normal',
            lead_source VARCHAR(40) NOT NULL DEFAULT 'direct',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
            FOREIGN KEY (location_id) REFERENCES locations(id),
            FOREIGN KEY (intake_id) REFERENCES intakes(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id {$id},
            actor_user_id {$fkUnsigned} NULL,
            action VARCHAR(100) NOT NULL,
            related_type VARCHAR(100) NOT NULL,
            related_id {$fkUnsigned} NOT NULL,
            old_value_json TEXT NULL,
            new_value_json TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL
        )
    ");
};
