<?php

declare(strict_types=1);

use App\Core\Env;
use App\Services\Mailer;

require dirname(__DIR__) . '/app/bootstrap.php';
Env::load(dirname(__DIR__) . '/.env');

$opts = getopt('', ['to::']);
$to = isset($opts['to']) ? (string) $opts['to'] : '';

if ($to === '') {
    fwrite(STDERR, "Usage: php scripts/_test_mailer.php --to=you@example.com\n");
    exit(1);
}

$driver = Env::get('MAIL_DRIVER') ?? 'log';
echo "MAIL_DRIVER = {$driver}\n";
if ($driver === 'smtp') {
    echo "SMTP_HOST       = " . (Env::get('SMTP_HOST') ?? '') . "\n";
    echo "SMTP_PORT       = " . (Env::get('SMTP_PORT') ?? '') . "\n";
    echo "SMTP_ENCRYPTION = " . (Env::get('SMTP_ENCRYPTION') ?? '') . "\n";
    echo "SMTP_USERNAME   = " . (Env::get('SMTP_USERNAME') ?? '(empty)') . "\n";
    echo "SMTP_PASSWORD   = " . (Env::get('SMTP_PASSWORD') ? '(set)' : '(empty)') . "\n";
}
echo "MAIL_FROM       = " . (Env::get('MAIL_FROM') ?? '') . "\n";
echo "MAIL_FROM_NAME  = " . (Env::get('MAIL_FROM_NAME') ?? '') . "\n";
echo "\nSending test message to {$to}...\n";

$body = "This is a test message from the Solo Roadside mailer.\n"
    . "Sent at " . date('c') . " from the local development server.\n";

$ok = (new Mailer())->send($to, 'Solo Roadside mailer test', $body);
echo $ok ? "OK\n" : "FAILED — see PHP error log for details.\n";
exit($ok ? 0 : 2);
