<?php

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = Env::get('DB_DSN');

        if (!$dsn) {
            $storagePath = dirname(__DIR__, 2) . '/storage/app.sqlite';
            $dsn = 'sqlite:' . $storagePath;
        }

        $user = Env::get('DB_USER', '');
        $pass = Env::get('DB_PASS', '');

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        if ($driver === 'mysql') {
            // Drop ONLY_FULL_GROUP_BY so queries that group by a primary key and
            // SELECT the rest of that table behave the same way they do on SQLite.
            // MySQL 8 already accepts most of these via functional-dependency
            // detection; relaxing the mode protects us on older MySQL versions
            // and against future sql_mode tightening. The other strict-mode
            // flags stay on so bad data still fails fast.
            $pdo->exec(
                "SET SESSION sql_mode = "
                . "'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            );
        }

        self::$connection = $pdo;

        return $pdo;
    }
}

