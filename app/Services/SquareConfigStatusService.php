<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AppSetting;

final class SquareConfigStatusService
{
    public function status(): array
    {
        $settings = new AppSetting();
        $webhookSignals = $this->webhookSignals();

        $environmentRaw = trim((string) ($settings->get('SQUARE_ENVIRONMENT') ?? ''));
        $environment = strtolower($environmentRaw);

        $accessToken = trim((string) ($settings->get('SQUARE_ACCESS_TOKEN') ?? ''));
        $locationId = trim((string) ($settings->get('SQUARE_LOCATION_ID') ?? ''));
        $webhookSignatureKey = trim((string) ($settings->get('SQUARE_WEBHOOK_SIGNATURE_KEY') ?? ''));
        $applicationId = trim((string) ($settings->get('SQUARE_APPLICATION_ID') ?? ''));
        $apiVersion = trim((string) ($settings->get('SQUARE_API_VERSION') ?? ''));

        $isEnvironmentValid = in_array($environment, ['sandbox', 'production'], true);

        $requiredMissing = [];
        if (!$isEnvironmentValid) {
            $requiredMissing[] = 'Environment (must be Sandbox or Production)';
        }
        if (!$this->isPresent($accessToken)) {
            $requiredMissing[] = 'Access Token';
        }
        if (!$this->isPresent($locationId)) {
            $requiredMissing[] = 'Location ID';
        }
        if (!$this->isPresent($webhookSignatureKey)) {
            $requiredMissing[] = 'Webhook Signature Key';
        }

        return [
            'mode_label' => $this->modeLabel($environment),
            'mode_raw' => $environmentRaw !== '' ? $environmentRaw : 'Not set',
            'all_required_present' => $requiredMissing === [],
            'required_missing' => $requiredMissing,
            'access_token' => [
                'present' => $this->isPresent($accessToken),
                'masked' => $this->maskSecret($accessToken),
            ],
            'location_id' => [
                'present' => $this->isPresent($locationId),
                'masked' => $this->maskLocationId($locationId),
            ],
            'webhook_signature_key' => [
                'present' => $this->isPresent($webhookSignatureKey),
                'masked' => $this->maskSecret($webhookSignatureKey),
            ],
            'application_id' => [
                'present' => $this->isPresent($applicationId),
                'masked' => $this->maskSecret($applicationId),
            ],
            'api_version' => $apiVersion !== '' ? $apiVersion : 'Default (SDK)',
            'webhook_endpoint_preview' => '/webhooks/square.php',
            'last_successful_webhook' => $webhookSignals['last_success'],
            'last_api_error' => $webhookSignals['last_error'],
        ];
    }

    /**
     * Read the most recent webhook signals from the audit log. "Success" is any
     * delivery whose signature verified and which finished dispatch — including
     * 'unmatched', since those still prove the pipeline works end to end.
     *
     * @return array{last_success: string, last_error: string}
     */
    private function webhookSignals(): array
    {
        try {
            $db = Database::connection();
            $ok = $db->query(
                "SELECT event_type, status, processed_at
                   FROM square_webhook_events
                  WHERE status IN ('matched', 'unmatched')
                  ORDER BY processed_at DESC
                  LIMIT 1"
            )->fetch();
            $err = $db->query(
                "SELECT event_type, message, processed_at
                   FROM square_webhook_events
                  WHERE status = 'error'
                  ORDER BY processed_at DESC
                  LIMIT 1"
            )->fetch();
        } catch (\Throwable $e) {
            // Table may not exist yet on a fresh install before migrations run.
            return [
                'last_success' => 'Not available yet',
                'last_error' => 'None recorded',
            ];
        }

        $lastSuccess = $ok
            ? sprintf('%s at %s (%s)', $ok['event_type'], $ok['processed_at'], $ok['status'])
            : 'Not available yet';
        $lastError = $err
            ? sprintf('%s at %s — %s', $err['event_type'], $err['processed_at'], (string) ($err['message'] ?? 'unspecified'))
            : 'None recorded';

        return ['last_success' => $lastSuccess, 'last_error' => $lastError];
    }

    private function isPresent(string $value): bool
    {
        return trim($value) !== '';
    }

    private function maskSecret(string $value): string
    {
        return $this->isPresent($value) ? 'Saved' : 'Missing';
    }

    private function maskLocationId(string $value): string
    {
        if (!$this->isPresent($value)) {
            return 'Missing';
        }

        $suffix = substr($value, -4);

        return '••••' . ($suffix !== false ? $suffix : '');
    }

    private function modeLabel(string $environment): string
    {
        return match ($environment) {
            'sandbox' => 'Sandbox',
            'production' => 'Production',
            default => 'Unknown',
        };
    }
}
