<?php

return function (PDO $db, string $driver): void {
    // Add columns that track the AI-ready staged version of an upload.
    // staged_file_path is null when no transformation was needed (the
    // original is already AI-ready, e.g., PDFs).
    $cols = [
        ['staged_file_path', 'VARCHAR(255) NULL'],
        ['staged_mime_type', 'VARCHAR(120) NULL'],
        ['staged_file_size', 'INTEGER NULL'],
        ['staging_driver', 'VARCHAR(40) NULL'],
        ['staging_warnings', 'TEXT NULL'],
    ];

    foreach ($cols as [$name, $type]) {
        // SQLite + MySQL both support ADD COLUMN; use a simple try/skip.
        try {
            $db->exec("ALTER TABLE document_intakes ADD COLUMN {$name} {$type}");
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'duplicate column') === false
                && stripos($msg, 'duplicate field') === false
                && stripos($msg, 'already exists') === false
            ) {
                throw $e;
            }
        }
    }
};
