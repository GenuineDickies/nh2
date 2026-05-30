<?php

return function (PDO $db, string $driver): void {
    // Add OpenAI usage tracking onto extractions so the operator can see
    // what each document actually cost and we can sum running totals.
    $extractionCols = [
        ['input_tokens', 'INTEGER NULL'],
        ['output_tokens', 'INTEGER NULL'],
        ['total_tokens', 'INTEGER NULL'],
        ['estimated_cost_cents', 'INTEGER NULL'], // micro-cost in cents * 100 (so 0.06¢ = 6)
        ['reused_from_extraction_id', 'INTEGER NULL'], // set when extraction was copied from a duplicate
    ];

    // Add duplicate-override flag so the operator can post a known duplicate
    // intentionally and we audit the decision.
    $intakeCols = [
        ['duplicate_override', 'TINYINT NOT NULL DEFAULT 0'],
        ['duplicate_of_intake_id', 'INTEGER NULL'],
    ];

    $addColumn = function (string $table, string $name, string $type) use ($db) {
        try {
            $db->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$type}");
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (
                stripos($msg, 'duplicate column') === false
                && stripos($msg, 'duplicate field') === false
                && stripos($msg, 'already exists') === false
            ) {
                throw $e;
            }
        }
    };

    foreach ($extractionCols as [$name, $type]) {
        $addColumn('document_extractions', $name, $type);
    }
    foreach ($intakeCols as [$name, $type]) {
        $addColumn('document_intakes', $name, $type);
    }
};
