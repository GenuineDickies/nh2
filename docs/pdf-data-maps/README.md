# PDF data maps

Each file in this directory documents the field-by-field data
provenance for a generated PDF. The rule everywhere in this system is
that **every visible value in a PDF must trace to a real source** —
database column, derived calculation, configured setting, system-
generated identifier, or approved boilerplate. No mockup data, no
hardcoded customer details, no decorative fields.

When a PDF template (or its view model in `app/Services/Pdf/ViewModels/`)
gains a new field or changes a source, **update the matching map here
in the same commit**. The maps are reviewed during the PDF QA pass on
each release.

| Doc type                  | Map file                                                            | View model                                                                                  |
| ------------------------- | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| Estimate                  | [estimate.md](estimate.md)                                          | `App\Services\Pdf\ViewModels\EstimatePdfViewModel`                                          |
| Invoice                   | [invoice.md](invoice.md)                                            | `App\Services\Pdf\ViewModels\InvoicePdfViewModel`                                           |
| Receipt                   | [receipt.md](receipt.md)                                            | `App\Services\Pdf\ViewModels\ReceiptPdfViewModel`                                           |
| Work Order                | [work-order.md](work-order.md)                                      | `App\Services\Pdf\ViewModels\WorkOrderPdfViewModel`                                         |
| Service Completion Report | [service-completion-report.md](service-completion-report.md)        | `App\Services\Pdf\ViewModels\ServiceCompletionReportPdfViewModel`                           |
| Proof Packet              | [proof-packet.md](proof-packet.md)                                  | `App\Services\Pdf\ViewModels\ProofPacketPdfViewModel`                                       |

Adding a new document type: register it in `App\Services\Pdf\PdfTemplateResolver::TYPES`, add a case to `resolve()`/`successRedirectFor()`/`indexRedirectFor()`/`relatedTypeFor()`, write the view model + data map, and add a route. No `DocumentController` change required.

See also: **[TODO.md](TODO.md)** — the living TODO for the PDF system
(unrendered mockup types, deferred work, renderer improvements). Update it
in the same commit as any PDF change.

Background on the original rewrite: **[IMPLEMENTATION-NOTE.md](IMPLEMENTATION-NOTE.md)**
— which mockup ideas were adopted, which were rejected, and why.

## Shared sources

These appear in every PDF and are sourced the same way each time:

| Field              | Source                                                                                       |
| ------------------ | -------------------------------------------------------------------------------------------- |
| Company name       | `COMPANY_NAME` env var via `App\Services\Pdf\CompanyInfo` (default: "White Knight Roadside, LLC") |
| Company address    | `COMPANY_ADDRESS_LINE_1`, `COMPANY_ADDRESS_LINE_2`, `COMPANY_CITY_STATE_ZIP` env vars       |
| Company phone      | `COMPANY_PHONE` env var                                                                      |
| Company email      | `COMPANY_EMAIL` env var                                                                      |
| Page number        | Tracked by `PdfDocument`, painted on each `banner()` invocation                              |
| Document title     | View model `title()` method                                                                  |
| Document number    | `generated_documents.document_number` — minted by `NumberingService::next('PDF')`            |

## Validation behavior

Each view model calls `PdfDataValidator` for the fields its document
cannot render without. On any miss the controller (`DocumentController::renderValidationError`)
returns HTTP 422 with the missing-field list, and the placeholder
`generated_documents` row is left without a `file_attachment_id` —
i.e., generation can be retried after the source record is corrected.
