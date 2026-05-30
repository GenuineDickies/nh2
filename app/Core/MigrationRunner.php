<?php

namespace App\Core;

use PDO;

final class MigrationRunner
{
    public function __construct(private PDO $db)
    {
    }

    public function run(string $migrationPath): array
    {
        $this->ensureMigrationTable();
        $applied = $this->appliedMigrations();
        $ran = [];

        foreach (glob(rtrim($migrationPath, '/\\') . '/*.php') ?: [] as $file) {
            $name = basename($file);

            if (isset($applied[$name])) {
                continue;
            }

            $migration = require $file;
            $this->db->beginTransaction();

            try {
                $migration($this->db, $this->db->getAttribute(PDO::ATTR_DRIVER_NAME));
                $stmt = $this->db->prepare('INSERT INTO migrations (migration, applied_at) VALUES (:migration, :applied_at)');
                $stmt->execute([
                    'migration' => $name,
                    'applied_at' => date('Y-m-d H:i:s'),
                ]);

                // MySQL implicitly commits a transaction when it sees DDL (CREATE/ALTER TABLE),
                // which leaves no active transaction by the time we reach commit(). Only call
                // commit/rollBack when a transaction is actually still open.
                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }
                $ran[] = $name;
            } catch (\Throwable $exception) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $exception;
            }
        }

        return $ran;
    }

    private function ensureMigrationTable(): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $idColumn = $driver === 'mysql'
            ? 'id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
            : 'id INTEGER PRIMARY KEY AUTOINCREMENT';

        $this->db->exec(sprintf(
            'CREATE TABLE IF NOT EXISTS migrations (
                %s,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            )',
            $idColumn
        ));
    }

    private function appliedMigrations(): array
    {
        $rows = $this->db->query('SELECT migration FROM migrations')->fetchAll();
        $applied = [];

        foreach ($rows as $row) {
            $applied[$row['migration']] = true;
        }

        return $applied;
    }
}
