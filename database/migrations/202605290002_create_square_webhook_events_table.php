<?php

declare(strict_types=1);

/**
 * Durable log of inbound Square webhook events.
 *
 * Serves three jobs at once:
 *   - Idempotency: Square may deliver the same event more than once. The unique
 *     event_id lets the handler ack duplicates without re-recording money.
 *   - Diagnostics: the Square settings "Runtime status" card reads the most
 *     recent processed row for "Last successful webhook" and the most recent
 *     failure for "Last Square API error".
 *   - Forensics: the raw payload is kept so a human can see exactly what Square
 *     sent when a payment didn't match an invoice.
 */
return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';

    $db->exec("
        CREATE TABLE IF NOT EXISTS square_webhook_events (
            id {$id},
            event_id VARCHAR(190) NOT NULL UNIQUE,
            event_type VARCHAR(80) NOT NULL,
            environment VARCHAR(20) NULL,
            payment_reference VARCHAR(190) NULL,
            invoice_id {$fkUnsigned} NULL,
            status VARCHAR(40) NOT NULL,
            message TEXT NULL,
            payload TEXT NULL,
            received_at DATETIME NOT NULL,
            processed_at DATETIME NULL
        )
    ");
};
