<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Money formatting used everywhere a dollar amount appears in a PDF.
 *
 * Centralised so the entire PDF system shares one convention
 * (e.g. always `$1,234.56`, parentheses for negatives, `+ $250.00`
 * for explicit deltas in change-order style rows).
 */
final class PdfMoney
{
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0.0);
        if ($value < 0) {
            return '($' . number_format(abs($value), 2, '.', ',') . ')';
        }
        return '$' . number_format($value, 2, '.', ',');
    }

    public static function signed(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0.0);
        if ($value > 0) {
            return '+ $' . number_format($value, 2, '.', ',');
        }
        if ($value < 0) {
            return '- $' . number_format(abs($value), 2, '.', ',');
        }
        return '$0.00';
    }
}
