<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $decimal = 'DECIMAL(12, 2)';
    $longText = $driver === 'mysql' ? 'LONGTEXT' : 'TEXT';

    $db->exec("
        CREATE TABLE IF NOT EXISTS document_intakes (
            id {$id},
            document_number VARCHAR(40) NOT NULL UNIQUE,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_mime_type VARCHAR(120) NOT NULL,
            file_size INTEGER NOT NULL DEFAULT 0,
            file_hash VARCHAR(64) NULL,
            source_type VARCHAR(30) NOT NULL DEFAULT 'unknown',
            status VARCHAR(30) NOT NULL DEFAULT 'uploaded',
            detected_document_type VARCHAR(40) NULL,
            document_type_confidence DECIMAL(5, 4) NULL,
            related_customer_id {$fkUnsigned} NULL,
            related_vehicle_id {$fkUnsigned} NULL,
            related_vendor_id {$fkUnsigned} NULL,
            related_service_request_id {$fkUnsigned} NULL,
            related_invoice_id {$fkUnsigned} NULL,
            related_purchase_order_id {$fkUnsigned} NULL,
            posted_vendor_document_id {$fkUnsigned} NULL,
            uploaded_by_user_id {$fkUnsigned} NULL,
            uploaded_at DATETIME NULL,
            processed_at DATETIME NULL,
            reviewed_at DATETIME NULL,
            approved_at DATETIME NULL,
            rejected_at DATETIME NULL,
            posted_at DATETIME NULL,
            error_message TEXT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS document_extractions (
            id {$id},
            document_intake_id {$fkUnsigned} NOT NULL,
            openai_model VARCHAR(80) NULL,
            raw_response_json {$longText} NULL,
            normalized_json {$longText} NULL,
            extraction_confidence DECIMAL(5, 4) NULL,
            warnings_json TEXT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (document_intake_id) REFERENCES document_intakes(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS document_extraction_line_items (
            id {$id},
            document_intake_id {$fkUnsigned} NOT NULL,
            line_number INTEGER NOT NULL DEFAULT 0,
            description VARCHAR(255) NOT NULL,
            sku VARCHAR(120) NULL,
            manufacturer_part_number VARCHAR(120) NULL,
            vendor_part_number VARCHAR(120) NULL,
            quantity {$decimal} NOT NULL DEFAULT 1,
            unit_price {$decimal} NOT NULL DEFAULT 0,
            line_subtotal {$decimal} NOT NULL DEFAULT 0,
            taxable TINYINT NOT NULL DEFAULT 0,
            category_guess VARCHAR(40) NULL,
            expense_type_guess VARCHAR(40) NULL,
            matched_catalog_item_id {$fkUnsigned} NULL,
            matched_inventory_item_id {$fkUnsigned} NULL,
            reviewed_category VARCHAR(40) NULL,
            reviewed_expense_account_id {$fkUnsigned} NULL,
            inventory_candidate TINYINT NOT NULL DEFAULT 0,
            resale_candidate TINYINT NOT NULL DEFAULT 0,
            warranty_candidate TINYINT NOT NULL DEFAULT 0,
            confidence DECIMAL(5, 4) NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (document_intake_id) REFERENCES document_intakes(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS document_matches (
            id {$id},
            document_intake_id {$fkUnsigned} NOT NULL,
            match_type VARCHAR(40) NOT NULL,
            matched_table VARCHAR(60) NOT NULL,
            matched_record_id {$fkUnsigned} NOT NULL,
            match_confidence DECIMAL(5, 4) NULL,
            match_reason VARCHAR(255) NULL,
            accepted_by_user TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (document_intake_id) REFERENCES document_intakes(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS document_posting_logs (
            id {$id},
            document_intake_id {$fkUnsigned} NOT NULL,
            posted_record_type VARCHAR(60) NOT NULL,
            posted_record_id {$fkUnsigned} NULL,
            action_taken VARCHAR(40) NOT NULL,
            before_json {$longText} NULL,
            after_json {$longText} NULL,
            posted_by_user_id {$fkUnsigned} NULL,
            posted_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (document_intake_id) REFERENCES document_intakes(id)
        )
    ");
};
