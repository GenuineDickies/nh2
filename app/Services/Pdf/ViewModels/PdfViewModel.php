<?php

declare(strict_types=1);

namespace App\Services\Pdf\ViewModels;

use App\Services\Pdf\CompanyInfo;
use App\Services\Pdf\PdfDocument;

/**
 * Base class for per-document PDF view models.
 *
 * A view model is responsible for:
 *  1. Loading the database record(s) for the document.
 *  2. Forcing a recalculation of any stored totals/balances.
 *  3. Validating required data via PdfDataValidator.
 *  4. Painting the document onto a PdfDocument via render().
 *
 * Subclasses MUST NOT hard-code customer data, totals, dates, document
 * numbers, or other facts that should come from the database. Static
 * values are limited to the company identity (CompanyInfo, env-driven)
 * and the approved boilerplate paragraphs returned by the doc-type
 * specific helpers below.
 */
abstract class PdfViewModel
{
    protected CompanyInfo $company;

    public function __construct(?CompanyInfo $company = null)
    {
        $this->company = $company ?? CompanyInfo::fromEnv();
    }

    /**
     * Title shown both in the PDF banner and stored on the
     * file_attachment / generated_documents row. The substring before
     * " — " is the banner title; the rest is the document number.
     */
    abstract public function title(): string;

    /**
     * Caption stored on the file attachment row (visible in document
     * lists, audit log payloads, etc.).
     */
    abstract public function fileCaption(): string;

    /**
     * Validate inputs and paint the document. Implementations should
     * raise via PdfDataValidator::failIfAny() before painting anything.
     */
    abstract public function render(PdfDocument $document): void;

    /**
     * Convenience masthead: company block on the left, doc-meta on the right.
     *
     * @param array<int, array{0:string,1:string}> $docMeta
     */
    protected function paintMasthead(PdfDocument $document, array $docMeta): void
    {
        $companyLines = $this->company->addressLines;
        if ($this->company->phone !== '') {
            $companyLines[] = 'Phone: ' . $this->company->phone;
        }
        if ($this->company->email !== '') {
            $companyLines[] = 'Email: ' . $this->company->email;
        }
        $document->masthead($this->company->name, $companyLines, $docMeta);
    }

    protected function formatVehicle(array $record): string
    {
        $parts = [
            $record['year'] ?? '',
            $record['make'] ?? '',
            $record['model'] ?? '',
            $record['color'] ?? '',
        ];
        $clean = array_values(array_filter(array_map('trim', array_map('strval', $parts)), static fn ($p) => $p !== ''));
        return implode(' ', $clean);
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    protected function vehicleTable(array $record): array
    {
        $rows = [];
        if (!empty($record['year']) || !empty($record['make'])) {
            $rows[] = ['Year', (string) ($record['year'] ?? '')];
            $rows[] = ['Make', (string) ($record['make'] ?? '')];
        }
        if (!empty($record['model'])) {
            $rows[] = ['Model', (string) $record['model']];
        }
        if (!empty($record['color'])) {
            $rows[] = ['Color', (string) $record['color']];
        }
        if (!empty($record['vin'])) {
            $rows[] = ['VIN', (string) $record['vin']];
        }
        return $rows;
    }

    /**
     * Build a customer block from a joined row that has first_name /
     * last_name / phone / email columns.
     *
     * @return array{name:string, lines:string[]}
     */
    protected function customerBlock(array $record, ?array $location = null): array
    {
        $name = trim((($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')));
        if ($name === '') {
            $name = 'Customer';
        }
        $lines = [];
        if (!empty($record['phone'])) {
            $lines[] = 'Phone: ' . $record['phone'];
        }
        if (!empty($record['email'])) {
            $lines[] = (string) $record['email'];
        }
        if ($location) {
            $lines[] = '';
            $lines[] = 'Service location:';
            if (!empty($location['address_line_1'])) {
                $lines[] = (string) $location['address_line_1'];
            }
            $city = trim((string) ($location['city'] ?? ''));
            $state = trim((string) ($location['state'] ?? ''));
            $zip = trim((string) ($location['postal_code'] ?? ''));
            $line2 = trim(trim($city . ($state ? ', ' . $state : '')) . ' ' . $zip);
            if ($line2 !== '') {
                $lines[] = $line2;
            }
        }
        return ['name' => $name, 'lines' => $lines];
    }
}
