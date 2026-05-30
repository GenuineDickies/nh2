<?php

namespace App\Models;

use App\Core\Repository;

final class CustomerLinkToken extends Repository
{
    public const PURPOSE_ESTIMATE_APPROVAL = 'estimate_approval';
    public const PURPOSE_INVOICE_VIEW = 'invoice_view';
    public const PURPOSE_STATUS = 'status';
    public const PURPOSE_LOCATION_CONFIRMATION = 'location_confirmation';

    public const PURPOSES = [
        self::PURPOSE_ESTIMATE_APPROVAL,
        self::PURPOSE_INVOICE_VIEW,
        self::PURPOSE_STATUS,
        self::PURPOSE_LOCATION_CONFIRMATION,
    ];

    public function mint(
        string $relatedType,
        int $relatedId,
        string $purpose,
        bool $singleUse = true,
        ?string $expiresAt = null,
        ?int $createdBy = null
    ): string {
        $token = bin2hex(random_bytes(24));
        $stmt = $this->db->prepare(
            'INSERT INTO customer_link_tokens (
                token, related_type, related_id, purpose, single_use,
                expires_at, used_at, created_by, created_at
            ) VALUES (
                :token, :related_type, :related_id, :purpose, :single_use,
                :expires_at, NULL, :created_by, :created_at
            )'
        );
        $stmt->execute([
            'token' => $token,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'purpose' => $purpose,
            'single_use' => $singleUse ? 1 : 0,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
            'created_at' => $this->now(),
        ]);

        return $token;
    }

    public function find(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[a-f0-9]+$/i', $token)) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM customer_link_tokens WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Returns the token row if it is valid (matches type/purpose, not expired,
     * not used if single-use). Returns null otherwise. Does NOT mark used.
     */
    public function lookup(string $token, string $expectedType, string $expectedPurpose): ?array
    {
        $row = $this->find($token);
        if (!$row) {
            return null;
        }
        if ($row['related_type'] !== $expectedType || $row['purpose'] !== $expectedPurpose) {
            return null;
        }
        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
            return null;
        }
        if ((int) $row['single_use'] === 1 && !empty($row['used_at'])) {
            return null;
        }

        return $row;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE customer_link_tokens SET used_at = :used_at WHERE id = :id AND used_at IS NULL');
        $stmt->execute([
            'used_at' => $this->now(),
            'id' => $id,
        ]);
    }

    public function latestForRelated(string $relatedType, int $relatedId, string $purpose): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM customer_link_tokens
             WHERE related_type = :type AND related_id = :id AND purpose = :purpose
             ORDER BY created_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute([
            'type' => $relatedType,
            'id' => $relatedId,
            'purpose' => $purpose,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
