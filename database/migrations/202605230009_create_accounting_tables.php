<?php

return function (PDO $db, string $driver): void {
    $id = $driver === 'mysql' ? 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fkUnsigned = $driver === 'mysql' ? 'INT UNSIGNED' : 'INTEGER';
    $bool = $driver === 'mysql' ? 'TINYINT(1)' : 'INTEGER';
    $uniqueSource = $driver === 'mysql'
        ? 'UNIQUE KEY ledger_entries_source_unique (source_type, source_id)'
        : 'UNIQUE (source_type, source_id)';

    $db->exec("
        CREATE TABLE IF NOT EXISTS accounting_accounts (
            id {$id},
            account_code VARCHAR(20) NOT NULL UNIQUE,
            account_name VARCHAR(120) NOT NULL,
            account_type VARCHAR(30) NOT NULL,
            parent_account_id {$fkUnsigned} NULL,
            active {$bool} NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (parent_account_id) REFERENCES accounting_accounts(id)
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS ledger_entries (
            id {$id},
            entry_number VARCHAR(32) NOT NULL UNIQUE,
            source_type VARCHAR(60) NOT NULL,
            source_id {$fkUnsigned} NOT NULL,
            entry_date DATE NOT NULL,
            memo VARCHAR(255) NOT NULL,
            posted {$bool} NOT NULL DEFAULT 1,
            posted_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            {$uniqueSource}
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS ledger_entry_lines (
            id {$id},
            ledger_entry_id {$fkUnsigned} NOT NULL,
            account_id {$fkUnsigned} NOT NULL,
            debit DECIMAL(10, 2) NOT NULL DEFAULT 0,
            credit DECIMAL(10, 2) NOT NULL DEFAULT 0,
            memo VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (ledger_entry_id) REFERENCES ledger_entries(id),
            FOREIGN KEY (account_id) REFERENCES accounting_accounts(id)
        )
    ");

    $now = date('Y-m-d H:i:s');
    $accounts = [
        ['1000', 'Cash', 'asset'],
        ['1010', 'Checking', 'asset'],
        ['1050', 'Square Clearing', 'asset'],
        ['1100', 'Accounts Receivable', 'asset'],
        ['1200', 'Parts Inventory', 'asset'],
        ['2000', 'Accounts Payable', 'liability'],
        ['2010', 'Credit Card Payable', 'liability'],
        ['2020', 'Sales Tax Payable', 'liability'],
        ['2050', 'Core Deposits Payable', 'liability'],
        ['4000', 'Service Revenue', 'income'],
        ['4010', 'Parts Revenue', 'income'],
        ['4020', 'Fee Revenue', 'income'],
        ['5000', 'Parts COGS', 'cogs'],
        ['5010', 'Materials COGS', 'cogs'],
        ['5020', 'Fuel Delivery COGS', 'cogs'],
        ['6000', 'Advertising', 'expense'],
        ['6010', 'Fuel Expense', 'expense'],
        ['6020', 'Tools and Equipment', 'expense'],
        ['6030', 'Consumables', 'expense'],
        ['6040', 'PPE and Safety', 'expense'],
        ['6050', 'Office/Admin', 'expense'],
        ['6060', 'Payment Processing Fees', 'expense'],
        ['6070', 'Meals/Personal Non-Business Review', 'expense'],
    ];
    $stmt = $db->prepare(
        'INSERT INTO accounting_accounts (account_code, account_name, account_type, active, created_at, updated_at)
         SELECT :account_code, :account_name, :account_type, 1, :created_at, :updated_at
         WHERE NOT EXISTS (SELECT 1 FROM accounting_accounts WHERE account_code = :lookup_code)'
    );

    foreach ($accounts as [$code, $name, $type]) {
        $stmt->execute([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'created_at' => $now,
            'updated_at' => $now,
            'lookup_code' => $code,
        ]);
    }
};
