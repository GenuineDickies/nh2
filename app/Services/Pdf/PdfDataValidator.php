<?php

declare(strict_types=1);

namespace App\Services\Pdf;

/**
 * Lightweight required-field guard for view models.
 *
 * Each view model calls these methods during build(). On any miss the
 * collected errors are thrown together as a single PdfValidationException
 * — the controller surfaces all of them at once instead of bouncing the
 * user back per-error.
 */
final class PdfDataValidator
{
    /** @var string[] */
    private array $errors = [];

    public function require(?string $value, string $fieldDescription): void
    {
        if ($value === null || trim($value) === '') {
            $this->errors[] = $fieldDescription . ' is required';
        }
    }

    public function requireId(mixed $value, string $fieldDescription): void
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            $this->errors[] = $fieldDescription . ' is required';
        }
    }

    public function requirePositiveAmount(mixed $value, string $fieldDescription): void
    {
        if (!is_numeric($value) || (float) $value <= 0) {
            $this->errors[] = $fieldDescription . ' must be greater than zero';
        }
    }

    public function requireNonEmpty(array $value, string $fieldDescription): void
    {
        if (!$value) {
            $this->errors[] = $fieldDescription . ' must have at least one entry';
        }
    }

    public function add(string $message): void
    {
        $this->errors[] = $message;
    }

    /** @return string[] */
    public function errors(): array
    {
        return $this->errors;
    }

    public function failIfAny(): void
    {
        if ($this->errors) {
            throw new PdfValidationException($this->errors);
        }
    }
}
