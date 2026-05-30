<?php

namespace App\Models;

use App\Core\Repository;

final class PasswordResetToken extends Repository
{
    /** Token TTL in seconds (1 hour). */
    public const TTL_SECONDS = 3600;

    public static function generateRawToken(): string
    {
        // 32 random bytes -> 43-char URL-safe base64 (no padding).
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * Invalidate any previously-issued unused tokens for the user, then
     * insert a fresh one. Only the most-recent token can ever complete a
     * reset, so an old (intercepted-but-unused) link can't replay later.
     */
    public function issue(int $userId, string $rawToken, ?string $ip = null): void
    {
        $now = $this->now();
        $expiresAt = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $this->db->prepare(
            'UPDATE password_reset_tokens
             SET used_at = :used_at
             WHERE user_id = :user_id AND used_at IS NULL'
        )->execute([
            'used_at' => $now,
            'user_id' => $userId,
        ]);

        $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, used_at, requested_ip, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NULL, :requested_ip, :created_at)'
        )->execute([
            'user_id' => $userId,
            'token_hash' => self::hash($rawToken),
            'expires_at' => $expiresAt,
            'requested_ip' => $ip,
            'created_at' => $now,
        ]);
    }

    public function findActiveByRaw(string $rawToken): ?array
    {
        if ($rawToken === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM password_reset_tokens
             WHERE token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at > :now'
        );
        $stmt->execute([
            'token_hash' => self::hash($rawToken),
            'now' => $this->now(),
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $this->db->prepare('UPDATE password_reset_tokens SET used_at = :used_at WHERE id = :id')
            ->execute([
                'used_at' => $this->now(),
                'id' => $id,
            ]);
    }
}
