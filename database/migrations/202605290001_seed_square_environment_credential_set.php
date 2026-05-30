<?php

declare(strict_types=1);

/**
 * Seed a parked Square credential set from the credentials already in the live
 * keys.
 *
 * Background: the Square settings screen used to store a single credential set
 * directly in the live keys (SQUARE_ACCESS_TOKEN, SQUARE_LOCATION_ID, …). The
 * screen now keeps two *parked* sets — Sandbox (…__SANDBOX) and Production
 * (…__PRODUCTION) — and the live keys are loaded from whichever set
 * SQUARE_ENVIRONMENT selects. Without this migration the credentials the admin
 * already entered would not show up under their environment column.
 *
 * This copies each present live credential into the parked set matching the
 * current SQUARE_ENVIRONMENT. It only fills parked rows that do not already
 * exist, so it never clobbers values entered manually and is safe to re-run.
 */
return function (PDO $db, string $driver): void {
    $credentialKeys = [
        'SQUARE_ACCESS_TOKEN',
        'SQUARE_LOCATION_ID',
        'SQUARE_WEBHOOK_SIGNATURE_KEY',
        'SQUARE_APPLICATION_ID',
    ];

    $read = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :key');

    // Which parked set do the current live credentials belong to?
    $read->execute(['key' => 'SQUARE_ENVIRONMENT']);
    $env = strtolower(trim((string) ($read->fetchColumn() ?: '')));
    if (!in_array($env, ['sandbox', 'production'], true)) {
        // No valid active environment selected: nothing to seed into.
        return;
    }
    $suffix = '__' . strtoupper($env);

    $insert = $db->prepare(
        'INSERT INTO app_settings (setting_key, setting_value, updated_at, updated_by)
         VALUES (:key, :value, :updated_at, NULL)'
    );

    foreach ($credentialKeys as $baseKey) {
        $parkedKey = $baseKey . $suffix;

        // Never clobber a parked value that already exists (fetchColumn returns
        // false only when the row is absent; a NULL stored value counts as
        // present and is left untouched).
        $read->execute(['key' => $parkedKey]);
        if ($read->fetchColumn() !== false) {
            continue;
        }

        // Only copy a live value that is actually present and non-blank.
        $read->execute(['key' => $baseKey]);
        $live = $read->fetchColumn();
        if ($live === false || (string) $live === '') {
            continue;
        }

        $insert->execute([
            'key' => $parkedKey,
            'value' => (string) $live,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
};
