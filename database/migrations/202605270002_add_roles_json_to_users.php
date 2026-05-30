<?php

return function (PDO $db, string $driver): void {
    if ($driver === 'mysql') {
        $columns = $db->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
        $hasRolesJson = false;
        foreach ($columns as $column) {
            if (($column['Field'] ?? '') === 'roles_json') {
                $hasRolesJson = true;
                break;
            }
        }
        if (!$hasRolesJson) {
            $db->exec("ALTER TABLE users ADD COLUMN roles_json TEXT NULL AFTER role");
        }
    } else {
        $columns = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
        $hasRolesJson = false;
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === 'roles_json') {
                $hasRolesJson = true;
                break;
            }
        }
        if (!$hasRolesJson) {
            $db->exec("ALTER TABLE users ADD COLUMN roles_json TEXT NULL");
        }
    }

    $rows = $db->query('SELECT id, role, roles_json FROM users')->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare('UPDATE users SET roles_json = :roles_json, updated_at = :updated_at WHERE id = :id');

    foreach ($rows as $row) {
        $existing = json_decode((string) ($row['roles_json'] ?? ''), true);
        if (is_array($existing) && $existing !== []) {
            continue;
        }

        $role = trim((string) ($row['role'] ?? 'operator'));
        if ($role === '') {
            $role = 'operator';
        }

        $stmt->execute([
            'id' => (int) $row['id'],
            'roles_json' => json_encode([$role]),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
};
