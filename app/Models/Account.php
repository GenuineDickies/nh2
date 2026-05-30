<?php

namespace App\Models;

use App\Core\Repository;

final class Account extends Repository
{
    public function all(): array
    {
        return $this->db
            ->query('SELECT * FROM accounting_accounts ORDER BY account_code ASC')
            ->fetchAll();
    }

    public function idForCode(string $code): int
    {
        $stmt = $this->db->prepare('SELECT id FROM accounting_accounts WHERE account_code = :code AND active = 1');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        if (!$id) {
            throw new \RuntimeException('Missing accounting account: ' . $code);
        }

        return (int) $id;
    }
}
