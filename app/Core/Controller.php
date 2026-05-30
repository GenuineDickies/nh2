<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $template, array $data = []): void
    {
        echo View::render($template, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function input(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $default;

        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return trim((string) $value);
        }

        return $default;
    }

    protected function query(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $default;

        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return trim((string) $value);
        }

        return $default;
    }
}
