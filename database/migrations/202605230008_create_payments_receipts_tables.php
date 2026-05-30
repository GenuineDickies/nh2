<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id {$id},
            payment_number VARCHAR(32) NOT NULL UNIQUE,
            invoice_id {$fkUnsigned} NOT NULL,
            customer_id {$fkUnsigned} NOT NULL,
            payment_method VARCHAR(30) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_status VARCHAR(30) NOT NULL DEFAULT 'completed',
            transaction_reference VARCHAR(120) NULL,
            paid_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS receipts (
            id {$id},
            receipt_number VARCHAR(32) NOT NULL UNIQUE,
            payment_id {$fkUnsigned} NOT NULL,
            invoice_id {$fkUnsigned} NOT NULL,
            customer_id {$fkUnsigned} NOT NULL,
            receipt_pdf_file_id {$fkUnsigned} NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (payment_id) REFERENCES payments(id),
            FOREIGN KEY (invoice_id) REFERENCES invoices(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        )
    ");
};
