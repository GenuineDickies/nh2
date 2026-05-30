<?php

namespace App\Models;

use App\Core\Env;
use App\Core\Repository;

/**
 * Key/value store for app-level settings that an admin can edit from the UI.
 *
 * Read priority for any given key:
 *   1. Row in app_settings (admin override via UI)
 *   2. Env var of the same name (.env or shell)
 *   3. Caller-supplied default
 *
 * Stored values always win over .env so the UI is authoritative — set a value
 * via {@see self::set()} to override, or {@see self::clear()} to fall back to
 * whatever .env (or default) provides.
 */
final class AppSetting extends Repository
{
    /**
     * Resolved value for a setting key, following the priority above.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stored = $this->getStored($key);
        if ($stored !== null) {
            return $stored;
        }

        $envValue = Env::get($key);
        if ($envValue !== null && $envValue !== '') {
            return $envValue;
        }

        return $default;
    }

    /**
     * Stored DB override (if any), bypassing env / defaults. Useful when the
     * UI needs to distinguish "this came from the database" from "this came
     * from .env".
     */
    public function getStored(string $key): ?string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k');
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $value = $row['setting_value'];
        return $value === null ? null : (string) $value;
    }

    /**
     * Where each known key is currently sourced from.
     * Returns one of: "db" | "env" | "missing".
     */
    public function source(string $key): string
    {
        if ($this->getStored($key) !== null) {
            return 'db';
        }
        $envValue = Env::get($key);
        if ($envValue !== null && $envValue !== '') {
            return 'env';
        }
        return 'missing';
    }

    public function set(string $key, string $value, ?int $userId = null): void
    {
        $stored = $this->getStored($key);
        $now = $this->now();

        if ($stored === null) {
            $stmt = $this->db->prepare(
                'INSERT INTO app_settings (setting_key, setting_value, updated_at, updated_by)
                 VALUES (:k, :v, :updated_at, :updated_by)'
            );
            $stmt->execute([
                'k' => $key,
                'v' => $value,
                'updated_at' => $now,
                'updated_by' => $userId,
            ]);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE app_settings
             SET setting_value = :v, updated_at = :updated_at, updated_by = :updated_by
             WHERE setting_key = :k'
        );
        $stmt->execute([
            'v' => $value,
            'updated_at' => $now,
            'updated_by' => $userId,
            'k' => $key,
        ]);
    }

    /**
     * Delete the DB override, letting any .env value take over again.
     */
    public function clear(string $key): void
    {
        $this->db->prepare('DELETE FROM app_settings WHERE setting_key = :k')->execute(['k' => $key]);
    }
}
