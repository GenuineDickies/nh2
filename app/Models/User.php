<?php

namespace App\Models;

use App\Core\Repository;

final class User extends Repository
{
    public const ROLES = ['operator', 'admin'];
    public const ROLE_LABELS = [
        'operator' => 'Operator',
        'admin' => 'Admin',
    ];

    public static function normalizeRoles(array $roles): array
    {
        $normalized = [];

        foreach ($roles as $role) {
            $role = strtolower(trim((string) $role));
            if (in_array($role, self::ROLES, true) && !in_array($role, $normalized, true)) {
                $normalized[] = $role;
            }
        }

        return $normalized !== [] ? $normalized : ['operator'];
    }

    public static function rolesFromUser(array $user): array
    {
        $decoded = json_decode((string) ($user['roles_json'] ?? ''), true);
        if (is_array($decoded)) {
            return self::normalizeRoles($decoded);
        }

        return self::normalizeRoles([(string) ($user['role'] ?? 'operator')]);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst($role);
    }

    public static function roleLabels(array $roles): string
    {
        return implode(', ', array_map([self::class, 'roleLabel'], self::normalizeRoles($roles)));
    }

    public static function hasRole(array $user, string $role): bool
    {
        return in_array($role, self::rolesFromUser($user), true);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateProfile(int $id, string $name, string $email, ?array $roles = null): void
    {
        $params = [
            'id' => $id,
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'updated_at' => $this->now(),
        ];

        $roleSql = '';
        if ($roles !== null) {
            $normalizedRoles = self::normalizeRoles($roles);
            $params['role'] = in_array('admin', $normalizedRoles, true) ? 'admin' : $normalizedRoles[0];
            $params['roles_json'] = json_encode($normalizedRoles);
            $roleSql = ', role = :role, roles_json = :roles_json';
        }

        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email' . $roleSql . ',
                 updated_at = :updated_at
             WHERE id = :id'
        );

        $stmt->execute($params);
    }

    public function updatePassword(int $id, string $password): void
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        $stmt = $this->db->prepare(
            'UPDATE users
             SET password_hash = :password_hash,
                 updated_at = :updated_at
             WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => $this->now(),
        ]);
    }

    public function create(string $email, string $password, string $name, string|array $roles = 'operator'): int
    {
        $normalizedRoles = self::normalizeRoles(is_array($roles) ? $roles : [$roles]);
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters.');
        }

        $primaryRole = in_array('admin', $normalizedRoles, true) ? 'admin' : $normalizedRoles[0];
        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password_hash, name, role, roles_json, active, created_at, updated_at)
             VALUES (:email, :password_hash, :name, :role, :roles_json, 1, :created_at, :updated_at)'
        );
        $stmt->execute([
            'email' => strtolower(trim($email)),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => trim($name) !== '' ? trim($name) : strtolower(trim($email)),
            'role' => $primaryRole,
            'roles_json' => json_encode($normalizedRoles),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function recordLogin(int $id): void
    {
        // Use distinct placeholders so MySQL's native prepares (which require
        // each named param to be bound exactly once) accept the statement.
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id');
        $now = $this->now();
        $stmt->execute([
            'last_login_at' => $now,
            'updated_at' => $now,
            'id' => $id,
        ]);
    }

    public function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, (string) $user['password_hash']);
    }
}
