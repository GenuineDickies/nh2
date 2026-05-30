<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AppSetting;

/**
 * Square ships two completely separate credential sets — one for the Sandbox
 * test environment and one for live Production. To make going live a single
 * switch (no retyping), this app keeps THREE copies in app_settings:
 *
 *   - The "live" keys (SQUARE_ACCESS_TOKEN, SQUARE_LOCATION_ID, …) are the
 *     credentials actually in use. Everything that talks to Square reads these,
 *     unchanged.
 *   - A parked Sandbox set     (…__SANDBOX)
 *   - A parked Production set  (…__PRODUCTION)
 *
 * You fill in both parked sets ahead of time. Flipping SQUARE_ENVIRONMENT
 * purges the live credential keys and reloads them from the parked set for the
 * newly-selected environment — see {@see self::loadSetIntoLive()}. Editing a
 * parked set while it is the active environment also mirrors into the live keys
 * so the running config never drifts — see {@see self::mirrorToLive()}.
 *
 * Only the four credential keys are environment-specific. SQUARE_ENVIRONMENT
 * (the switch itself) and SQUARE_API_VERSION (a shared SDK schema pin) live as
 * plain single keys and are not parked.
 */
final class SquareConfig
{
    /** Credential keys that have a separate value per environment. */
    public const CREDENTIAL_KEYS = [
        'SQUARE_ACCESS_TOKEN',
        'SQUARE_LOCATION_ID',
        'SQUARE_WEBHOOK_SIGNATURE_KEY',
        'SQUARE_APPLICATION_ID',
    ];

    public const ENVIRONMENTS = ['sandbox', 'production'];

    private AppSetting $settings;

    public function __construct(?AppSetting $settings = null)
    {
        $this->settings = $settings ?? new AppSetting();
    }

    public static function isCredentialKey(string $key): bool
    {
        return in_array($key, self::CREDENTIAL_KEYS, true);
    }

    /** Returns 'sandbox' | 'production', or '' if the value is unset/invalid. */
    public static function normalizeEnv(string $env): string
    {
        $env = strtolower(trim($env));
        return in_array($env, self::ENVIRONMENTS, true) ? $env : '';
    }

    /**
     * Storage key for a credential within a parked set, e.g.
     * SQUARE_ACCESS_TOKEN__SANDBOX. Assumes a valid environment.
     */
    public static function setKey(string $baseKey, string $env): string
    {
        return $baseKey . '__' . strtoupper(self::normalizeEnv($env));
    }

    /** The currently-selected environment ('' if unset/invalid). */
    public function activeEnvironment(): string
    {
        return self::normalizeEnv((string) ($this->settings->get('SQUARE_ENVIRONMENT') ?? ''));
    }

    /** Stored value of a credential within a parked set (null if unset). */
    public function setValue(string $baseKey, string $env): ?string
    {
        if (self::normalizeEnv($env) === '') {
            return null;
        }
        return $this->settings->getStored(self::setKey($baseKey, $env));
    }

    /**
     * Copy the parked set value for one credential into the live key, so the
     * running config tracks edits to the active environment's set. Clears the
     * live key when the parked value is blank/absent. No-ops when nothing
     * differs (avoids needless updated_at / audit churn).
     */
    public function mirrorToLive(string $baseKey, string $env, ?int $userId = null): void
    {
        if (!self::isCredentialKey($baseKey) || self::normalizeEnv($env) === '') {
            return;
        }
        $this->writeOrClear($baseKey, (string) ($this->setValue($baseKey, $env) ?? ''), $userId);
    }

    /**
     * Purge every live credential key and reload it from the parked set for
     * $env. Called when the environment switch flips. A blank/absent parked
     * value leaves the live key cleared rather than stale.
     */
    public function loadSetIntoLive(string $env, ?int $userId = null): void
    {
        $env = self::normalizeEnv($env);
        foreach (self::CREDENTIAL_KEYS as $baseKey) {
            $value = $env === '' ? '' : (string) ($this->setValue($baseKey, $env) ?? '');
            $this->writeOrClear($baseKey, $value, $userId);
        }
    }

    /**
     * Set $key to $value, or clear it when $value is blank. Returns true only
     * when the stored value actually changed.
     */
    private function writeOrClear(string $key, string $value, ?int $userId): bool
    {
        $value = trim($value);
        $stored = $this->settings->getStored($key);

        if ($value === '') {
            if ($stored !== null) {
                $this->settings->clear($key);
                return true;
            }
            return false;
        }

        if ($stored === $value) {
            return false;
        }

        $this->settings->set($key, $value, $userId);
        return true;
    }
}
