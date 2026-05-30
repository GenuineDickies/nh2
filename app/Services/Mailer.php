<?php

namespace App\Services;

use App\Core\Env;
use RuntimeException;

/**
 * Minimal outbound mailer.
 *
 * Driver is selected via the MAIL_DRIVER env var:
 *   - "log"  (default) writes every message to storage/sent-mail/*.eml so the
 *            dev can inspect what would have been sent without needing real
 *            mail infrastructure. Safe for local dev.
 *   - "mail" hands off to PHP's mail() — requires a working sendmail / SMTP
 *            relay configured in php.ini. Typically works on SiteGround;
 *            usually does NOT work out of the box on Windows.
 *   - "smtp" speaks SMTP directly to a remote server using raw sockets. No
 *            external libraries required. Configure via:
 *                SMTP_HOST          (required, e.g. smtp.gmail.com)
 *                SMTP_PORT          (default 587)
 *                SMTP_USERNAME      (optional, enables AUTH LOGIN)
 *                SMTP_PASSWORD
 *                SMTP_ENCRYPTION    tls (STARTTLS, default), ssl (SMTPS), none
 *                SMTP_TIMEOUT       seconds, default 15
 */
final class Mailer
{
    public function send(string $to, string $subject, string $textBody): bool
    {
        $driver = strtolower((string) (Env::get('MAIL_DRIVER') ?? 'log'));
        $fromAddress = (string) (Env::get('MAIL_FROM') ?? 'no-reply@localhost');
        $fromName = (string) (Env::get('MAIL_FROM_NAME') ?? 'Solo Roadside');

        $to = $this->stripHeaderInjection($to);
        $subject = $this->stripHeaderInjection($subject);

        switch ($driver) {
            case 'mail':
                return $this->sendViaMailFunction($to, $subject, $textBody, $fromAddress, $fromName);
            case 'smtp':
                return $this->sendViaSmtp($to, $subject, $textBody, $fromAddress, $fromName);
            case 'log':
            default:
                return $this->sendViaLog($to, $subject, $textBody, $fromAddress, $fromName);
        }
    }

    // ---------------------------------------------------------------- drivers

    private function sendViaMailFunction(string $to, string $subject, string $body, string $fromAddress, string $fromName): bool
    {
        $headers = [
            'From: ' . $this->formatAddress($fromName, $fromAddress),
            'Reply-To: ' . $fromAddress,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        $ok = mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$ok) {
            error_log("Mailer[mail]: mail() returned false for {$to}");
        }
        return $ok;
    }

    private function sendViaLog(string $to, string $subject, string $body, string $fromAddress, string $fromName): bool
    {
        $dir = dirname(__DIR__, 2) . '/storage/sent-mail';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log('Mailer[log]: failed to create ' . $dir);
            return false;
        }

        $slug = preg_replace('/[^A-Za-z0-9._-]+/', '_', $to) ?: 'mail';
        $filename = sprintf('%s/%s-%s.eml', $dir, date('Ymd-His'), $slug);
        $contents = 'From: ' . $this->formatAddress($fromName, $fromAddress) . "\r\n"
            . "To: {$to}\r\n"
            . "Subject: {$subject}\r\n"
            . 'Date: ' . date('r') . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "\r\n"
            . $body;

        if (file_put_contents($filename, $contents) === false) {
            error_log('Mailer[log]: failed to write ' . $filename);
            return false;
        }
        return true;
    }

    private function sendViaSmtp(string $to, string $subject, string $body, string $fromAddress, string $fromName): bool
    {
        $host = (string) Env::get('SMTP_HOST');
        if ($host === '') {
            error_log('Mailer[smtp]: SMTP_HOST is empty');
            return false;
        }

        $encryption = strtolower((string) (Env::get('SMTP_ENCRYPTION') ?? 'tls'));
        $port = (int) (Env::get('SMTP_PORT') ?? ($encryption === 'ssl' ? 465 : 587));
        $username = (string) (Env::get('SMTP_USERNAME') ?? '');
        $password = (string) (Env::get('SMTP_PASSWORD') ?? '');
        $timeout = max(1, (int) (Env::get('SMTP_TIMEOUT') ?? 15));

        $transport = $encryption === 'ssl' ? 'tls://' : 'tcp://';
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $transport . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );
        if (!$socket) {
            error_log("Mailer[smtp]: connect to {$host}:{$port} failed: {$errstr} ({$errno})");
            return false;
        }
        stream_set_timeout($socket, $timeout);

        try {
            $this->smtpExpect($socket, 220);
            $this->smtpCommand($socket, 'EHLO ' . $this->ehloName());
            $this->smtpExpect($socket, 250);

            if ($encryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS');
                $this->smtpExpect($socket, 220);
                if (!stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                        | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT
                        | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                )) {
                    throw new RuntimeException('STARTTLS handshake failed');
                }
                // Re-EHLO over the encrypted channel — required by spec.
                $this->smtpCommand($socket, 'EHLO ' . $this->ehloName());
                $this->smtpExpect($socket, 250);
            }

            if ($username !== '') {
                $this->smtpCommand($socket, 'AUTH LOGIN');
                $this->smtpExpect($socket, 334);
                $this->smtpCommand($socket, base64_encode($username));
                $this->smtpExpect($socket, 334);
                $this->smtpCommand($socket, base64_encode($password));
                $this->smtpExpect($socket, 235);
            }

            $envelopeFrom = $this->extractAddress($fromAddress);
            $envelopeTo = $this->extractAddress($to);

            $this->smtpCommand($socket, 'MAIL FROM:<' . $envelopeFrom . '>');
            $this->smtpExpect($socket, 250);
            $this->smtpCommand($socket, 'RCPT TO:<' . $envelopeTo . '>');
            $this->smtpExpect($socket, [250, 251]);
            $this->smtpCommand($socket, 'DATA');
            $this->smtpExpect($socket, 354);

            $message = 'From: ' . $this->formatAddress($fromName, $fromAddress) . "\r\n"
                . 'To: ' . $to . "\r\n"
                . 'Subject: ' . $subject . "\r\n"
                . 'Date: ' . date('r') . "\r\n"
                . 'MIME-Version: 1.0' . "\r\n"
                . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
                . 'Content-Transfer-Encoding: 8bit' . "\r\n"
                . "\r\n"
                . $this->dotStuff($body)
                . "\r\n.";
            $this->smtpCommand($socket, $message);
            $this->smtpExpect($socket, 250);

            $this->smtpCommand($socket, 'QUIT');
            // Don't strictly need to read the 221 response.
        } catch (\Throwable $e) {
            error_log('Mailer[smtp]: ' . $e->getMessage());
            @fclose($socket);
            return false;
        }

        @fclose($socket);
        return true;
    }

    // ----------------------------------------------------------- SMTP helpers

    /** @param resource $socket */
    private function smtpCommand($socket, string $line): void
    {
        $written = @fwrite($socket, $line . "\r\n");
        if ($written === false) {
            throw new RuntimeException('Write to SMTP socket failed');
        }
    }

    /**
     * @param resource $socket
     * @param int|int[] $expected
     */
    private function smtpExpect($socket, $expected): string
    {
        $expected = is_array($expected) ? $expected : [$expected];
        $response = '';
        while (!feof($socket)) {
            $line = @fgets($socket, 1024);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                $detail = !empty($meta['timed_out']) ? 'timed out' : 'closed';
                throw new RuntimeException("Read from SMTP socket failed ({$detail})");
            }
            $response .= $line;
            // Multi-line responses use "XYZ-" continuation, final line uses "XYZ ".
            if (strlen($line) >= 4 && $line[3] === ' ') {
                $code = (int) substr($line, 0, 3);
                if (!in_array($code, $expected, true)) {
                    throw new RuntimeException("SMTP unexpected response: " . rtrim($response));
                }
                return $response;
            }
        }
        throw new RuntimeException('SMTP connection closed before response: ' . rtrim($response));
    }

    private function dotStuff(string $body): string
    {
        // RFC 5321: any line in the message body that starts with a dot must
        // be escaped with an extra leading dot so the terminator isn't faked.
        $normalized = preg_replace("/\r\n|\r|\n/", "\r\n", $body) ?? $body;
        return preg_replace("/^\./m", '..', $normalized) ?? $normalized;
    }

    private function ehloName(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host !== '') {
            return preg_replace('/:\d+$/', '', $host) ?: 'localhost';
        }
        return gethostname() ?: 'localhost';
    }

    // ----------------------------------------------------------- shared utils

    private function formatAddress(string $name, string $email): string
    {
        $name = trim($this->stripHeaderInjection($name));
        $email = $this->stripHeaderInjection($email);
        if ($name === '') {
            return $email;
        }
        return sprintf('%s <%s>', $name, $email);
    }

    private function extractAddress(string $value): string
    {
        // Accepts either "foo@bar" or "Name <foo@bar>" and returns the bare address.
        if (preg_match('/<([^>]+)>/', $value, $m)) {
            return trim($m[1]);
        }
        return trim($value);
    }

    private function stripHeaderInjection(string $value): string
    {
        return preg_replace("/[\r\n]+/", ' ', $value) ?? '';
    }
}
