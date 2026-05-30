<?php

namespace App\Services;

use App\Core\Env;
use App\Models\DocumentIntake;
use App\Models\DocumentExtractionLineItem;
use RuntimeException;

/**
 * Wraps OpenAI document classification and JSON extraction.
 *
 * The rest of the app must call this service rather than touching OpenAI directly,
 * so a future swap (provider, model, prompt) only requires changes here.
 */
final class OpenAiDocumentExtractionService
{
    private const ENDPOINT = 'https://api.openai.com/v1/responses';
    private const DEFAULT_MODEL = 'gpt-4o-mini';
    private const TIMEOUT_SECONDS = 90;

    /**
     * Per-1M-token USD prices, used for the cost estimate shown to the
     * operator. Defaults are the published rates for the listed models;
     * unknown models fall through to a conservative gpt-4o rate so we
     * never display "$0" for paid usage.
     */
    private const TOKEN_PRICES_PER_MILLION = [
        'gpt-4o-mini'      => ['input' => 0.150, 'output' => 0.600],
        'gpt-4o'           => ['input' => 2.500, 'output' => 10.000],
        'gpt-4.1-mini'     => ['input' => 0.400, 'output' => 1.600],
        'gpt-4.1'          => ['input' => 2.000, 'output' => 8.000],
        'gpt-4.1-nano'     => ['input' => 0.100, 'output' => 0.400],
    ];

    public function isEnabled(): bool
    {
        $flag = (string) Env::get('OPENAI_DOCUMENT_EXTRACTION_ENABLED', 'true');
        $bool = filter_var($flag, FILTER_VALIDATE_BOOLEAN);
        if (!$bool) {
            return false;
        }

        return (string) Env::get('OPENAI_API_KEY', '') !== '';
    }

    public function modelName(): string
    {
        $model = (string) Env::get('OPENAI_MODEL', '');
        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    /**
     * Send the uploaded file to OpenAI and return:
     *   [
     *     'raw'        => string raw response JSON (or ''),
     *     'normalized' => array normalized extraction shape,
     *     'warnings'   => string[],
     *     'error'      => ?string,
     *     'model'      => string,
     *   ]
     */
    public function extract(string $absoluteFilePath, string $mimeType): array
    {
        $model = $this->modelName();

        if (!$this->isEnabled()) {
            return [
                'raw' => '',
                'normalized' => $this->emptyNormalized(),
                'warnings' => ['OpenAI extraction is disabled or API key is missing. Document was uploaded but not classified.'],
                'error' => null,
                'model' => $model,
                'usage' => [
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'total_tokens' => 0,
                    'cost_hundredths_cent' => 0,
                ],
            ];
        }

        if (!is_file($absoluteFilePath)) {
            return $this->failure($model, 'Uploaded file not found on disk.');
        }

        $base64 = base64_encode((string) file_get_contents($absoluteFilePath));
        if ($base64 === '') {
            return $this->failure($model, 'Uploaded file is empty or unreadable.');
        }

        $userContent = [
            [
                'type' => 'input_text',
                'text' => "Analyze this document and return JSON matching the schema. Document filename: "
                    . basename($absoluteFilePath),
            ],
        ];

        if (str_starts_with($mimeType, 'image/')) {
            $userContent[] = [
                'type' => 'input_image',
                'image_url' => 'data:' . $mimeType . ';base64,' . $base64,
            ];
        } elseif ($mimeType === 'application/pdf') {
            $userContent[] = [
                'type' => 'input_file',
                'filename' => basename($absoluteFilePath),
                'file_data' => 'data:application/pdf;base64,' . $base64,
            ];
        } else {
            return $this->failure($model, "Unsupported MIME type for extraction: {$mimeType}.");
        }

        $payload = [
            'model' => $model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $this->systemPrompt(),
                    ]],
                ],
                [
                    'role' => 'user',
                    'content' => $userContent,
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_object',
                ],
            ],
        ];

        [$body, $httpCode, $error] = $this->postJson($payload);

        if ($error !== null) {
            return $this->failure($model, 'OpenAI request failed: ' . $error, $body);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return $this->failure($model, "OpenAI returned HTTP {$httpCode}.", $body);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->failure($model, 'OpenAI response was not valid JSON.', $body);
        }

        $textPayload = $this->extractTextPayload($decoded);
        if ($textPayload === null) {
            return $this->failure($model, 'OpenAI response did not contain text output.', $body);
        }

        $parsed = json_decode($textPayload, true);
        if (!is_array($parsed)) {
            return $this->failure($model, 'OpenAI text payload was not valid JSON.', $body);
        }

        $normalized = $this->normalize($parsed);
        $warnings = is_array($parsed['warnings'] ?? null)
            ? array_map('strval', $parsed['warnings'])
            : [];

        $usage = $this->extractUsage($decoded, $model);

        return [
            'raw' => $body,
            'normalized' => $normalized,
            'warnings' => $warnings,
            'error' => null,
            'model' => $model,
            'usage' => $usage,
        ];
    }

    /**
     * Pull token counts from the response and price them so we can show the
     * operator what each call cost. Returns zeros when usage isn't present,
     * never throws — cost tracking should never block extraction.
     */
    private function extractUsage(array $decoded, string $model): array
    {
        $usage = $decoded['usage'] ?? [];
        $input = (int) ($usage['input_tokens'] ?? 0);
        $output = (int) ($usage['output_tokens'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? ($input + $output));

        $price = self::TOKEN_PRICES_PER_MILLION[$model]
            ?? self::TOKEN_PRICES_PER_MILLION['gpt-4o'];

        // Cost in USD, then convert to hundredths-of-a-cent (integer) so we
        // can sum without float drift. 1 cent = 100 hundredths.
        $usd = ($input / 1_000_000 * $price['input']) + ($output / 1_000_000 * $price['output']);
        $hundredthsOfCent = (int) round($usd * 10_000);

        return [
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $total,
            'cost_hundredths_cent' => $hundredthsOfCent,
        ];
    }

    public function systemPrompt(): string
    {
        $types = implode(', ', DocumentIntake::DOCUMENT_TYPES);
        $categories = implode(', ', DocumentExtractionLineItem::CATEGORIES);

        return <<<PROMPT
You are a bookkeeping assistant for a one-person roadside assistance and mobile mechanic business owned by the user uploading this document. The user is processing their OWN business paperwork — receipts they paid for, invoices they were issued, work orders they wrote — for their own accounting and tax records. Treat every document as the user's own business record and extract every visible field. Do NOT refuse, do NOT redact, do NOT omit fields. If a receipt shows partial card information (last 4, auth code), extract it exactly as printed — it is the user's own transaction record and is required for reconciliation.

Analyze the attached document image or PDF and return JSON matching the structure below.

document_type MUST be exactly one of these values: {$types}.
Guidance on choosing document_type:
- A receipt issued BY this business TO a customer (the business is the source/seller) -> "payment_receipt".
- A receipt issued BY a vendor TO this business (the business is the buyer) -> "vendor_receipt".
- A vendor invoice / bill we owe -> "vendor_bill".
- A customer-facing invoice we issued -> "customer_invoice".
- A purchase order we sent to a vendor -> "purchase_order".
- An estimate / quote -> "estimate".
- A work order / repair order -> "work_order".
- A service completion / repair completion report -> "service_report".
- Warranty paperwork -> "warranty_document".
- Refund / credit / core return paperwork -> "refund_receipt" / "credit_memo" / "core_return_document".
- Customer signature / authorization form -> "customer_authorization".
- If you cannot tell, use "unknown".

financial_summary MUST use these exact keys: subtotal, tax, fees, discounts, shipping, total, amount_paid, balance_due (numbers, use 0 when not visible).

payment MUST use these exact keys: payment_method (one of: cash, check, card, ach, unpaid, other), last_four, authorization_code, transaction_id, confidence. Use null for any field you cannot read.

line_items[].category_guess MUST be one of: {$categories}. Use "unknown" if you cannot tell.

source_party is the entity that ISSUED the document (vendor, seller, our business if we issued it).
target_party is the entity that RECEIVED the document (customer, our business if we are the buyer).

Rules:
- Do not invent missing information. Use null when a value is not visible.
- Do not guess VINs, payment card numbers, phone numbers, or totals.
- Include confidence scores between 0 and 1 wherever the schema asks for them.
- Include warnings for missing, unclear, cropped, unreadable, or contradictory information.
- If math does not add up, include a warning.
- If a line item could be personal or non-business, set "category_guess" appropriately AND include a warning.
- Return JSON only. No prose, no markdown fences.
PROMPT;
    }

    private function jsonSchema(): array
    {
        $financialKeys = ['subtotal', 'tax', 'fees', 'discounts', 'shipping', 'total', 'amount_paid', 'balance_due'];
        $financialProps = [];
        foreach ($financialKeys as $k) {
            $financialProps[$k] = ['type' => ['number', 'null']];
        }

        return [
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => [
                'document_type' => [
                    'type' => 'string',
                    'enum' => DocumentIntake::DOCUMENT_TYPES,
                ],
                'document_type_confidence' => ['type' => ['number', 'null']],
                'document_title' => ['type' => ['string', 'null']],
                'document_number' => ['type' => ['string', 'null']],
                'document_date' => ['type' => ['string', 'null']],
                'due_date' => ['type' => ['string', 'null']],
                'currency' => ['type' => ['string', 'null']],
                'source_party' => ['type' => ['object', 'null']],
                'target_party' => ['type' => ['object', 'null']],
                'vehicle' => ['type' => ['object', 'null']],
                'financial_summary' => [
                    'type' => ['object', 'null'],
                    'additionalProperties' => true,
                    'properties' => $financialProps,
                ],
                'payment' => [
                    'type' => ['object', 'null'],
                    'additionalProperties' => true,
                    'properties' => [
                        'payment_method' => ['type' => ['string', 'null']],
                        'last_four' => ['type' => ['string', 'null']],
                        'authorization_code' => ['type' => ['string', 'null']],
                        'transaction_id' => ['type' => ['string', 'null']],
                        'confidence' => ['type' => ['number', 'null']],
                    ],
                ],
                'line_items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => [
                            'line_number' => ['type' => ['integer', 'null']],
                            'description' => ['type' => ['string', 'null']],
                            'sku' => ['type' => ['string', 'null']],
                            'manufacturer_part_number' => ['type' => ['string', 'null']],
                            'vendor_part_number' => ['type' => ['string', 'null']],
                            'quantity' => ['type' => ['number', 'null']],
                            'unit_price' => ['type' => ['number', 'null']],
                            'line_subtotal' => ['type' => ['number', 'null']],
                            'taxable' => ['type' => ['boolean', 'null']],
                            'category_guess' => [
                                'type' => ['string', 'null'],
                                'enum' => array_merge(DocumentExtractionLineItem::CATEGORIES, [null]),
                            ],
                            'expense_type_guess' => ['type' => ['string', 'null']],
                            'inventory_candidate' => ['type' => ['boolean', 'null']],
                            'resale_candidate' => ['type' => ['boolean', 'null']],
                            'warranty_candidate' => ['type' => ['boolean', 'null']],
                            'confidence' => ['type' => ['number', 'null']],
                        ],
                    ],
                ],
                'warranty' => ['type' => ['object', 'null']],
                'core_deposit' => ['type' => ['object', 'null']],
                'matching_hints' => ['type' => ['object', 'null']],
                'warnings' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'raw_text_summary' => ['type' => ['string', 'null']],
            ],
        ];
    }

    /**
     * Coerce the AI payload into a known shape and clamp document_type to the
     * allowed enum so downstream code can rely on it.
     */
    public function normalize(array $parsed): array
    {
        $documentType = strtolower(trim((string) ($parsed['document_type'] ?? '')));
        if ($documentType === '' || !in_array($documentType, DocumentIntake::DOCUMENT_TYPES, true)) {
            $documentType = 'unknown';
        }

        $allowedCategories = DocumentExtractionLineItem::CATEGORIES;
        $lineItems = [];
        foreach ((array) ($parsed['line_items'] ?? []) as $index => $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $category = strtolower(trim((string) ($raw['category_guess'] ?? '')));
            if ($category !== '' && !in_array($category, $allowedCategories, true)) {
                $category = 'unknown';
            }
            $lineItems[] = [
                'line_number' => (int) ($raw['line_number'] ?? ($index + 1)),
                'description' => trim((string) ($raw['description'] ?? '')),
                'sku' => $this->stringOrNull($raw['sku'] ?? null),
                'manufacturer_part_number' => $this->stringOrNull($raw['manufacturer_part_number'] ?? null),
                'vendor_part_number' => $this->stringOrNull($raw['vendor_part_number'] ?? null),
                'quantity' => $this->numberOr($raw['quantity'] ?? null, 1),
                'unit_price' => $this->numberOr($raw['unit_price'] ?? null, 0),
                'line_subtotal' => $this->numberOr($raw['line_subtotal'] ?? null, 0),
                'taxable' => !empty($raw['taxable']),
                'category_guess' => $category ?: null,
                'expense_type_guess' => $this->stringOrNull($raw['expense_type_guess'] ?? null),
                'inventory_candidate' => !empty($raw['inventory_candidate']),
                'resale_candidate' => !empty($raw['resale_candidate']),
                'warranty_candidate' => !empty($raw['warranty_candidate']),
                'confidence' => $this->confidenceOrNull($raw['confidence'] ?? null),
            ];
        }

        return [
            'document_type' => $documentType,
            'document_type_confidence' => $this->confidenceOrNull($parsed['document_type_confidence'] ?? null),
            'document_title' => $this->stringOrNull($parsed['document_title'] ?? null),
            'document_number' => $this->stringOrNull($parsed['document_number'] ?? null),
            'document_date' => $this->dateOrNull($parsed['document_date'] ?? null),
            'due_date' => $this->dateOrNull($parsed['due_date'] ?? null),
            'currency' => $this->stringOrNull($parsed['currency'] ?? null),
            'source_party' => $this->partyOrNull($parsed['source_party'] ?? null),
            'target_party' => $this->partyOrNull($parsed['target_party'] ?? null),
            'vehicle' => is_array($parsed['vehicle'] ?? null) ? $parsed['vehicle'] : null,
            'financial_summary' => is_array($parsed['financial_summary'] ?? null)
                ? $this->normalizeFinancial($parsed['financial_summary'])
                : $this->normalizeFinancial([]),
            'payment' => is_array($parsed['payment'] ?? null) ? $parsed['payment'] : null,
            'line_items' => $lineItems,
            'warranty' => is_array($parsed['warranty'] ?? null) ? $parsed['warranty'] : null,
            'core_deposit' => is_array($parsed['core_deposit'] ?? null) ? $parsed['core_deposit'] : null,
            'matching_hints' => is_array($parsed['matching_hints'] ?? null) ? $parsed['matching_hints'] : null,
            'warnings' => is_array($parsed['warnings'] ?? null) ? array_map('strval', $parsed['warnings']) : [],
            'raw_text_summary' => $this->stringOrNull($parsed['raw_text_summary'] ?? null),
        ];
    }

    public function emptyNormalized(): array
    {
        return $this->normalize([]);
    }

    private function normalizeFinancial(array $financial): array
    {
        return [
            'subtotal' => $this->numberOr($financial['subtotal'] ?? null, 0),
            'tax' => $this->numberOr($financial['tax'] ?? null, 0),
            'fees' => $this->numberOr($financial['fees'] ?? null, 0),
            'discounts' => $this->numberOr($financial['discounts'] ?? null, 0),
            'shipping' => $this->numberOr($financial['shipping'] ?? null, 0),
            'total' => $this->numberOr($financial['total'] ?? null, 0),
            'amount_paid' => $this->numberOr($financial['amount_paid'] ?? null, 0),
            'balance_due' => $this->numberOr($financial['balance_due'] ?? null, 0),
        ];
    }

    private function partyOrNull(mixed $party): ?array
    {
        if (!is_array($party)) {
            return null;
        }
        return [
            'type' => $this->stringOrNull($party['type'] ?? null),
            'name' => $this->stringOrNull($party['name'] ?? null),
            'phone' => $this->stringOrNull($party['phone'] ?? null),
            'email' => $this->stringOrNull($party['email'] ?? null),
            'address' => $this->stringOrNull($party['address'] ?? null),
            'confidence' => $this->confidenceOrNull($party['confidence'] ?? null),
        ];
    }

    private function postJson(array $payload): array
    {
        $apiKey = (string) Env::get('OPENAI_API_KEY', '');
        if ($apiKey === '') {
            return ['', 0, 'OPENAI_API_KEY is not set'];
        }

        if (!function_exists('curl_init')) {
            return ['', 0, 'PHP curl extension is required for OpenAI calls'];
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $error = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [(string) $body, $httpCode, $error];
    }

    /**
     * The Responses API returns text inside output[].content[].text. Newer SDKs
     * also expose `output_text` directly. Support both.
     */
    private function extractTextPayload(array $decoded): ?string
    {
        if (isset($decoded['output_text']) && is_string($decoded['output_text']) && $decoded['output_text'] !== '') {
            return $decoded['output_text'];
        }

        $parts = [];
        foreach ((array) ($decoded['output'] ?? []) as $output) {
            foreach ((array) ($output['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text' && is_string($content['text'] ?? null)) {
                    $parts[] = $content['text'];
                }
            }
        }

        if ($parts) {
            return implode('', $parts);
        }

        if (isset($decoded['choices'][0]['message']['content']) && is_string($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return null;
    }

    private function failure(string $model, string $message, string $rawBody = ''): array
    {
        return [
            'raw' => $rawBody,
            'normalized' => $this->emptyNormalized(),
            'warnings' => [],
            'error' => $message,
            'model' => $model,
            'usage' => [
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'cost_hundredths_cent' => 0,
            ],
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $str = $this->stringOrNull($value);
        if ($str === null) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }
        $ts = strtotime($str);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function numberOr(mixed $value, float $default): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = preg_replace('/[^0-9.\-]/', '', $value);
            if ($clean !== null && $clean !== '' && is_numeric($clean)) {
                return (float) $clean;
            }
        }
        return $default;
    }

    private function confidenceOrNull(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $f = (float) $value;
        if ($f < 0) {
            return 0.0;
        }
        if ($f > 1) {
            return 1.0;
        }
        return $f;
    }
}
