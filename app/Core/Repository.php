<?php

namespace App\Core;

use PDO;

abstract class Repository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

