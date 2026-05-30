<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id {$id},
            invoice_number VARCHAR(32) NOT NULL UNIQUE,
            service_request_id {$fkUnsigned} NOT NULL,
            estimate_id {$fkUnsigned} NULL,
            service_completion_report_id {$fkUnsigned} NULL,
            customer_id {$fkUnsigned} NOT NULL,
            vehicle_id {$fkUnsigned} NULL,
            no_vehicle_serviced_flag {$bool} NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
            total DECIMAL(10, 2) NOT NULL DEFAULT 0,
            amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0,
            balance_due DECIMAL(10, 2) NOT NULL DEFAULT 0,
            issued_at DATETIME NULL,
            due_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (service_request_id) REFERENCES service_requests(id),
            FOREIGN KEY (estimate_id) REFERENCES estimates(id),
            FOREIGN KEY (service_completion_report_id) REFERENCES service_completion_reports(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id),
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS invoice_line_items (
            id {$id},
            invoice_id {$fkUnsigned} NOT NULL,
            catalog_item_id {$fkUnsigned} NULL,
            line_type VARCHAR(30) NOT NULL,
            description VARCHAR(255) NOT NULL,
            quantity DECIMAL(10, 2) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
            taxable {$bool} NOT NULL DEFAULT 0,
            income_account_id {$fkUnsigned} NULL,
            cogs_account_id {$fkUnsigned} NULL,
            line_subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id),
            FOREIGN KEY (catalog_item_id) REFERENCES catalog_items(id)
        )
    ");
};

