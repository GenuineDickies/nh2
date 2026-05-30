<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Repository;

final class AuditLog extends Repository
{
    public function record(string $action, string $relatedType, int $relatedId, ?array $oldValue = null, ?array $newValue = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, related_type, related_id, old_value_json, new_value_json, ip_address, user_agent, created_at)
             VALUES (:actor_user_id, :action, :related_type, :related_id, :old_value_json, :new_value_json, :ip_address, :user_agent, :created_at)'
        );
        $stmt->execute([
            'actor_user_id' => Auth::userId(),
            'action' => $action,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'old_value_json' => $oldValue ? json_encode($oldValue) : null,
            'new_value_json' => $newValue ? json_encode($newValue) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => $this->now(),
        ]);
    }
}

