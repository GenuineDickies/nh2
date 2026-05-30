<?php

namespace App\Core;

use App\Models\User;

final class Auth
{
    /** Paths the auth guard allows without a session. */
    public const PUBLIC_PATHS = [
        '/login',
        '/logout',
        '/setup',
        '/forgot-password',
        '/reset-password',
    ];

    /** Path prefixes the auth guard allows without a session (customer portal links). */
    public const PUBLIC_PREFIXES = [
        '/p/',
    ];

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        $params = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $params['lifetime'] ?? 0,
            'path' => '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(int $userId): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        self::startSession();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        $id = self::userId();
        if ($id === null) {
            return null;
        }

        static $cached = null;
        static $cachedId = null;
        if ($cachedId !== $id) {
            $cached = (new User())->find($id);
            $cachedId = $id;
            if (!$cached || (int) $cached['active'] !== 1) {
                self::logout();
                return null;
            }
        }

        return $cached;
    }

    public static function isPublicPath(string $path): bool
    {
        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return true;
        }
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }
}
