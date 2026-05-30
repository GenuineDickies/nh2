<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS file_attachments (
            id {$id},
            related_type VARCHAR(60) NOT NULL,
            related_id {$fkUnsigned} NOT NULL,
            file_type VARCHAR(30) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            original_filename VARCHAR(180) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            file_size INTEGER NOT NULL DEFAULT 0,
            caption VARCHAR(255) NULL,
            latitude DECIMAL(10, 7) NULL,
            longitude DECIMAL(10, 7) NULL,
            uploaded_by {$fkUnsigned} NULL,
            created_at DATETIME NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS generated_documents (
            id {$id},
            document_number VARCHAR(32) NOT NULL UNIQUE,
            document_type VARCHAR(40) NOT NULL,
            related_type VARCHAR(60) NOT NULL,
            related_id {$fkUnsigned} NOT NULL,
            title VARCHAR(160) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'placeholder',
            file_attachment_id {$fkUnsigned} NULL,
            generated_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (file_attachment_id) REFERENCES file_attachments(id)
        )
    ");
};
