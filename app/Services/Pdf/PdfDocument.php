<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * High-level page composer.
 *
 * Provides the section primitives used by every document template:
 *   masthead   — logo placeholder + company info + document-meta table
 *   banner     — black bar with title (left) and page number (right)
 *   metaStrip  — customer / vehicle / totals row at the top of the body
 *   detailBar  — single-row strip for service-type / payment-method / etc.
 *   chargesTable — itemised charges with subtotal / tax / total rows
 *   kvSection  — section heading + a key/value block
 *   paragraphs — section heading + body paragraphs
 *   footer     — legal text + optional signature block + optional total pill
 *
 * The class tracks a vertical cursor (y, measured from the top of the
 * page in points) and flows content top-down. When a primitive would
 * overflow the printable area it calls PdfRenderer::newPage() and reflows
 * starting at the page top.
 *
 * Visual style is inspired by the workbook mockups in /mockups: black
 * banner with letter-spaced uppercase title, light-gray (0.95) header
 * cells, 0.5pt borders, Helvetica body. Times New Roman is unavailable
 * without font embedding, so Helvetica is used throughout.
 */
final class PdfDocument
{
    private const MARGIN_LEFT = 36.0;   // 0.5 in
    private const MARGIN_RIGHT = 36.0;
    private const MARGIN_TOP = 36.0;
    private const MARGIN_BOTTOM = 48.0;

    private const GRAY_FILL = 0.95;       // header / subtotal cell background
    private const BANNER_RGB = 0.0;       // pure black

    private PdfRenderer $renderer;
    private float $cursor;
    private string $bannerTitle = '';
    private int $pageNumber = 1;
    /** Drawn lazily — set by setHeader() / setBanner(), painted on each newPage(). */
    private ?array $header = null;
    private ?string $headerBannerTitle = null;

    public function __construct(?PdfRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new PdfRenderer();
        $this->cursor = self::MARGIN_TOP;
    }

    public function output(): string
    {
        return $this->renderer->output();
    }

    public function contentLeft(): float
    {
        return self::MARGIN_LEFT;
    }

    public function contentRight(): float
    {
        return PdfRenderer::PAGE_WIDTH - self::MARGIN_RIGHT;
    }

    public function contentWidth(): float
    {
        return PdfRenderer::PAGE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
    }

    public function cursor(): float
    {
        return $this->cursor;
    }

    public function advance(float $dy): void
    {
        $this->cursor += $dy;
        $this->ensureSpace(0);
    }

    public function ensureSpace(float $needed): void
    {
        $bottomLimit = PdfRenderer::PAGE_HEIGHT - self::MARGIN_BOTTOM;
        if ($this->cursor + $needed > $bottomLimit) {
            $this->newPage();
        }
    }

    private function newPage(): void
    {
        $this->renderer->newPage();
        $this->cursor = self::MARGIN_TOP;
        $this->pageNumber++;
        if ($this->headerBannerTitle !== null) {
            $this->banner($this->headerBannerTitle);
        }
    }

    // ─── MASTHEAD ───────────────────────────────────────────────────────

    /**
     * Three-column masthead: logo placeholder (left), company info
     * (center), doc-meta table (right).
     *
     * @param string[] $companyLines
     * @param array<int, array{0:string,1:string}> $docMeta
     */
    public function masthead(string $companyName, array $companyLines, array $docMeta): void
    {
        $left = $this->contentLeft();
        $right = $this->contentRight();
        $width = $this->contentWidth();

        $logoW = 70.0;
        $logoH = 80.0;
        $logoX = $left;
        $logoY = $this->cursor;

        $metaW = 200.0;
        $metaX = $right - $metaW;

        $companyX = $logoX + $logoW + 10.0;
        $companyW = $metaX - $companyX - 10.0;

        // Logo placeholder: outlined shield-shape stand-in. The real
        // logo lives as an asset and would be drawn by a JPEG-XObject in
        // a fuller PDF library; here we draw a simple bordered box with
        // the company initials.
        $this->renderer->strokeRect($logoX, $logoY, $logoW, $logoH, 1.0);
        $initials = $this->initials($companyName);
        $this->renderer->textCenter($initials, $logoX + ($logoW / 2.0), $logoY + ($logoH / 2.0) + 6.0, 24.0, PdfRenderer::FONT_BOLD);

        // Company block
        $companyCursor = $logoY + 12.0;
        $this->renderer->text($companyName, $companyX, $companyCursor, 12.0, PdfRenderer::FONT_BOLD);
        $companyCursor += 14.0;
        foreach ($companyLines as $line) {
            $this->renderer->text($line, $companyX, $companyCursor, 9.5);
            $companyCursor += 12.0;
        }

        // Document meta table
        $metaRowH = 13.0;
        $metaLabelW = 78.0;
        $metaY = $logoY;
        foreach ($docMeta as $idx => [$label, $value]) {
            $rowTop = $metaY + ($idx * $metaRowH);
            $this->renderer->fillRect($metaX, $rowTop, $metaLabelW, $metaRowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
            $this->renderer->strokeRect($metaX, $rowTop, $metaLabelW, $metaRowH);
            $this->renderer->strokeRect($metaX + $metaLabelW, $rowTop, $metaW - $metaLabelW, $metaRowH);
            $this->renderer->textRight($label, $metaX + $metaLabelW - 4.0, $rowTop + 9.0, 8.5);
            $this->renderer->text(self::truncate($value, $metaW - $metaLabelW - 8.0, 8.5), $metaX + $metaLabelW + 4.0, $rowTop + 9.0, 8.5);
        }

        $metaHeight = (count($docMeta) * $metaRowH);
        $this->cursor = $logoY + max($logoH, $metaHeight, 12.0 + (count($companyLines) * 12.0)) + 6.0;
    }

    // ─── BANNER ─────────────────────────────────────────────────────────

    /**
     * Black banner with the document title in letter-spaced uppercase
     * (left) and the page number (right). Re-emitted on every new page
     * so multi-page documents keep their identity.
     */
    public function banner(string $title): void
    {
        $this->bannerTitle = $title;
        $this->headerBannerTitle = $title;
        $h = 22.0;
        $this->ensureSpace($h);

        $left = $this->contentLeft();
        $right = $this->contentRight();
        $width = $this->contentWidth();

        $this->renderer->fillRect($left, $this->cursor, $width, $h, self::BANNER_RGB, self::BANNER_RGB, self::BANNER_RGB);

        $titleUpper = strtoupper($title);
        $titleSpaced = implode(' ', str_split($titleUpper));
        $this->renderer->text($titleSpaced, $left + 10.0, $this->cursor + 15.0, 12.0, PdfRenderer::FONT_BOLD, 1.0, 1.0, 1.0);
        $this->renderer->textRight('Page ' . $this->pageNumber, $right - 10.0, $this->cursor + 15.0, 9.0, PdfRenderer::FONT_REGULAR, 1.0, 1.0, 1.0);

        $this->cursor += $h + 4.0;
    }

    // ─── META STRIP ────────────────────────────────────────────────────

    /**
     * Three-column meta strip: customer (left), vehicle table (center),
     * totals table (right). Each column wraps independently; the strip
     * grows to whichever column is tallest.
     *
     * @param array{name:string, lines:string[]} $customer
     * @param array<int, array{0:string,1:string}>|null $vehicle
     * @param array<int, array{0:string,1:string}>|null $totals
     * @param string|null $totalsGrandLabel  Label of the row to render in bold (heavy border + bold).
     */
    public function metaStrip(array $customer, ?array $vehicle, ?array $totals, ?string $totalsGrandLabel = null): void
    {
        $left = $this->contentLeft();
        $width = $this->contentWidth();
        $top = $this->cursor;

        $custW = 180.0;
        $totalsW = $totals ? 160.0 : 0.0;
        $vehW = $width - $custW - $totalsW;

        $custX = $left;
        $vehX = $custX + $custW;
        $totalsX = $vehX + $vehW;

        // CUSTOMER BLOCK
        $custLineH = 11.0;
        $custLines = array_merge([$customer['name']], $customer['lines']);
        $custHeight = 6.0 + (count($custLines) * $custLineH) + 4.0;

        // VEHICLE BLOCK
        $rowH = 13.0;
        $vehHeight = $vehicle ? (count($vehicle) * $rowH) : 0.0;

        // TOTALS BLOCK
        $totalsHeight = $totals ? (count($totals) * $rowH) : 0.0;

        $blockHeight = max($custHeight, $vehHeight, $totalsHeight);
        $this->ensureSpace($blockHeight);
        $top = $this->cursor;

        // Draw customer
        $this->renderer->strokeRect($custX, $top, $custW, $blockHeight);
        $cursorY = $top + 12.0;
        $this->renderer->text($customer['name'], $custX + 6.0, $cursorY, 9.5, PdfRenderer::FONT_BOLD);
        $cursorY += 11.0;
        foreach ($customer['lines'] as $line) {
            $this->renderer->text(self::truncate($line, $custW - 12.0, 9.0), $custX + 6.0, $cursorY, 9.0);
            $cursorY += $custLineH;
        }

        // Draw vehicle (key/value rows)
        if ($vehicle) {
            $labelW = 60.0;
            foreach ($vehicle as $idx => [$k, $v]) {
                $y = $top + ($idx * $rowH);
                $this->renderer->fillRect($vehX, $y, $labelW, $rowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
                $this->renderer->strokeRect($vehX, $y, $labelW, $rowH);
                $this->renderer->strokeRect($vehX + $labelW, $y, $vehW - $labelW, $rowH);
                $this->renderer->textRight($k, $vehX + $labelW - 4.0, $y + 9.0, 8.5);
                $this->renderer->text(self::truncate($v, $vehW - $labelW - 8.0, 8.5), $vehX + $labelW + 4.0, $y + 9.0, 8.5);
            }
            // If the vehicle table is shorter than the customer block,
            // extend the right border down so the column lines up.
            if ($vehHeight < $blockHeight) {
                $this->renderer->strokeRect($vehX, $top + $vehHeight, $vehW, $blockHeight - $vehHeight);
            }
        }

        // Draw totals
        if ($totals) {
            $labelW = 80.0;
            $grandLabel = $totalsGrandLabel !== null ? strtolower($totalsGrandLabel) : null;
            foreach ($totals as $idx => [$k, $v]) {
                $y = $top + ($idx * $rowH);
                $isGrand = $grandLabel !== null && strtolower($k) === $grandLabel;
                $line = $isGrand ? 1.5 : 0.5;
                $bgR = $bgG = $bgB = $isGrand ? self::GRAY_FILL : self::GRAY_FILL;
                if ($isGrand) {
                    $this->renderer->fillRect($totalsX, $y, $totalsW, $rowH, $bgR, $bgG, $bgB);
                } else {
                    $this->renderer->fillRect($totalsX, $y, $labelW, $rowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
                }
                $this->renderer->strokeRect($totalsX, $y, $labelW, $rowH, $line);
                $this->renderer->strokeRect($totalsX + $labelW, $y, $totalsW - $labelW, $rowH, $line);
                $font = $isGrand ? PdfRenderer::FONT_BOLD : PdfRenderer::FONT_REGULAR;
                $this->renderer->textRight($k, $totalsX + $labelW - 4.0, $y + 9.0, 8.5, $font);
                $this->renderer->textRight(self::truncate($v, $totalsW - $labelW - 8.0, 8.5, $font), $totalsX + $totalsW - 4.0, $y + 9.0, 8.5, $font);
            }
            if ($totalsHeight < $blockHeight) {
                $this->renderer->strokeRect($totalsX, $top + $totalsHeight, $totalsW, $blockHeight - $totalsHeight);
            }
        }

        $this->cursor = $top + $blockHeight + 6.0;
    }

    // ─── DETAIL BAR ────────────────────────────────────────────────────

    /**
     * Horizontal key/value strip (Service Type | Quoted ETA, etc.).
     *
     * @param array<int, array{0:string,1:string}> $pairs
     */
    public function detailBar(array $pairs): void
    {
        if (!$pairs) {
            return;
        }
        $h = 14.0;
        $this->ensureSpace($h);
        $left = $this->contentLeft();
        $width = $this->contentWidth();
        $cellW = $width / count($pairs);

        foreach ($pairs as $idx => [$k, $v]) {
            $x = $left + ($idx * $cellW);
            $labelW = 90.0;
            $this->renderer->fillRect($x, $this->cursor, $labelW, $h, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
            $this->renderer->strokeRect($x, $this->cursor, $labelW, $h);
            $this->renderer->strokeRect($x + $labelW, $this->cursor, $cellW - $labelW, $h);
            $this->renderer->textRight($k, $x + $labelW - 4.0, $this->cursor + 10.0, 8.5);
            $this->renderer->text(self::truncate($v, $cellW - $labelW - 8.0, 8.5), $x + $labelW + 4.0, $this->cursor + 10.0, 8.5);
        }

        $this->cursor += $h + 4.0;
    }

    // ─── SECTIONS ──────────────────────────────────────────────────────

    public function sectionHeading(string $title): void
    {
        $this->ensureSpace(22.0);
        $this->cursor += 4.0;
        $this->renderer->text(strtoupper($title), $this->contentLeft(), $this->cursor + 9.0, 9.5, PdfRenderer::FONT_BOLD);
        $this->cursor += 12.0;
        $this->renderer->hLine($this->contentLeft(), $this->cursor, $this->contentWidth(), 0.7);
        $this->cursor += 4.0;
    }

    /**
     * Paragraphs section. Each paragraph wraps to the content width.
     *
     * @param string[] $paragraphs
     */
    public function paragraphs(array $paragraphs, float $size = 9.5): void
    {
        $maxW = $this->contentWidth();
        foreach ($paragraphs as $p) {
            $lines = PdfRenderer::wrap($p, $maxW, $size);
            foreach ($lines as $line) {
                $this->ensureSpace(13.0);
                $this->renderer->text($line, $this->contentLeft(), $this->cursor + 9.0, $size);
                $this->cursor += 12.0;
            }
            $this->cursor += 4.0;
        }
    }

    /**
     * Bulleted list inside a section.
     *
     * @param string[] $items
     */
    public function bullets(array $items, float $size = 9.5): void
    {
        $maxW = $this->contentWidth() - 16.0;
        foreach ($items as $item) {
            $lines = PdfRenderer::wrap($item, $maxW, $size);
            $first = true;
            foreach ($lines as $line) {
                $this->ensureSpace(13.0);
                if ($first) {
                    $this->renderer->text('•', $this->contentLeft() + 2.0, $this->cursor + 9.0, $size);
                    $first = false;
                }
                $this->renderer->text($line, $this->contentLeft() + 16.0, $this->cursor + 9.0, $size);
                $this->cursor += 12.0;
            }
        }
        $this->cursor += 2.0;
    }

    /**
     * Two-column key/value table.
     *
     * @param array<int, array{0:string,1:string}> $rows
     */
    public function kvTable(array $rows, ?float $labelWidth = null): void
    {
        if (!$rows) {
            return;
        }
        $rowH = 13.0;
        $left = $this->contentLeft();
        $width = $this->contentWidth();
        $labelW = $labelWidth ?? 140.0;

        foreach ($rows as [$k, $v]) {
            $this->ensureSpace($rowH);
            $this->renderer->fillRect($left, $this->cursor, $labelW, $rowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
            $this->renderer->strokeRect($left, $this->cursor, $labelW, $rowH);
            $this->renderer->strokeRect($left + $labelW, $this->cursor, $width - $labelW, $rowH);
            $this->renderer->textRight($k, $left + $labelW - 4.0, $this->cursor + 9.0, 9.0);
            $this->renderer->text(self::truncate($v, $width - $labelW - 8.0, 9.0), $left + $labelW + 4.0, $this->cursor + 9.0, 9.0);
            $this->cursor += $rowH;
        }
        $this->cursor += 4.0;
    }

    // ─── CHARGES TABLE ─────────────────────────────────────────────────

    /**
     * Itemised charges table — the workhorse of every financial document.
     *
     * @param array<int, array{label:string, align?:string, width?:float}> $columns
     * @param array<int, string[]> $rows
     * @param array<int, array{label:string, amount:string}> $subtotalRows  Gray rows (Subtotal, Tax, etc.)
     * @param array{label:string, amount:string}|null $totalRow   Bold + thick-bordered row (TOTAL).
     */
    public function chargesTable(array $columns, array $rows, array $subtotalRows = [], ?array $totalRow = null): void
    {
        $left = $this->contentLeft();
        $width = $this->contentWidth();
        $headerH = 16.0;
        $rowH = 14.0;

        // Compute column widths: explicit widths use the supplied value,
        // remaining width is divided equally among "auto" columns.
        $explicit = 0.0;
        $autoCols = 0;
        foreach ($columns as $col) {
            if (!empty($col['width'])) {
                $explicit += (float) $col['width'];
            } else {
                $autoCols++;
            }
        }
        $autoW = $autoCols > 0 ? max(40.0, ($width - $explicit) / $autoCols) : 0.0;
        $colWidths = [];
        foreach ($columns as $col) {
            $colWidths[] = !empty($col['width']) ? (float) $col['width'] : $autoW;
        }

        // Header
        $this->ensureSpace($headerH + $rowH);
        $this->renderer->fillRect($left, $this->cursor, $width, $headerH, 0.0, 0.0, 0.0);
        $x = $left;
        foreach ($columns as $idx => $col) {
            $w = $colWidths[$idx];
            $align = $col['align'] ?? 'left';
            $label = (string) $col['label'];
            if ($align === 'right') {
                $this->renderer->textRight($label, $x + $w - 6.0, $this->cursor + 11.0, 9.0, PdfRenderer::FONT_BOLD, 1.0, 1.0, 1.0);
            } else {
                $this->renderer->text($label, $x + 6.0, $this->cursor + 11.0, 9.0, PdfRenderer::FONT_BOLD, 1.0, 1.0, 1.0);
            }
            $x += $w;
        }
        $this->cursor += $headerH;

        // Body rows. Long descriptions wrap inside their cell, and the
        // row height grows to match the tallest cell.
        foreach ($rows as $row) {
            $cellLines = [];
            $maxLines = 1;
            foreach ($columns as $idx => $col) {
                $w = $colWidths[$idx];
                $cellText = (string) ($row[$idx] ?? '');
                $lines = PdfRenderer::wrap($cellText, $w - 12.0, 9.0);
                $cellLines[$idx] = $lines;
                if (count($lines) > $maxLines) {
                    $maxLines = count($lines);
                }
            }
            $thisRowH = max($rowH, 4.0 + ($maxLines * 11.0));
            $this->ensureSpace($thisRowH);

            $x = $left;
            foreach ($columns as $idx => $col) {
                $w = $colWidths[$idx];
                $this->renderer->strokeRect($x, $this->cursor, $w, $thisRowH);
                $align = $col['align'] ?? 'left';
                foreach ($cellLines[$idx] as $i => $line) {
                    $ty = $this->cursor + 11.0 + ($i * 11.0);
                    if ($align === 'right') {
                        $this->renderer->textRight($line, $x + $w - 6.0, $ty, 9.0);
                    } else {
                        $this->renderer->text($line, $x + 6.0, $ty, 9.0);
                    }
                }
                $x += $w;
            }
            $this->cursor += $thisRowH;
        }

        // Subtotal rows: spans the description columns with gray bg.
        $amountColIdx = count($columns) - 1;
        $amountColW = $colWidths[$amountColIdx];
        $descSpanW = $width - $amountColW;
        foreach ($subtotalRows as $sub) {
            $this->ensureSpace($rowH);
            $this->renderer->fillRect($left, $this->cursor, $width, $rowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
            $this->renderer->strokeRect($left, $this->cursor, $descSpanW, $rowH);
            $this->renderer->strokeRect($left + $descSpanW, $this->cursor, $amountColW, $rowH);
            $this->renderer->text((string) $sub['label'], $left + 6.0, $this->cursor + 10.0, 9.0);
            $this->renderer->textRight((string) $sub['amount'], $left + $width - 6.0, $this->cursor + 10.0, 9.0);
            $this->cursor += $rowH;
        }

        // Total row: bold + heavier border.
        if ($totalRow) {
            $thisRowH = $rowH + 2.0;
            $this->ensureSpace($thisRowH);
            $this->renderer->fillRect($left, $this->cursor, $width, $thisRowH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
            $this->renderer->strokeRect($left, $this->cursor, $descSpanW, $thisRowH, 1.5);
            $this->renderer->strokeRect($left + $descSpanW, $this->cursor, $amountColW, $thisRowH, 1.5);
            $this->renderer->text(strtoupper((string) $totalRow['label']), $left + 6.0, $this->cursor + 11.0, 9.5, PdfRenderer::FONT_BOLD);
            $this->renderer->textRight((string) $totalRow['amount'], $left + $width - 6.0, $this->cursor + 11.0, 9.5, PdfRenderer::FONT_BOLD);
            $this->cursor += $thisRowH;
        }
        $this->cursor += 6.0;
    }

    // ─── FOOTER ────────────────────────────────────────────────────────

    /**
     * Footer with optional legal text, signature lines, and total pill.
     *
     * @param string|null $legal             Block of small-print text on the left.
     * @param array{showCustomer:bool, showTechnician:bool, customerLabel?:string, techLabel?:string}|null $signatures
     * @param array{label:string, amount:string}|null $pill
     */
    public function footer(?string $legal, ?array $signatures, ?array $pill): void
    {
        if ($legal === null && !$signatures && !$pill) {
            return;
        }

        $this->cursor += 6.0;
        $left = $this->contentLeft();
        $width = $this->contentWidth();
        $right = $this->contentRight();

        $signatureW = ($signatures || $pill) ? 230.0 : 0.0;
        $legalW = $signatureW > 0 ? $width - $signatureW - 18.0 : $width;

        // Compute heights so we can reserve space, advance to the right
        // spot, and align the two columns at the bottom.
        $legalLines = $legal !== null ? PdfRenderer::wrap($legal, $legalW, 7.5) : [];
        $legalH = count($legalLines) * 10.0;

        $sigH = 0.0;
        if ($signatures) {
            if (!empty($signatures['showCustomer'])) {
                $sigH += 36.0;
            }
            if (!empty($signatures['showTechnician'])) {
                $sigH += 36.0;
            }
        }
        if ($pill) {
            $sigH += 24.0;
        }

        $blockH = max($legalH, $sigH);
        $this->ensureSpace($blockH + 6.0);

        $top = $this->cursor;

        // Legal column (left)
        $ly = $top;
        foreach ($legalLines as $line) {
            $this->renderer->text($line, $left, $ly + 8.0, 7.5);
            $ly += 10.0;
        }

        // Signature column (right)
        if ($signatureW > 0) {
            $sx = $right - $signatureW;
            $sy = $top;

            if (!empty($signatures['showCustomer'])) {
                $sy += 22.0;
                $this->renderer->hLine($sx, $sy, $signatureW, 0.7);
                $this->renderer->text((string) ($signatures['customerLabel'] ?? 'Customer Signature'), $sx, $sy + 10.0, 7.5);
                $sy += 14.0;
            }
            if (!empty($signatures['showTechnician'])) {
                $sy += 22.0;
                $this->renderer->hLine($sx, $sy, $signatureW, 0.7);
                $this->renderer->text((string) ($signatures['techLabel'] ?? 'Technician Signature'), $sx, $sy + 10.0, 7.5);
                $sy += 14.0;
            }

            if ($pill) {
                $sy += 8.0;
                $pillH = 18.0;
                $this->renderer->fillRect($sx, $sy, $signatureW, $pillH, self::GRAY_FILL, self::GRAY_FILL, self::GRAY_FILL);
                $this->renderer->strokeRect($sx, $sy, $signatureW, $pillH, 1.5);
                $this->renderer->text((string) $pill['label'], $sx + 8.0, $sy + 12.0, 10.0, PdfRenderer::FONT_BOLD);
                $this->renderer->textRight((string) $pill['amount'], $sx + $signatureW - 8.0, $sy + 12.0, 10.0, PdfRenderer::FONT_BOLD);
                $sy += $pillH;
            }
        }

        $this->cursor = $top + $blockH + 6.0;
    }

    // ─── Utility ───────────────────────────────────────────────────────

    public static function truncate(string $text, float $maxWidth, float $size, string $font = PdfRenderer::FONT_REGULAR): string
    {
        if ($text === '') {
            return '';
        }
        if (PdfRenderer::measure($text, $size, $font) <= $maxWidth) {
            return $text;
        }
        $ellipsis = '...';
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        foreach ($chars as $char) {
            if (PdfRenderer::measure($out . $char . $ellipsis, $size, $font) > $maxWidth) {
                return $out . $ellipsis;
            }
            $out .= $char;
        }
        return $out;
    }

    private function initials(string $name): string
    {
        $tokens = preg_split('/\s+/', trim($name)) ?: [];
        $out = '';
        foreach ($tokens as $token) {
            $first = mb_substr($token, 0, 1);
            if (preg_match('/[A-Za-z]/', $first)) {
                $out .= strtoupper($first);
            }
            if (mb_strlen($out) >= 2) {
                break;
            }
        }
        return $out !== '' ? $out : 'CO';
    }
}
