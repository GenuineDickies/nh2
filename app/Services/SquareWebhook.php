<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AppSetting;
use PDO;
use Throwable;

/**
 * Inbound Square webhook handler.
 *
 * Responsibilities, in order:
 *   1. Verify Square's HMAC-SHA256 signature on the raw request body.
 *   2. Deduplicate by event_id (Square retries the same id on non-2xx).
 *   3. Persist a "received" audit row immediately, so a crash inside the
 *      type-specific handler still leaves a trail.
 *   4. Dispatch the event to a small per-type handler that does best-effort
 *      cross-referencing into the local payments table.
 *
 * Side effects today are intentionally minimal: there is no Square SDK client
 * in the app yet, so most events have no local record to update. The audit
 * row is the artifact — the Diagnostics panel reads from it and a human can
 * inspect the payload column when a payment didn't reconcile.
 */
final class SquareWebhook
{
    /** Event types subscribed in the Square Developer Dashboard. */
    private const SUBSCRIBED_TYPES = [
        'payment.created',
        'payment.updated',
        'refund.created',
        'refund.updated',
        'dispute.created',
    ];

    private PDO $db;
    private AppSetting $settings;

    public function __construct(?PDO $db = null, ?AppSetting $settings = null)
    {
        $this->db = $db ?? Database::connection();
        $this->settings = $settings ?? new AppSetting();
    }

    /**
     * @return array{0:int,1:string} [httpStatus, responseBody]
     */
    public function handle(string $rawBody, string $signatureHeader, string $notificationUrl): array
    {
        $signatureKey = trim((string) ($this->settings->get('SQUARE_WEBHOOK_SIGNATURE_KEY') ?? ''));
        if ($signatureKey === '') {
            error_log('[square-webhook] no signature key configured; rejecting delivery');
            return [503, 'Webhook signature key not configured.'];
        }

        if (!$this->verifySignature($notificationUrl, $rawBody, $signatureKey, $signatureHeader)) {
            // Diagnostic: if the live key didn't match, try the parked sandbox and
            // production keys and report which (if any) would have verified. Saves
            // a guessing game when the dashboard subscription's env doesn't match
            // the env loaded into the live key.
            $sandboxKey = trim((string) ($this->settings->getStored('SQUARE_WEBHOOK_SIGNATURE_KEY__SANDBOX') ?? ''));
            $prodKey = trim((string) ($this->settings->getStored('SQUARE_WEBHOOK_SIGNATURE_KEY__PRODUCTION') ?? ''));
            $matches = [];
            if ($sandboxKey !== '' && $this->verifySignature($notificationUrl, $rawBody, $sandboxKey, $signatureHeader)) {
                $matches[] = 'SANDBOX';
            }
            if ($prodKey !== '' && $this->verifySignature($notificationUrl, $rawBody, $prodKey, $signatureHeader)) {
                $matches[] = 'PRODUCTION';
            }
            $hint = $matches === [] ? 'no stored key matched (keys may be stale)' : 'matched parked key for: ' . implode(', ', $matches);
            error_log('[square-webhook] signature mismatch for url=' . $notificationUrl . ' — ' . $hint);
            return [401, 'Invalid signature.'];
        }

        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return [400, 'Invalid JSON body.'];
        }

        $eventId = $this->stringOrNull($event['event_id'] ?? null);
        $type = $this->stringOrNull($event['type'] ?? null);
        if ($eventId === null || $type === null) {
            return [400, 'Missing event_id or type.'];
        }

        if ($this->alreadyProcessed($eventId)) {
            // Idempotent ack — Square's retry of an already-handled event.
            return [200, 'Already processed.'];
        }

        $env = strtolower(trim((string) ($this->settings->get('SQUARE_ENVIRONMENT') ?? '')));
        $reference = $this->extractReference($type, $event);
        $rowId = $this->recordReceived($eventId, $type, $env, $reference, $rawBody);

        try {
            [$status, $invoiceId, $note] = $this->dispatch($type, $event);
            $this->markProcessed($rowId, $status, $invoiceId, $note);
            return [200, 'OK.'];
        } catch (Throwable $e) {
            error_log('[square-webhook] handler error for ' . $type . ': ' . $e->getMessage());
            $this->markProcessed($rowId, 'error', null, $this->truncate($e->getMessage(), 250));
            // Square will retry on 5xx, which is what we want for transient errors.
            return [500, 'Handler error.'];
        }
    }

    /** Reconstruct the URL Square signed against. Honors a reverse proxy's forwarded headers. */
    public static function notificationUrlFromServer(array $server): string
    {
        $forwardedProto = isset($server['HTTP_X_FORWARDED_PROTO'])
            ? strtolower(trim((string) $server['HTTP_X_FORWARDED_PROTO']))
            : '';
        if ($forwardedProto !== '') {
            $proto = $forwardedProto;
        } else {
            $https = (string) ($server['HTTPS'] ?? '');
            $proto = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
        }
        $host = (string) ($server['HTTP_X_FORWARDED_HOST'] ?? $server['HTTP_HOST'] ?? 'localhost');
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        return $proto . '://' . $host . $uri;
    }

    public static function signatureHeaderFromServer(array $server): string
    {
        return (string) ($server['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] ?? '');
    }

    /** Square v2 signature scheme: base64(HMAC-SHA256(key, notification_url + body)). */
    private function verifySignature(string $url, string $body, string $key, string $given): bool
    {
        if ($given === '') {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $url . $body, $key, true));
        return hash_equals($expected, $given);
    }

    private function alreadyProcessed(string $eventId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM square_webhook_events WHERE event_id = :id LIMIT 1');
        $stmt->execute(['id' => $eventId]);
        return (bool) $stmt->fetchColumn();
    }

    private function recordReceived(string $eventId, string $type, string $env, ?string $reference, string $payload): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO square_webhook_events
                (event_id, event_type, environment, payment_reference, status, payload, received_at)
             VALUES (:event_id, :event_type, :environment, :payment_reference, :status, :payload, :received_at)'
        );
        $stmt->execute([
            'event_id' => $eventId,
            'event_type' => $type,
            'environment' => $env !== '' ? $env : null,
            'payment_reference' => $reference,
            'status' => 'received',
            'payload' => $payload,
            'received_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function markProcessed(int $rowId, string $status, ?int $invoiceId, ?string $note): void
    {
        $stmt = $this->db->prepare(
            'UPDATE square_webhook_events
                SET status = :status, invoice_id = :invoice_id, message = :message, processed_at = :processed_at
              WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'invoice_id' => $invoiceId,
            'message' => $note,
            'processed_at' => date('Y-m-d H:i:s'),
            'id' => $rowId,
        ]);
    }

    /** Pull the Square identifier we care about for cross-referencing. */
    private function extractReference(string $type, array $event): ?string
    {
        $object = $event['data']['object'] ?? [];
        if (!is_array($object)) {
            return null;
        }
        if (strncmp($type, 'payment.', 8) === 0) {
            return $this->stringOrNull($object['payment']['id'] ?? null);
        }
        if (strncmp($type, 'refund.', 7) === 0) {
            // Refunds carry the originating payment id, which is what we'd match in-app.
            return $this->stringOrNull($object['refund']['payment_id'] ?? null);
        }
        if (strncmp($type, 'dispute.', 8) === 0) {
            // The meaningful cross-reference for a dispute is the payment being
            // disputed — that's what links to our payments table. The dispute's
            // own id stays in the payload column for forensics.
            return $this->stringOrNull($object['dispute']['disputed_payment']['payment_id'] ?? null);
        }
        return null;
    }

    /**
     * Per-type side effects. Returns [status, invoiceId, note] for the audit row.
     *
     *   status = matched   : found and (optionally) updated a local record
     *          = unmatched : event was valid but no local record references it
     *
     * @return array{0:string,1:?int,2:?string}
     */
    private function dispatch(string $type, array $event): array
    {
        if (!in_array($type, self::SUBSCRIBED_TYPES, true)) {
            // Square should never deliver an unsubscribed type, but be defensive.
            return ['unmatched', null, 'Unsubscribed event type.'];
        }

        if ($type === 'payment.created' || $type === 'payment.updated') {
            $payment = $event['data']['object']['payment'] ?? null;
            if (!is_array($payment)) {
                return ['unmatched', null, 'No payment object in payload.'];
            }
            $squareId = $this->stringOrNull($payment['id'] ?? null);
            $squareStatus = strtoupper((string) ($payment['status'] ?? ''));
            if ($squareId === null) {
                return ['unmatched', null, 'Missing payment.id.'];
            }
            $local = $this->findLocalPaymentByReference($squareId);
            if (!$local) {
                // No in-app payment with this Square id — expected today, since the
                // app doesn't yet initiate Square charges. The audit row is the value.
                return ['unmatched', null, 'No in-app payment references ' . $squareId . '.'];
            }
            if ($squareStatus === 'COMPLETED' && (string) ($local['payment_status'] ?? '') !== 'completed') {
                $this->markPaymentCompleted((int) $local['id']);
                return ['matched', (int) $local['invoice_id'], 'Marked completed from Square status COMPLETED.'];
            }
            return ['matched', (int) $local['invoice_id'], 'Square status=' . $squareStatus . '; no local change.'];
        }

        if ($type === 'refund.created' || $type === 'refund.updated') {
            $refund = $event['data']['object']['refund'] ?? null;
            $paymentRef = is_array($refund) ? $this->stringOrNull($refund['payment_id'] ?? null) : null;
            if ($paymentRef === null) {
                return ['unmatched', null, 'Refund payload missing payment_id.'];
            }
            $local = $this->findLocalPaymentByReference($paymentRef);
            if (!$local) {
                return ['unmatched', null, 'Refund references unknown payment ' . $paymentRef . '.'];
            }
            // Refunds aren't a first-class in-app model yet; logging is the value.
            return ['matched', (int) $local['invoice_id'], 'Refund logged for in-app payment #' . $local['id'] . '.'];
        }

        // dispute.created — no in-app dispute model, but pull the reason/amount/state
        // into the audit message so triage doesn't require opening the payload column.
        $dispute = $event['data']['object']['dispute'] ?? null;
        if (!is_array($dispute)) {
            return ['unmatched', null, 'No dispute object in payload.'];
        }
        $paymentRef = $this->stringOrNull($dispute['disputed_payment']['payment_id'] ?? null);
        $reason = (string) ($dispute['reason'] ?? 'unspecified');
        $state = (string) ($dispute['state'] ?? 'unspecified');
        $amount = isset($dispute['amount_money']['amount'])
            ? sprintf('$%.2f %s', ((int) $dispute['amount_money']['amount']) / 100, (string) ($dispute['amount_money']['currency'] ?? ''))
            : 'unknown amount';

        if ($paymentRef === null) {
            return ['unmatched', null, sprintf('Dispute (%s, %s, %s) — no disputed payment id.', $reason, $state, $amount)];
        }

        $local = $this->findLocalPaymentByReference($paymentRef);
        if (!$local) {
            return ['unmatched', null, sprintf('Dispute on Square payment %s (%s, %s, %s) — no in-app payment.', $paymentRef, $reason, $state, $amount)];
        }

        return ['matched', (int) $local['invoice_id'], sprintf('Dispute on in-app payment #%d: %s, %s, %s.', (int) $local['id'], $reason, $state, $amount)];
    }

    private function findLocalPaymentByReference(string $reference): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, invoice_id, payment_status FROM payments
              WHERE transaction_reference = :ref
              ORDER BY id DESC
              LIMIT 1'
        );
        $stmt->execute(['ref' => $reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function markPaymentCompleted(int $paymentId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE payments
                SET payment_status = :s, updated_at = :u
              WHERE id = :id'
        );
        $stmt->execute([
            's' => 'completed',
            'u' => date('Y-m-d H:i:s'),
            'id' => $paymentId,
        ]);
    }

    private function stringOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    private function truncate(string $s, int $max): string
    {
        return strlen($s) <= $max ? $s : substr($s, 0, $max - 1) . '…';
    }
}
