<?php

namespace App\Core;

final class View
{
    public static function render(string $template, array $__viewData = []): string
    {
        $viewPath = dirname(__DIR__) . '/Views/' . $template . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($__viewData, EXTR_SKIP);
        unset($__viewData);

        ob_start();
        require $viewPath;

        return ob_get_clean() ?: '';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function address(?array $source, string $fallback = ''): string
    {
        if (!$source) {
            return $fallback;
        }

        $street = trim((string) ($source['address_line_1'] ?? ''));
        if (!empty($source['address_line_2'])) {
            $street = trim($street . ' ' . (string) $source['address_line_2']);
        }

        $city = trim((string) ($source['city'] ?? ''));
        $state = trim((string) ($source['state'] ?? ''));
        $postal = trim((string) ($source['postal_code'] ?? ''));

        $cityState = trim($city);
        if ($state !== '') {
            $cityState = $cityState === '' ? $state : $cityState . ', ' . $state;
        }
        if ($postal !== '') {
            $cityState = $cityState === '' ? $postal : $cityState . ' ' . $postal;
        }

        $parts = array_filter([$street, $cityState], static fn ($p) => $p !== '');
        if (!$parts) {
            return $fallback;
        }

        return implode(', ', $parts);
    }

    public static function asset(string $path): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($basePath === '' || $basePath === '.') {
            $basePath = '';
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}
