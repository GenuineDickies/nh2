<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use RuntimeException;

/**
 * Thrown when a PDF view model is asked to render a document that's
 * missing required data (no customer, no line items, missing VIN
 * without the no-vehicle flag, etc.). The controller catches this and
 * routes the user back to the source record with the message attached
 * as a flash error.
 */
final class PdfValidationException extends RuntimeException
{
    /** @var string[] */
    public array $errors;

    /** @param string[] $errors */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(
            $errors
                ? 'Cannot generate PDF: ' . implode('; ', $errors)
                : 'Cannot generate PDF: required data is missing'
        );
    }
}
