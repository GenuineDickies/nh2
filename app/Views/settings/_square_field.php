<?php

use App\Core\View;

/**
 * Renders a single editable setting as its own per-field <form> (so each Save
 * button submits just that one value and the SPA layer swaps only .content).
 *
 * Expects in scope (shared via include from settings/square.php):
 *   $row         array   one row from the controller (field + storage info)
 *   $csrfToken   string
 *   $savedKey    string  base key just saved (for the "Saved just now" note)
 *   $savedScope  string  environment of the just-saved field ('' for global)
 *   $activeEnv   string  the environment currently loaded into the live keys
 *   $sourceLabel callable(string $source, bool $present): string
 *
 * Two layouts:
 *   - global  (scope === ''): full card with label + description + help.
 *   - compact (scope set):    just an env-labelled input row; the credential's
 *                             label/description/help are rendered once by the
 *                             surrounding credential group.
 */

$field = $row['field'];
$baseKey = (string) $field['key'];
$type = (string) $field['type'];
$required = (bool) ($field['required'] ?? false);
$scope = (string) $row['scope'];                 // '' | sandbox | production
$compact = $scope !== '';
$inputId = (string) $row['input_id'];
$source = (string) $row['source'];
$present = (bool) $row['present'];
$resolved = (string) $row['resolved_value'];
$error = $row['error'] ?? null;
$detailsId = 'help-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($inputId));
$isLive = $compact && $scope === ($activeEnv ?? '');
$justSaved = ($savedKey ?? '') === $baseKey && ($savedScope ?? '') === $scope && $error === null;
?>
<form method="post" action="/admin/settings/square" class="setting-form spa-form" autocomplete="off" novalidate>
    <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken ?? '') ?>">
    <input type="hidden" name="field" value="<?= View::e($baseKey) ?>">
    <?php if ($scope !== ''): ?>
        <input type="hidden" name="scope" value="<?= View::e($scope) ?>">
    <?php endif; ?>

    <fieldset class="setting-field <?= $compact ? 'setting-field-compact' : '' ?> <?= $error ? 'has-error' : '' ?> <?= $isLive ? 'is-live' : '' ?>">
        <?php if (!$compact): ?>
            <div class="setting-field-head">
                <label for="<?= View::e($inputId) ?>">
                    <?= View::e((string) $field['label']) ?>
                    <?php if ($required): ?><span class="muted">(required)</span><?php endif; ?>
                </label>
                <?= $sourceLabel($source, $present) ?>
            </div>
            <p class="setting-field-description"><?= View::e((string) $field['description']) ?></p>
            <details class="setting-field-help" id="<?= View::e($detailsId) ?>">
                <summary>Where do I find this?</summary>
                <p><?= View::e((string) $field['where_to_find']) ?></p>
            </details>
        <?php endif; ?>

        <div class="setting-field-control">
            <?php if ($compact): ?>
                <span class="setting-field-scope-label">
                    <label for="<?= View::e($inputId) ?>"><?= View::e(ucfirst($scope)) ?></label>
                    <?php if ($isLive): ?>
                        <span class="status-badge" data-status="active" title="This set is loaded into the live credentials right now">Live</span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>

            <div class="setting-field-input">
                <?php if ($type === 'select'): ?>
                    <select name="<?= View::e($baseKey) ?>" id="<?= View::e($inputId) ?>">
                        <?php foreach ((array) ($field['options'] ?? []) as $optValue => $optLabel):
                            $isSelected = (string) $resolved === (string) $optValue;
                            if ($resolved === '' && $optValue === '') {
                                $isSelected = true;
                            }
                        ?>
                            <option value="<?= View::e((string) $optValue) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= View::e((string) $optLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($type === 'secret'): ?>
                    <input
                        type="password"
                        name="<?= View::e($baseKey) ?>"
                        id="<?= View::e($inputId) ?>"
                        autocomplete="new-password"
                        placeholder="<?= $present ? 'Saved — leave blank to keep unchanged' : View::e((string) ($field['placeholder'] ?? 'Paste value')) ?>"
                    >

                <?php else: ?>
                    <input
                        type="text"
                        name="<?= View::e($baseKey) ?>"
                        id="<?= View::e($inputId) ?>"
                        value="<?= View::e($resolved) ?>"
                        placeholder="<?= View::e((string) ($field['placeholder'] ?? '')) ?>"
                        spellcheck="false"
                    >
                <?php endif; ?>
            </div>

            <button type="submit" class="primary-action setting-field-save">Save</button>

            <?php if ($compact): ?>
                <span class="setting-field-source"><?= $sourceLabel($source, $present) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($type === 'secret' && $present): ?>
            <label class="setting-field-clear">
                <input type="checkbox" name="clear_<?= View::e($baseKey) ?>" value="1">
                Clear this saved value
            </label>
        <?php endif; ?>

        <?php if ($justSaved): ?>
            <small class="field-saved" role="status">Saved just now.</small>
        <?php endif; ?>
        <?php if ($error): ?>
            <small class="field-error"><?= View::e((string) $error) ?></small>
        <?php endif; ?>
    </fieldset>
</form>
