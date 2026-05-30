<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $decimal = 'DECIMAL(12, 2)';

    $db->exec("
        CREATE TABLE IF NOT EXISTS vendor_documents (
            id {$id},
            document_number VARCHAR(32) NOT NULL UNIQUE,
            vendor_id {$fkUnsigned} NULL,
            document_type VARCHAR(40) NOT NULL DEFAULT 'receipt',
            external_document_number VARCHAR(120) NULL,
            document_date DATE NULL,
            subtotal {$decimal} NOT NULL DEFAULT 0,
            tax_total {$decimal} NOT NULL DEFAULT 0,
            total {$decimal} NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'uploaded',
            payment_method VARCHAR(30) NULL,
            file_attachment_id {$fkUnsigned} NULL,
            notes TEXT NULL,
            uploaded_at DATETIME NULL,
            approved_at DATETIME NULL,
            posted_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (vendor_id) REFERENCES vendors(id),
            FOREIGN KEY (file_attachment_id) REFERENCES file_attachments(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS vendor_document_line_items (
            id {$id},
            vendor_document_id {$fkUnsigned} NOT NULL,
            service_request_id {$fkUnsigned} NULL,
            invoice_id {$fkUnsigned} NULL,
            item_name VARCHAR(180) NOT NULL,
            part_number VARCHAR(120) NULL,
            category VARCHAR(40) NOT NULL DEFAULT 'other',
            quantity {$decimal} NOT NULL DEFAULT 1,
            unit_cost {$decimal} NOT NULL DEFAULT 0,
            line_total {$decimal} NOT NULL DEFAULT 0,
            reviewed_flag TINYINT NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (vendor_document_id) REFERENCES vendor_documents(id)
        )
    ");
};
