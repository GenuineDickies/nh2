<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Low-level PDF page writer.
 *
 * Plain PDF 1.4 output, no external library. Supports the small set of
 * primitives the templates need: text in two Helvetica weights, filled
 * rectangles, stroked rectangles, horizontal lines, and right/center
 * alignment via Helvetica character-width approximation.
 *
 * Coordinates are in PDF points (1pt = 1/72in). Origin is bottom-left,
 * but the helpers below accept top-down Y values (relative to page top)
 * because every section grows downward.
 *
 * One page at a time. Pagination is handled by the caller (PdfDocument)
 * via newPage() — when content would overflow it emits the current page
 * and starts a fresh one.
 */
final class PdfRenderer
{
    public const PAGE_WIDTH = 612.0;  // Letter
    public const PAGE_HEIGHT = 792.0;

    public const FONT_REGULAR = 'F1';
    public const FONT_BOLD = 'F2';

    /** Approximate average glyph width as a fraction of font size. Helvetica ≈ 0.5; Bold ≈ 0.55. */
    private const WIDTH_FACTOR_REGULAR = 0.50;
    private const WIDTH_FACTOR_BOLD = 0.55;

    /** @var string[] One stream per page. */
    private array $pageStreams = [];

    private string $current = '';

    public function __construct()
    {
        $this->beginPage();
    }

    public function newPage(): void
    {
        $this->pageStreams[] = $this->current;
        $this->beginPage();
    }

    private function beginPage(): void
    {
        // Start each page with white background by skipping fill — PDF default.
        $this->current = "q\n";
    }

    /**
     * Finalise the document and return the binary PDF as a string.
     */
    public function output(): string
    {
        $this->pageStreams[] = $this->current;
        $pages = $this->pageStreams;
        $this->pageStreams = [];
        $this->current = '';

        return $this->buildDocument($pages);
    }

    // ─── Primitives ─────────────────────────────────────────────────────

    /**
     * Draw a filled rectangle at top-left (x, yTop) with width w, height h.
     * Colour is RGB in 0..1.
     */
    public function fillRect(float $x, float $yTop, float $w, float $h, float $r, float $g, float $b): void
    {
        $yBottom = self::PAGE_HEIGHT - $yTop - $h;
        $this->current .= sprintf(
            "%s %s %s rg\n%s %s %s %s re\nf\n",
            $this->num($r), $this->num($g), $this->num($b),
            $this->num($x), $this->num($yBottom), $this->num($w), $this->num($h)
        );
    }

    public function strokeRect(float $x, float $yTop, float $w, float $h, float $line = 0.5): void
    {
        $yBottom = self::PAGE_HEIGHT - $yTop - $h;
        $this->current .= sprintf(
            "0 0 0 RG\n%s w\n%s %s %s %s re\nS\n",
            $this->num($line),
            $this->num($x), $this->num($yBottom), $this->num($w), $this->num($h)
        );
    }

    public function hLine(float $x, float $yTop, float $w, float $line = 0.5): void
    {
        $y = self::PAGE_HEIGHT - $yTop;
        $this->current .= sprintf(
            "0 0 0 RG\n%s w\n%s %s m\n%s %s l\nS\n",
            $this->num($line),
            $this->num($x), $this->num($y),
            $this->num($x + $w), $this->num($y)
        );
    }

    /**
     * Write a single line of text. yBaseline is measured from the top of
     * the page; the renderer converts it to PDF's bottom-up coordinates.
     */
    public function text(
        string $text,
        float $x,
        float $yBaseline,
        float $size = 10.0,
        string $font = self::FONT_REGULAR,
        float $r = 0.0,
        float $g = 0.0,
        float $b = 0.0
    ): void {
        if ($text === '') {
            return;
        }
        $y = self::PAGE_HEIGHT - $yBaseline;
        $this->current .= sprintf(
            "BT\n/%s %s Tf\n%s %s %s rg\n%s %s Td\n(%s) Tj\nET\n",
            $font,
            $this->num($size),
            $this->num($r), $this->num($g), $this->num($b),
            $this->num($x), $this->num($y),
            $this->escapeString($text)
        );
    }

    public function textRight(
        string $text,
        float $xRight,
        float $yBaseline,
        float $size = 10.0,
        string $font = self::FONT_REGULAR,
        float $r = 0.0,
        float $g = 0.0,
        float $b = 0.0
    ): void {
        $width = self::measure($text, $size, $font);
        $this->text($text, $xRight - $width, $yBaseline, $size, $font, $r, $g, $b);
    }

    public function textCenter(
        string $text,
        float $xCenter,
        float $yBaseline,
        float $size = 10.0,
        string $font = self::FONT_REGULAR,
        float $r = 0.0,
        float $g = 0.0,
        float $b = 0.0
    ): void {
        $width = self::measure($text, $size, $font);
        $this->text($text, $xCenter - ($width / 2.0), $yBaseline, $size, $font, $r, $g, $b);
    }

    /**
     * Approximate the rendered width (in points) of a Helvetica string.
     * Uses a single average glyph width per weight; sufficient for the
     * column-alignment work the templates need.
     */
    public static function measure(string $text, float $size, string $font = self::FONT_REGULAR): float
    {
        $factor = $font === self::FONT_BOLD ? self::WIDTH_FACTOR_BOLD : self::WIDTH_FACTOR_REGULAR;
        return mb_strlen($text) * $size * $factor;
    }

    /**
     * Greedily wrap $text into lines that fit $maxWidth at the given font/size.
     *
     * @return string[]
     */
    public static function wrap(string $text, float $maxWidth, float $size, string $font = self::FONT_REGULAR): array
    {
        if ($text === '') {
            return [''];
        }

        $lines = [];
        foreach (preg_split('/\r?\n/', $text) ?: [''] as $paragraph) {
            $words = preg_split('/\s+/', trim($paragraph)) ?: [];
            if (!$words || ($words[0] ?? '') === '') {
                $lines[] = '';
                continue;
            }
            $line = '';
            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line . ' ' . $word;
                if (self::measure($candidate, $size, $font) <= $maxWidth) {
                    $line = $candidate;
                    continue;
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }
                // Single word longer than the column: hard-break by characters.
                if (self::measure($word, $size, $font) > $maxWidth) {
                    $chunk = '';
                    $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    foreach ($chars as $char) {
                        $next = $chunk . $char;
                        if (self::measure($next, $size, $font) > $maxWidth && $chunk !== '') {
                            $lines[] = $chunk;
                            $chunk = $char;
                        } else {
                            $chunk = $next;
                        }
                    }
                    $line = $chunk;
                } else {
                    $line = $word;
                }
            }
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines ?: [''];
    }

    // ─── Document build ─────────────────────────────────────────────────

    /**
     * Compose the final PDF bytes from per-page content streams.
     *
     * @param string[] $pages
     */
    private function buildDocument(array $pages): string
    {
        $objects = [];
        $catalogId = 1;
        $pagesId = 2;
        $fontRegularId = 3;
        $fontBoldId = 4;

        $firstPageObj = 5;
        $pageObjectIds = [];
        $contentObjectIds = [];
        for ($i = 0; $i < count($pages); $i++) {
            $pageObjectIds[] = $firstPageObj + ($i * 2);
            $contentObjectIds[] = $firstPageObj + ($i * 2) + 1;
        }
        $pageRefs = implode(' ', array_map(static fn ($id) => $id . ' 0 R', $pageObjectIds));

        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        $objects[$pagesId] = "<< /Type /Pages /Kids [{$pageRefs}] /Count " . count($pages) . " >>";
        $objects[$fontRegularId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontBoldId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        foreach ($pages as $i => $stream) {
            $stream .= "Q\n";
            $pageId = $pageObjectIds[$i];
            $contentId = $contentObjectIds[$i];
            $w = self::PAGE_WIDTH;
            $h = self::PAGE_HEIGHT;
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R "
                . "/MediaBox [0 0 {$w} {$h}] "
                . "/Resources << /Font << /F1 {$fontRegularId} 0 R /F2 {$fontBoldId} 0 R >> >> "
                . "/Contents {$contentId} 0 R >>";
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xrefStart = strlen($pdf);
        $count = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF\n";

        return $pdf;
    }

    private function num(float $value): string
    {
        // PDF parsers accept decimal numbers. Trim to 3 places, no thousand separators.
        $s = number_format($value, 3, '.', '');
        // Strip pointless trailing zeros.
        if (str_contains($s, '.')) {
            $s = rtrim(rtrim($s, '0'), '.');
            if ($s === '' || $s === '-') {
                $s = '0';
            }
        }
        return $s;
    }

    /**
     * Escape a string for use inside a PDF literal. Non-ASCII characters
     * outside CP1252 are dropped because the embedded fonts are not
     * subsetted; replacements (em-dash → "-", etc.) are mapped first.
     */
    private function escapeString(string $value): string
    {
        $value = strtr($value, [
            "\u{2013}" => '-', // en dash
            "\u{2014}" => '-', // em dash
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2026}" => '...',
            "\u{00A0}" => ' ',
        ]);

        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
        }

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $converted);
    }
}
