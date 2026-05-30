<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SettingsCatalog;
use App\Services\SquareConfig;
use App\Services\SquareConfigStatusService;

final class SquareSettingsController extends Controller
{
    private const GROUP = 'square';

    public function show(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $savedKey = isset($_GET['saved']) ? (string) $_GET['saved'] : '';
        $savedScope = isset($_GET['scope']) ? SquareConfig::normalizeEnv((string) $_GET['scope']) : '';
        $this->render([], null, $savedKey !== '' ? 'saved' : null, $savedKey, $savedScope);
    }

    public function update(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        if (!Csrf::isValid((string) $this->input('csrf_token', ''))) {
            $this->render([], 'Your session expired. Reload this page and try saving again.', null);
            return;
        }

        // Each setting is its own form; the hidden "field" input tells us which
        // one is being saved so we only touch that single setting.
        $requestedKey = (string) $this->input('field', '');
        $field = $this->findField($requestedKey);
        if ($field === null) {
            $this->render([], 'That setting could not be found. Reload this page and try again.', null);
            return;
        }

        $settings = new AppSetting();
        $config = new SquareConfig($settings);
        $userId = Auth::userId();

        // --- The environment switch -------------------------------------------
        // Saving a new environment purges the live credential keys and reloads
        // them from that environment's parked set, so "going live" is one click.
        if ($requestedKey === 'SQUARE_ENVIRONMENT') {
            [$err, $change] = $this->applyField($field, $requestedKey, $settings, $userId, true);
            if ($err !== null) {
                $this->render([$requestedKey => $err], 'This setting needs attention before it can save.', null);
                return;
            }
            if ($change !== null) {
                $newEnv = SquareConfig::normalizeEnv((string) ($_POST[$requestedKey] ?? ''));
                $config->loadSetIntoLive($newEnv, $userId);
                $this->audit($userId, 'environment → ' . ($newEnv !== '' ? $newEnv : 'unset') . ' (live credentials reloaded)');
            }
            $this->redirect('/admin/settings/square?saved=' . urlencode($requestedKey));
            return;
        }

        // --- A per-environment credential (sandbox or production set) ---------
        if (SquareConfig::isCredentialKey($requestedKey)) {
            $scope = SquareConfig::normalizeEnv((string) $this->input('scope', ''));
            if ($scope === '') {
                $this->render([], 'Could not tell which environment set to save. Reload this page and try again.', null);
                return;
            }
            $storageKey = SquareConfig::setKey($requestedKey, $scope);
            // Parked sets allow staged/partial entry, so don't hard-error on a
            // blank required field here — completeness is judged on the live set.
            [$err, $change] = $this->applyField($field, $storageKey, $settings, $userId, false);
            if ($err !== null) {
                $this->render([$storageKey => $err], 'This setting needs attention before it can save.', null, $requestedKey, $scope);
                return;
            }
            // Keep the running config in sync if we just edited the active set.
            if ($scope === $config->activeEnvironment()) {
                $config->mirrorToLive($requestedKey, $scope, $userId);
            }
            if ($change !== null) {
                $this->audit($userId, $change);
            }
            $this->redirect('/admin/settings/square?saved=' . urlencode($requestedKey) . '&scope=' . urlencode($scope));
            return;
        }

        // --- A shared field (API Version) -------------------------------------
        [$err, $change] = $this->applyField($field, $requestedKey, $settings, $userId, false);
        if ($err !== null) {
            $this->render([$requestedKey => $err], 'This setting needs attention before it can save.', null);
            return;
        }
        if ($change !== null) {
            $this->audit($userId, $change);
        }
        $this->redirect('/admin/settings/square?saved=' . urlencode($requestedKey));
    }

    /**
     * Resolve a catalog field definition by its key, or null if unknown.
     *
     * @return array<string, mixed>|null
     */
    private function findField(string $key): ?array
    {
        if ($key === '') {
            return null;
        }
        foreach (SettingsCatalog::group(self::GROUP) as $field) {
            if ((string) $field['key'] === $key) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Apply a single submitted setting to $storageKey. Returns
     * [errorMessage, changeLabel]:
     *   - errorMessage is non-null only when validation failed (nothing saved).
     *   - changeLabel is non-null only when the stored value actually changed
     *     (used for the audit log); null means "no-op, nothing to record".
     *
     * The form field is named after the catalog key ($field['key']) regardless
     * of which environment set it targets, so the value/clear inputs are read
     * from there while reads/writes go to $storageKey.
     *
     * @param array<string, mixed> $field
     * @return array{0: ?string, 1: ?string}
     */
    private function applyField(array $field, string $storageKey, AppSetting $settings, ?int $userId, bool $enforceRequiredBlank): array
    {
        $baseKey = (string) $field['key'];
        $type = (string) $field['type'];
        $required = (bool) ($field['required'] ?? false);

        // For secrets, the form provides a separate "Clear" checkbox so the
        // user can wipe a stored value without having to type it just to
        // overwrite it with blank.
        if ($type === 'secret' && (string) $this->input('clear_' . $baseKey, '') === '1') {
            if ($settings->getStored($storageKey) !== null) {
                $settings->clear($storageKey);
                return [null, $storageKey . ' (cleared)'];
            }
            return [null, null];
        }

        $value = trim((string) ($_POST[$baseKey] ?? ''));

        if ($type === 'secret' && $value === '') {
            // Blank submission for a secret = "don't change". This avoids
            // wiping a stored secret just because the field rendered empty.
            return [null, null];
        }

        if ($type === 'select') {
            $allowed = array_keys((array) ($field['options'] ?? []));
            if ($value !== '' && !in_array($value, $allowed, true)) {
                return ['Choose one of the listed options.', null];
            }
        }

        if ($enforceRequiredBlank && $required && $value === '' && $type !== 'secret') {
            // Required text/select: blank = error (don't silently clear it).
            return ['This field is required.', null];
        }

        $stored = $settings->getStored($storageKey);

        if ($value === '') {
            // Blank submission = clear the stored value.
            if ($stored !== null) {
                $settings->clear($storageKey);
                return [null, $storageKey . ' (cleared)'];
            }
            return [null, null];
        }

        if ($stored === $value) {
            return [null, null];
        }

        $settings->set($storageKey, $value, $userId);
        return [null, $storageKey];
    }

    private function audit(?int $userId, string $change): void
    {
        if (!$userId) {
            return;
        }
        (new AuditLog())->record('app_settings_updated', 'app_setting', 0, null, [
            'group' => self::GROUP,
            'keys' => [$change],
        ]);
    }

    private function render(array $errors, ?string $error, ?string $flash, string $savedKey = '', string $savedScope = ''): void
    {
        $status = (new SquareConfigStatusService())->status();
        $settings = new AppSetting();
        $config = new SquareConfig($settings);

        $environmentField = null;
        $apiVersionField = null;
        $credentialFields = [];
        foreach (SettingsCatalog::group(self::GROUP) as $field) {
            $key = (string) $field['key'];
            if (SquareConfig::isCredentialKey($key)) {
                $credentialFields[] = $field;
            } elseif ($key === 'SQUARE_ENVIRONMENT') {
                $environmentField = $field;
            } else {
                // Any remaining shared field (currently just API Version).
                $apiVersionField = $field;
            }
        }

        // One row per credential field, per environment set.
        $sets = [];
        foreach (SquareConfig::ENVIRONMENTS as $env) {
            $rows = [];
            foreach ($credentialFields as $field) {
                $rows[] = $this->buildRow($field, $settings, $errors, $env);
            }
            $sets[$env] = $rows;
        }

        $this->view('layouts/app', [
            'title' => 'Square Settings',
            'active' => 'settings',
            'content' => 'settings/square',
            'status' => $status,
            'environmentRow' => $environmentField ? $this->buildRow($environmentField, $settings, $errors, '') : null,
            'apiVersionRow' => $apiVersionField ? $this->buildRow($apiVersionField, $settings, $errors, '') : null,
            'sets' => $sets,
            'activeEnv' => $config->activeEnvironment(),
            'errors' => $errors,
            'errorBanner' => $error,
            'flash' => $flash,
            'savedKey' => $savedKey,
            'savedScope' => $savedScope,
            'csrfToken' => Csrf::token(),
        ]);
    }

    /**
     * Build the view row for one field in one environment scope ('' for the
     * shared/global fields). Errors are keyed by storage key.
     *
     * @param array<string, mixed> $field
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    private function buildRow(array $field, AppSetting $settings, array $errors, string $scope): array
    {
        $baseKey = (string) $field['key'];
        $scoped = SquareConfig::isCredentialKey($baseKey) && $scope !== '';
        $storageKey = $scoped ? SquareConfig::setKey($baseKey, $scope) : $baseKey;
        $source = $settings->source($storageKey);

        return [
            'field' => $field,
            'scope' => $scoped ? $scope : '',
            'storage_key' => $storageKey,
            'input_id' => $scoped ? ($baseKey . '_' . $scope) : $baseKey,
            'stored_value' => $settings->getStored($storageKey),
            'resolved_value' => (string) ($settings->get($storageKey, '') ?? ''),
            'source' => $source,                       // db | env | missing
            'present' => $source !== 'missing',
            'error' => $errors[$storageKey] ?? null,
        ];
    }

    private function ensureAdmin(): bool
    {
        $currentUser = Auth::user();
        if (!$currentUser || !User::hasRole($currentUser, 'admin')) {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Forbidden',
                'message' => 'Admin access is required to view or change Square settings.',
            ]);
            return false;
        }
        return true;
    }
}
