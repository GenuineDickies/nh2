<?php

declare(strict_types=1);

/**
 * Public entry point for Square webhook deliveries.
 *
 * This lives under public/ and ends in .php so both the built-in dev server
 * (via index.php's static-file fall-through) and a production Apache/nginx +
 * PHP-FPM stack execute it directly. That deliberately bypasses the app's
 * auth guard — webhook authentication is the HMAC signature, not a session.
 */

use App\Core\Env;
use App\Services\SquareWebhook;

require dirname(__DIR__, 2) . '/app/bootstrap.php';

Env::load(dirname(__DIR__, 2) . '/.env');

// Square only sends POST. Reject anything else early — and make HEAD/GET checks
// from monitoring tools cheap.
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method Not Allowed';
    exit;
}

$rawBody = (string) file_get_contents('php://input');
$signature = SquareWebhook::signatureHeaderFromServer($_SERVER);
$notificationUrl = SquareWebhook::notificationUrlFromServer($_SERVER);

[$status, $body] = (new SquareWebhook())->handle($rawBody, $signature, $notificationUrl);

http_response_code($status);
header('Content-Type: text/plain; charset=UTF-8');
echo $body;
