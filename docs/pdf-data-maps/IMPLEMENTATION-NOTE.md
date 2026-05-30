# PDF rewrite — implementation note

## Mockups reviewed

All 15 mockup files under [/mockups](../../mockups/) were reviewed.
The four primary inputs were:

| File                              | What was adopted                                                                                                  |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `_styles.css`                     | Visual language: black letter-spaced banner, gray (#f2f2f2) header cells, 0.5pt borders, table-heavy layout       |
| `00-estimate.html`                | Three-column masthead (logo + company info + doc-meta), meta strip (customer / vehicle / totals), charges table   |
| `03-receipt.html`                 | Two-column footer (legal text + signature/pill block), key-value Payment table, warranty boilerplate placement    |
| `02-change-order.html`            | Detail bar pattern for one-line strips ("References Waiver: …"), totals-with-delta layout idea                    |

The remaining mockups (waiver, customer-refusal, technician-decline,
diagnostic, tow-referral, internal forms) helped confirm that the
same six section primitives (masthead, banner, meta-strip, detail-bar,
section heading + paragraphs/bullets/kv-table, charges-table, footer)
cover every workbook document. None of them have been wired to a
generator yet — only the four PDFs the app produces today have
view models.

## Adopted visual ideas

* Black letter-spaced uppercase banner with right-aligned page number
* Light-gray header cell fills (key column of every K/V table)
* Doc-meta table in the top-right of the masthead
* Three-column meta strip: customer / vehicle / totals
* Charges table with a black header bar and gray subtotal rows
* Total row gets a thicker border (1.5pt) and bold text
* Footer pill: bordered box with bold label + right-aligned amount
* Consistent margin (0.5") and content width across all documents

## Explicitly rejected (would have been copying fake data)

| Mockup item                                    | Why rejected                                                                                  |
| ---------------------------------------------- | --------------------------------------------------------------------------------------------- |
| `J. Sample / j.sample@example.com / (503) 555-0142` | Placeholder customer; never appears in generated PDFs                                     |
| `2015 HONDA CIVIC LX / 19XFB2F50FE000000 / 123 ABC OR` | Placeholder vehicle; vehicle data comes from `vehicles` row                            |
| `1234 SE Example St / Portland, OR 97214`      | Placeholder address; comes from joined `locations` row                                        |
| `E-1023 / R-1023 / Job 1023`                   | Mockup-only IDs; real numbers come from `NumberingService::next($prefix)`                     |
| `Apr 26, 2026 9:42 AM`                         | Hardcoded date; formatted from the source row's `created_at` / `paid_at` / `issued_at`        |
| Specific Square auth code `A1B2C3`             | Placeholder reference; comes from `payments.transaction_reference`                            |
| Line items (battery, alternator, Duralast Gold) | Placeholder catalog rows; come from `estimate_line_items` / `invoice_line_items`             |
| `Lifetime warranty` / `Group 51R AGM` claims   | Marketing language tied to a specific part; not a generated PDF concern                       |
| Google review URL `g.page/r/WhiteKnightRoadsidePDX/review` and QR | No config field for review URL, no QR generator in the stack                |
| Mockup "Time on Scene" / "Mileage Out" rows on receipt | No source columns yet — `vehicles` has no `mileage_out`; not invented                 |
| Embedded shield-logo SVG                       | Hand-rolled PDF writer has no image XObject support; placeholder initials box used instead    |
| Times New Roman body font                      | No font embedding without a real PDF library; Helvetica used everywhere                       |
| Legal copy ("Oregon ORS …", waiver language)   | Per `mockups/index.html`, ship-ready legal needs Oregon-licensed counsel review; not adopted  |

## Updated PDF templates

Six document types now use view models + the new `Pdf` namespace
renderer (the original four plus Work Order and Service Completion
Report, which were named in the original agent brief):

| Document type             | Trigger                                                       | View model class                                                              |
| ------------------------- | ------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `estimate_pdf`            | POST `/estimates/{id}/documents/generate`                     | `EstimatePdfViewModel`                                                        |
| `invoice_pdf`             | POST `/invoices/{id}/documents/generate`                      | `InvoicePdfViewModel`                                                         |
| `receipt_pdf`             | POST `/receipts/{id}/documents/generate`                      | `ReceiptPdfViewModel`                                                         |
| `work_order_pdf`          | POST `/work-orders/{id}/documents/generate`                   | `WorkOrderPdfViewModel`                                                       |
| `service_completion_pdf`  | POST `/service-reports/{id}/documents/generate`               | `ServiceCompletionReportPdfViewModel`                                         |
| `proof_packet_pdf`        | POST `/service-requests/{id}/proof-packet/documents/generate` | `ProofPacketPdfViewModel`                                                     |

`POST /documents/{id}/regenerate` also goes through the new pipeline.
`PdfTemplateResolver` is the single place that knows the doc-type →
view model / redirect mapping, so adding new types does not touch
`DocumentController`.

## Architecture (under `app/Services/Pdf/`)

```
CompanyInfo                — env-driven tenant identity
PdfRenderer                — low-level page writer (text, rects, fills, fonts, alignment)
PdfDocument                — section composer (masthead, banner, meta-strip, charges, footer)
PdfMoney                   — money formatting helpers
PdfValidationException     — thrown when required data is missing
PdfDataValidator           — required-field guard for view models
PdfTemplateResolver        — maps doc-type → view model + redirect paths
ViewModels/
  PdfViewModel             — abstract base; common customer/vehicle helpers
  EstimatePdfViewModel
  InvoicePdfViewModel
  ReceiptPdfViewModel
  WorkOrderPdfViewModel
  ServiceCompletionReportPdfViewModel
  ProofPacketPdfViewModel
```

`app/Services/PdfService.php` is the thin orchestrator that runs a view
model, writes the bytes, and registers a `file_attachments` row.
`DocumentController` calls `Estimate::recalculate` / `Invoice::recalculate`
before constructing the view model so totals never drift from line items.

## Data-mapping docs (one per PDF type)

* [estimate.md](estimate.md)
* [invoice.md](invoice.md)
* [receipt.md](receipt.md)
* [work-order.md](work-order.md)
* [service-completion-report.md](service-completion-report.md)
* [proof-packet.md](proof-packet.md)

See also [TODO.md](TODO.md) for the rest of the document types named in
the original agent brief and the workbook mockups.

## Known limitations

* **No font embedding.** Helvetica only (built-in PDF Type-1 base font).
  Text outside CP1252 is transliterated or stripped by
  `PdfRenderer::escapeString`.
* **No raster images.** The logo is a bordered initials box; the
  on-file signature image is not embedded in the receipt PDF. Both
  require PDF image XObjects, which the hand-rolled writer does not
  implement.
* **Approximate text widths.** Right-aligned columns use a Helvetica
  width approximation (`PdfRenderer::measure`). Visual alignment is
  good for normal labels; pixel-perfect alignment of pathological
  strings (heavy ligature use, many `W`/`i` mixes) is not guaranteed.
* **No PDF library.** The project is "no framework, plain OOP PHP +
  PDO" — `composer.json` does not exist. If a polished mockup-fidelity
  output becomes a priority, dropping in Dompdf or mPDF behind the
  same `PdfDocument` interface is a few hundred lines of change; the
  view models stay as-is.
