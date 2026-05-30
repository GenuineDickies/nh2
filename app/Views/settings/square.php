<?php

use App\Core\View;

$status = $status ?? [];
$flash = $flash ?? null;
$errorBanner = $errorBanner ?? null;
$csrfToken = $csrfToken ?? '';
$savedKey = $savedKey ?? '';
$savedScope = $savedScope ?? '';
$activeEnv = $activeEnv ?? '';
$environmentRow = $environmentRow ?? null;
$apiVersionRow = $apiVersionRow ?? null;
$sets = $sets ?? ['sandbox' => [], 'production' => []];

$missing = $status['required_missing'] ?? [];
$allRequiredPresent = (bool) ($status['all_required_present'] ?? false);
$activeLabel = $activeEnv !== '' ? ucfirst($activeEnv) : 'Not set';

$sourceLabel = static function (string $source, bool $present): string {
    if ($source === 'db') {
        return '<span class="status-badge" data-status="active" title="Saved from this form">Saved</span>';
    }
    if ($source === 'env') {
        return '<span class="status-badge" data-status="inactive" title="Inherited from the server\'s .env file">From .env</span>';
    }
    return '<span class="status-badge" data-status="cancelled">Empty</span>';
};

// Per-set completeness (required credential fields only), for the summary chips.
$setMissing = [];
foreach (['sandbox', 'production'] as $env) {
    $names = [];
    foreach (($sets[$env] ?? []) as $r) {
        if (!empty($r['field']['required']) && empty($r['present'])) {
            $names[] = (string) $r['field']['label'];
        }
    }
    $setMissing[$env] = $names;
};

// Human label for the just-saved field (for the confirmation banner).
$savedLabel = '';
$collectLabel = static function ($row) use (&$savedLabel, $savedKey, $savedScope) {
    if (!$row) {
        return;
    }
    if ((string) ($row['field']['key'] ?? '') === $savedKey && (string) ($row['scope'] ?? '') === $savedScope) {
        $label = (string) ($row['field']['label'] ?? '');
        $scope = (string) ($row['scope'] ?? '');
        $savedLabel = $scope !== '' ? ucfirst($scope) . ' ' . $label : $label;
    }
};
$collectLabel($environmentRow);
$collectLabel($apiVersionRow);
foreach (['sandbox', 'production'] as $env) {
    foreach (($sets[$env] ?? []) as $r) {
        $collectLabel($r);
    }
}
?>

<?php if ($flash === 'saved'): ?>
    <div class="alert alert-success" role="status">
        <?= $savedLabel !== '' ? View::e($savedLabel) . ' saved.' : 'Setting saved.' ?>
    </div>
<?php endif; ?>

<?php if ($errorBanner): ?>
    <div class="alert alert-warning" role="alert"><?= View::e($errorBanner) ?></div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Admin Settings</p>
            <h2>Square Configuration</h2>
        </div>
        <span class="status-badge" data-status="<?= $allRequiredPresent ? 'active' : 'pending' ?>">
            <?= $allRequiredPresent ? 'Ready' : 'Action needed' ?>
        </span>
    </div>

    <p class="muted">
        Square keeps separate credentials for testing (Sandbox) and live payments (Production).
        Fill in <strong>both</strong> sets below now; the <strong>Environment</strong> switch decides
        which set is loaded into the live credentials the app actually uses. Flipping it reloads the
        live credentials from the matching set — no retyping when you go live.
    </p>

    <?php if ($allRequiredPresent): ?>
        <p class="alert alert-success">
            Live configuration is complete and running on <strong><?= View::e($activeLabel) ?></strong>.
        </p>
    <?php else: ?>
        <p class="alert alert-warning">
            Live configuration (<strong><?= View::e($activeLabel) ?></strong>) is missing or invalid:
            <strong><?= View::e(implode(', ', $missing)) ?></strong>
        </p>
    <?php endif; ?>

    <?php /* The environment switch — saving it reloads the live credentials. */ ?>
    <?php if ($environmentRow): $row = $environmentRow; include __DIR__ . '/_square_field.php'; ?>
        <p class="muted setting-switch-note">
            Saving a new environment purges the live credentials and reloads them from that set below.
        </p>
    <?php endif; ?>

    <?php /* Per-set readiness summary. */ ?>
    <div class="set-summary">
        <?php foreach (['sandbox', 'production'] as $env):
            $names = $setMissing[$env];
            $complete = $names === [];
            $isActive = $env === $activeEnv;
        ?>
            <div class="set-summary-item">
                <span class="status-badge" data-status="<?= $complete ? 'active' : 'pending' ?>">
                    <?= View::e(ucfirst($env)) ?> set<?= $complete ? ' complete' : '' ?>
                </span>
                <?php if ($isActive): ?>
                    <span class="status-badge" data-status="active" title="Loaded into the live credentials">Live</span>
                <?php endif; ?>
                <?php if (!$complete): ?>
                    <small class="muted">needs <?= View::e(implode(', ', $names)) ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php /* Credential groups: one block per credential, Sandbox + Production rows inside. */ ?>
    <div class="credential-groups">
        <?php
        $credentialCount = count($sets['sandbox'] ?? []);
        for ($i = 0; $i < $credentialCount; $i++):
            $sandboxRow = $sets['sandbox'][$i];
            $productionRow = $sets['production'][$i] ?? null;
            $field = $sandboxRow['field'];
            $required = (bool) ($field['required'] ?? false);
            $groupHelpId = 'help-' . preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $field['key']));
        ?>
            <div class="credential-group">
                <div class="credential-group-head">
                    <h3>
                        <?= View::e((string) $field['label']) ?>
                        <?php if ($required): ?><span class="muted">(required)</span><?php else: ?><span class="muted">(optional)</span><?php endif; ?>
                    </h3>
                </div>
                <p class="setting-field-description"><?= View::e((string) $field['description']) ?></p>
                <details class="setting-field-help" id="<?= View::e($groupHelpId) ?>">
                    <summary>Where do I find this?</summary>
                    <p><?= View::e((string) $field['where_to_find']) ?></p>
                </details>

                <div class="credential-group-sets">
                    <?php $row = $sandboxRow; include __DIR__ . '/_square_field.php'; ?>
                    <?php if ($productionRow): $row = $productionRow; include __DIR__ . '/_square_field.php'; ?><?php endif; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <?php /* Shared (non-environment-specific) settings. */ ?>
    <?php if ($apiVersionRow): ?>
        <div class="credential-group">
            <div class="credential-group-head">
                <h3>Shared settings</h3>
                <span class="muted">Applies to both environments</span>
            </div>
            <?php $row = $apiVersionRow; include __DIR__ . '/_square_field.php'; ?>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">Diagnostics</p>
            <h2>Runtime status</h2>
        </div>
    </div>

    <p class="muted">
        Read-only signals that help confirm Square is wired up correctly. These are not editable.
    </p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Signal</th>
                <th>Value</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Webhook endpoint URL</td>
                <td>
                    <code><?= View::e((string) ($status['webhook_endpoint_preview'] ?? '/webhooks/square.php')) ?></code>
                    <p class="muted setting-field-description">
                        Configure this as the destination URL when you create or edit the webhook
                        subscription in your Square Developer Dashboard.
                    </p>
                </td>
            </tr>
            <tr>
                <td>Last successful webhook</td>
                <td><?= View::e((string) ($status['last_successful_webhook'] ?? 'Not available yet')) ?></td>
            </tr>
            <tr>
                <td>Last Square API error</td>
                <td><?= View::e((string) ($status['last_api_error'] ?? 'None recorded')) ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <p class="muted">
        Secrets remain masked on this screen and are never rendered in full. Saved values override
        anything set in the server's <code>.env</code> file.
    </p>
</div>
