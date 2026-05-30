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
    }

    $stmt = $db->prepare(
        'UPDATE users
         SET role = :new_role,
             roles_json = :roles_json,
             updated_at = :updated_at
         WHERE LOWER(email) = :email
           AND (role <> :current_role OR roles_json IS NULL OR roles_json NOT LIKE :admin_role_match)'
    );

    $stmt->execute([
        'new_role' => 'admin',
        'current_role' => 'admin',
        'roles_json' => json_encode(['admin']),
        'admin_role_match' => '%"admin"%',
        'updated_at' => date('Y-m-d H:i:s'),
        'email' => 'admin@wkrllc.com',
    ]);
};
