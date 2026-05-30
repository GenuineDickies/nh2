<?php

namespace App\Services;

use App\Core\Env;

/**
 * Normalizes uploaded images into an AI-ready JPEG before they are sent to
 * OpenAI. Handles:
 *   - WebP / HEIC / TIFF / BMP / GIF / etc. → JPEG (sidestepping vision
 *     decoder quirks we have actually seen in production)
 *   - EXIF orientation (phones save sideways; the AI sees them sideways)
 *   - Resize so the long edge is <= 2000 px (small thermal-printer text
 *     stays legible without bloating the image-token budget)
 *   - Validation that the staged file is actually a real image with
 *     reasonable dimensions — catches malformed sources before we spend
 *     money on an OpenAI call that will fail
 *
 * PDFs are passed through unchanged because OpenAI handles them natively.
 *
 * Drivers are tried in order: Imagick → GD → ffmpeg(exec) → passthrough.
 * Whichever driver is available wins; the rest exist so this works locally
 * (ffmpeg only) AND on a typical shared host (GD almost always).
 */
final class ImageStagingService
{
    public const MAX_LONG_EDGE = 2000;
    public const MIN_DIMENSION = 200;
    public const MAX_OUTPUT_BYTES = 15 * 1024 * 1024;
    public const JPEG_QUALITY = 85;

    private const IMAGE_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'image/heic', 'image/heif', 'image/tiff', 'image/bmp',
    ];

    /**
     * Stage a source upload into an AI-ready file.
     *
     * Returns:
     *   [
     *     'staged_path' => string|null absolute path on disk (null = no-op, use original)
     *     'staged_mime' => string|null
     *     'staged_size' => int|null
     *     'driver'      => string  imagick|gd|ffmpeg|passthrough|failed
     *     'warnings'    => string[]
     *     'error'       => ?string fatal staging error (null = ok)
     *   ]
     */
    public function stage(string $sourcePath, string $sourceMime, string $stagedDirectory): array
    {
        if (!is_file($sourcePath)) {
            return $this->failure('Source file does not exist for staging.');
        }

        // PDFs pass through — OpenAI handles them natively.
        if ($sourceMime === 'application/pdf') {
            return [
                'staged_path' => null,
                'staged_mime' => null,
                'staged_size' => null,
                'driver' => 'passthrough',
                'warnings' => [],
                'error' => null,
            ];
        }

        if (!in_array($sourceMime, self::IMAGE_MIMES, true)) {
            return $this->failure("Unsupported MIME type for staging: {$sourceMime}");
        }

        if (!is_dir($stagedDirectory) && !mkdir($stagedDirectory, 0775, true) && !is_dir($stagedDirectory)) {
            return $this->failure('Could not create staging directory.');
        }

        $stagedPath = $stagedDirectory . '/' . bin2hex(random_bytes(16)) . '.jpg';

        $drivers = $this->availableDrivers();
        if (!$drivers) {
            return [
                'staged_path' => null,
                'staged_mime' => null,
                'staged_size' => null,
                'driver' => 'passthrough',
                'warnings' => ['No image driver available (Imagick, GD, or ffmpeg). Original file sent to AI as-is — quality may suffer for very large or rotated images.'],
                'error' => null,
            ];
        }

        $lastError = null;
        $accumulatedWarnings = [];
        foreach ($drivers as $driver) {
            $result = $this->{'stageWith' . ucfirst($driver)}($sourcePath, $sourceMime, $stagedPath);
            if ($result['error'] === null) {
                $validation = $this->validateStaged($stagedPath);
                if ($validation['error']) {
                    @unlink($stagedPath);
                    $lastError = $validation['error'];
                    $accumulatedWarnings[] = "{$driver} produced invalid output: " . $validation['error'];
                    continue;
                }
                return [
                    'staged_path' => $stagedPath,
                    'staged_mime' => 'image/jpeg',
                    'staged_size' => filesize($stagedPath) ?: 0,
                    'driver' => $driver,
                    'warnings' => array_merge($accumulatedWarnings, $result['warnings'] ?? [], $validation['warnings']),
                    'error' => null,
                ];
            }
            $lastError = $result['error'];
            $accumulatedWarnings[] = "{$driver} driver failed: " . $result['error'];
        }

        return [
            'staged_path' => null,
            'staged_mime' => null,
            'staged_size' => null,
            'driver' => 'failed',
            'warnings' => $accumulatedWarnings,
            'error' => $lastError ?? 'All staging drivers failed.',
        ];
    }

    private function availableDrivers(): array
    {
        $drivers = [];
        if (extension_loaded('imagick')) {
            $drivers[] = 'imagick';
        }
        if (extension_loaded('gd')) {
            $drivers[] = 'gd';
        }
        if (function_exists('exec') && $this->ffmpegPath() !== null) {
            $drivers[] = 'ffmpeg';
        }
        return $drivers;
    }

    // ----- Drivers --------------------------------------------------------

    private function stageWithImagick(string $sourcePath, string $sourceMime, string $stagedPath): array
    {
        try {
            $im = new \Imagick();
            $im->readImage($sourcePath);
            // Some formats (HEIC, animated WebP) have multiple frames; keep first.
            $im->setIteratorIndex(0);
            $im = $im->coalesceImages();
            $im->setIteratorIndex(0);

            $this->applyImagickOrientation($im);

            $width = $im->getImageWidth();
            $height = $im->getImageHeight();
            if ($width <= 0 || $height <= 0) {
                return ['error' => 'Imagick reported zero dimensions.', 'warnings' => []];
            }

            [$targetW, $targetH] = $this->fitWithin($width, $height, self::MAX_LONG_EDGE);
            if ($targetW !== $width || $targetH !== $height) {
                $im->resizeImage($targetW, $targetH, \Imagick::FILTER_LANCZOS, 1);
            }

            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(self::JPEG_QUALITY);
            $im->stripImage();
            $im->writeImage($stagedPath);
            $im->clear();
            return ['error' => null, 'warnings' => []];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'warnings' => []];
        }
    }

    private function stageWithGd(string $sourcePath, string $sourceMime, string $stagedPath): array
    {
        try {
            $img = $this->readWithGd($sourcePath, $sourceMime);
            if (!$img) {
                return ['error' => 'GD could not decode the source.', 'warnings' => []];
            }

            $width = imagesx($img);
            $height = imagesy($img);
            if ($width <= 0 || $height <= 0) {
                imagedestroy($img);
                return ['error' => 'GD reported zero dimensions.', 'warnings' => []];
            }

            $rotated = $this->applyGdOrientation($img, $sourcePath);
            if ($rotated !== $img) {
                imagedestroy($img);
                $img = $rotated;
                $width = imagesx($img);
                $height = imagesy($img);
            }

            [$targetW, $targetH] = $this->fitWithin($width, $height, self::MAX_LONG_EDGE);
            if ($targetW !== $width || $targetH !== $height) {
                $resized = imagecreatetruecolor($targetW, $targetH);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $targetW, $targetH, $width, $height);
                imagedestroy($img);
                $img = $resized;
            }

            $ok = imagejpeg($img, $stagedPath, self::JPEG_QUALITY);
            imagedestroy($img);

            if (!$ok) {
                return ['error' => 'GD could not write JPEG output.', 'warnings' => []];
            }
            return ['error' => null, 'warnings' => []];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'warnings' => []];
        }
    }

    private function stageWithFfmpeg(string $sourcePath, string $sourceMime, string $stagedPath): array
    {
        $ffmpeg = $this->ffmpegPath();
        if ($ffmpeg === null) {
            return ['error' => 'ffmpeg binary not found.', 'warnings' => []];
        }

        // scale='if(gt(a,1),min(MAX,iw),-2)':'if(gt(a,1),-2,min(MAX,ih))'
        // a is aspect ratio (w/h). If wider than tall, cap width; else cap height.
        // -2 keeps aspect and forces even (required by JPEG/yuvj420p).
        $max = self::MAX_LONG_EDGE;
        $filter = sprintf(
            "scale='if(gt(a\\,1)\\,min(%d\\,iw)\\,-2)':'if(gt(a\\,1)\\,-2\\,min(%d\\,ih))'",
            $max,
            $max
        );

        $cmd = sprintf(
            '%s -y -hide_banner -loglevel error -i %s -vf %s -frames:v 1 -q:v 3 -pix_fmt yuvj420p %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($sourcePath),
            escapeshellarg($filter),
            escapeshellarg($stagedPath)
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !is_file($stagedPath) || filesize($stagedPath) === 0) {
            return [
                'error' => 'ffmpeg exit ' . $exitCode . ': ' . trim(implode("\n", $output)),
                'warnings' => [],
            ];
        }

        return ['error' => null, 'warnings' => []];
    }

    // ----- Validation -----------------------------------------------------

    private function validateStaged(string $stagedPath): array
    {
        if (!is_file($stagedPath) || filesize($stagedPath) === 0) {
            return ['error' => 'Staged file is missing or empty.', 'warnings' => []];
        }
        if (filesize($stagedPath) > self::MAX_OUTPUT_BYTES) {
            return ['error' => 'Staged file exceeds size limit after conversion.', 'warnings' => []];
        }

        $info = @getimagesize($stagedPath);
        if (!is_array($info)) {
            return ['error' => 'Staged file could not be re-read as an image.', 'warnings' => []];
        }
        [$width, $height] = $info;
        if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
            return [
                'error' => sprintf(
                    'Staged image is too small (%dx%d). The source file is likely malformed or a thumbnail.',
                    $width,
                    $height
                ),
                'warnings' => [],
            ];
        }

        $warnings = [];
        if ($width < 800 || $height < 800) {
            $warnings[] = sprintf(
                'Staged image is low resolution (%dx%d). Small text may not be readable.',
                $width,
                $height
            );
        }

        return ['error' => null, 'warnings' => $warnings];
    }

    // ----- GD helpers -----------------------------------------------------

    private function readWithGd(string $path, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/gif' => @imagecreatefromgif($path),
            'image/bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            default => false,
        };
    }

    private function applyGdOrientation(\GdImage $img, string $sourcePath): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($sourcePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        return match ($orientation) {
            3 => imagerotate($img, 180, 0) ?: $img,
            6 => imagerotate($img, -90, 0) ?: $img,
            8 => imagerotate($img, 90, 0) ?: $img,
            default => $img,
        };
    }

    // ----- Imagick helpers ------------------------------------------------

    private function applyImagickOrientation(\Imagick $im): void
    {
        $orientation = $im->getImageOrientation();
        switch ($orientation) {
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $im->rotateImage('#000', 180);
                break;
            case \Imagick::ORIENTATION_RIGHTTOP:
                $im->rotateImage('#000', 90);
                break;
            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $im->rotateImage('#000', -90);
                break;
        }
        $im->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }

    // ----- Shared helpers -------------------------------------------------

    /**
     * Return (newW, newH) so the long edge equals $maxLong and aspect ratio
     * is preserved. Never upscales — if the source already fits, returns the
     * original dimensions.
     */
    private function fitWithin(int $width, int $height, int $maxLong): array
    {
        $longest = max($width, $height);
        if ($longest <= $maxLong) {
            return [$width, $height];
        }
        $scale = $maxLong / $longest;
        return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
    }

    private function ffmpegPath(): ?string
    {
        $explicit = (string) Env::get('FFMPEG_PATH', '');
        if ($explicit !== '' && is_file($explicit)) {
            return $explicit;
        }
        // PATH lookup — works on Windows and POSIX shells.
        $isWindows = stripos(PHP_OS_FAMILY, 'win') === 0;
        $cmd = $isWindows ? 'where ffmpeg 2>NUL' : 'command -v ffmpeg 2>/dev/null';
        $result = @shell_exec($cmd);
        if (!is_string($result)) {
            return null;
        }
        $first = trim(strtok($result, "\n") ?: '');
        return $first !== '' && is_file($first) ? $first : null;
    }

    private function failure(string $message): array
    {
        return [
            'staged_path' => null,
            'staged_mime' => null,
            'staged_size' => null,
            'driver' => 'failed',
            'warnings' => [],
            'error' => $message,
        ];
    }
}
