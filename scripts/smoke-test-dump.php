<?php

$path = $argv[1] ?? null;
if (!$path || !is_file($path)) {
    fwrite(STDERR, "usage: php scripts/smoke-test-dump.php <file.pdf>\n");
    exit(1);
}
$content = file_get_contents($path);
// Match (...) Tj while honoring \( and \).
preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/', $content, $m);
foreach ($m[1] as $inner) {
    $inner = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $inner);
    echo $inner . PHP_EOL;
}
