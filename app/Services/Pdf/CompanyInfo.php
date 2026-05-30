<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Core\Env;

/**
 * Company identity used in the PDF masthead and footer.
 *
 * Sourced from environment variables so deployments can rebrand without
 * touching templates. The defaults match the production tenant (White
 * Knight Roadside, LLC) so a fresh checkout still produces a useful PDF.
 */
final class CompanyInfo
{
    public string $name;
    /** @var string[] */
    public array $addressLines;
    public string $phone;
    public string $email;
    public string $legalName;

    public function __construct(
        string $name,
        array $addressLines,
        string $phone,
        string $email,
        string $legalName
    ) {
        $this->name = $name;
        $this->addressLines = array_values(array_filter($addressLines, static fn ($l) => trim((string) $l) !== ''));
        $this->phone = $phone;
        $this->email = $email;
        $this->legalName = $legalName;
    }

    public static function fromEnv(): self
    {
        $name = (string) Env::get('COMPANY_NAME', 'White Knight Roadside, LLC');
        $addr1 = (string) Env::get('COMPANY_ADDRESS_LINE_1', '7216 SW 204th Ave #3');
        $addr2 = (string) Env::get('COMPANY_ADDRESS_LINE_2', '');
        $cityStateZip = (string) Env::get('COMPANY_CITY_STATE_ZIP', 'Beaverton, OR 97007');
        $phone = (string) Env::get('COMPANY_PHONE', '(503) 764-3154');
        $email = (string) Env::get('COMPANY_EMAIL', 'admin@wkrllc.com');
        $legal = (string) Env::get('COMPANY_LEGAL_NAME', $name);

        return new self(
            $name,
            [$addr1, $addr2, $cityStateZip],
            $phone,
            $email,
            $legal
        );
    }
}
