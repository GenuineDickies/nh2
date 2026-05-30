<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Declarative metadata for editable settings.
 *
 * Each entry describes one setting — what it controls, where the operator
 * should look to find the correct value, and how it should be rendered.
 *
 * Keep "description" plain and short; keep "where_to_find" specific (real
 * dashboard names, exact tabs/labels) so a non-technical operator can find
 * the value without guessing.
 *
 * Field shape:
 *   key          string  — env var name (also the app_settings.setting_key)
 *   label        string  — short human label
 *   type         string  — "text" | "secret" | "select"
 *   options      array   — only for type="select" (value => human label)
 *   required     bool    — show in "missing" alert when blank
 *   description  string  — what this setting is / what it controls
 *   where_to_find string — concrete step-by-step location for the value
 *   placeholder  string  — optional input placeholder hint
 */
final class SettingsCatalog
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     *         group_slug => list of setting definitions in display order
     */
    public static function groups(): array
    {
        return [
            'square' => self::squareGroup(),
        ];
    }

    public static function group(string $slug): array
    {
        $groups = self::groups();
        return $groups[$slug] ?? [];
    }

    public static function fieldKeys(string $groupSlug): array
    {
        return array_map(static fn (array $f) => (string) $f['key'], self::group($groupSlug));
    }

    private static function squareGroup(): array
    {
        return [
            [
                'key' => 'SQUARE_ENVIRONMENT',
                'label' => 'Environment',
                'type' => 'select',
                'options' => [
                    '' => '— Select —',
                    'sandbox' => 'Sandbox (test transactions, fake money)',
                    'production' => 'Production (real customers, real money)',
                ],
                'required' => true,
                'description' =>
                    'Which Square API server this app talks to. "Sandbox" routes everything to '
                    . 'test accounts so you can dry-run charges without touching real cards. '
                    . '"Production" hits live Square accounts and real cardholders.',
                'where_to_find' =>
                    'Pick "Sandbox" while you are testing the integration. Switch to '
                    . '"Production" only after you have your live Square credentials and you are '
                    . 'ready to accept real payments. Every other setting on this page must come '
                    . 'from the matching side (sandbox or production) of your Square Developer '
                    . 'Dashboard.',
            ],
            [
                'key' => 'SQUARE_ACCESS_TOKEN',
                'label' => 'Access Token',
                'type' => 'secret',
                'required' => true,
                'description' =>
                    'The long-lived authentication token Square issues to your application. '
                    . 'Sent on every request this app makes to the Square API so Square knows '
                    . 'which merchant it is acting on behalf of.',
                'where_to_find' =>
                    'Sign in at developer.squareup.com → open your application from the list → '
                    . 'click the "Credentials" tab → switch the toggle in the upper-right to '
                    . 'either "Sandbox" or "Production" to match the Environment chosen above → '
                    . 'copy the value labeled "Access token". Sandbox tokens start with '
                    . '"EAAAEx…"; production tokens start with "EAAA…".',
                'placeholder' => 'EAAA…',
            ],
            [
                'key' => 'SQUARE_LOCATION_ID',
                'label' => 'Location ID',
                'type' => 'text',
                'required' => true,
                'description' =>
                    'Identifies which of your Square business locations this app should record '
                    . 'payments and orders against. A single Square account can have many '
                    . 'locations (storefronts, vehicles, pop-ups, etc.).',
                'where_to_find' =>
                    'For Production: sign in to your Square Dashboard at app.squareup.com → '
                    . '"Settings" (gear icon, bottom-left) → "Account & Settings" → "Business" → '
                    . '"Locations". Each location row shows its Location ID, e.g. '
                    . '"L1AB23CD45EFG6". Copy the ID for the location that should own the money '
                    . 'this app brings in. '
                    . 'For Sandbox: developer.squareup.com → your sandbox test account → "Locations".',
                'placeholder' => 'L…',
            ],
            [
                'key' => 'SQUARE_WEBHOOK_SIGNATURE_KEY',
                'label' => 'Webhook Signature Key',
                'type' => 'secret',
                'required' => true,
                'description' =>
                    'Shared secret Square uses to sign every webhook notification it sends here. '
                    . 'The server checks this signature so it can trust the webhook really came '
                    . 'from Square and was not forged by someone else.',
                'where_to_find' =>
                    'In your Square Developer Dashboard → your application → "Webhooks" → '
                    . '"Subscriptions" → open (or create) the subscription pointed at the webhook '
                    . 'endpoint shown below this form → copy the value labeled "Signature Key". '
                    . 'If you reset this key in Square, paste the new value here right away or '
                    . 'incoming webhooks will start failing signature checks.',
            ],
            [
                'key' => 'SQUARE_APPLICATION_ID',
                'label' => 'Application ID',
                'type' => 'text',
                'required' => false,
                'description' =>
                    'Public identifier for your Square application. Only needed if you embed '
                    . 'Square\'s in-browser payment form (the Web Payments SDK) on your own pages. '
                    . 'Server-side API calls do not use it.',
                'where_to_find' =>
                    'Square Developer Dashboard → your application → "Credentials" tab → the '
                    . 'value labeled "Application ID". Starts with "sandbox-sq0idp-" for Sandbox '
                    . 'or "sq0idp-" for Production. Leave blank if you are not using the Web '
                    . 'Payments SDK.',
                'placeholder' => 'sq0idp-… or sandbox-sq0idp-…',
            ],
            [
                'key' => 'SQUARE_API_VERSION',
                'label' => 'API Version',
                'type' => 'text',
                'required' => false,
                'description' =>
                    'Pin Square\'s API to a specific date-versioned schema (e.g. "2024-04-17"). '
                    . 'Leave blank to use whatever version the installed Square SDK ships with — '
                    . 'that is the right answer in almost every case.',
                'where_to_find' =>
                    'Only set this if Square engineering or the release notes at '
                    . 'developer.squareup.com/docs/build-basics/versioning have asked you to. '
                    . 'Otherwise leave it blank.',
                'placeholder' => 'e.g. 2024-04-17',
            ],
        ];
    }
}
