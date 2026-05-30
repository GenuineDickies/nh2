# PDF system — living TODO

The PDF pipeline rewrite (2026-05-24) covered the four document types
the app generates today. This file tracks everything that was deferred
or is worth doing next. Update it in the same commit as the work it
describes — keep it honest by deleting items as they ship and adding
new ones the moment they're identified.

Each item carries a status tag:

| Tag             | Meaning                                                                  |
| --------------- | ------------------------------------------------------------------------ |
| `done`          | Shipped — kept here briefly for context, then pruned                     |
| `next`          | Highest-leverage next-up; pick from here when starting PDF work          |
| `deferred`      | Identified but not in scope; rationale recorded                          |
| `blocked`       | Cannot start until a listed dependency lands                             |
| `nice-to-have`  | Real improvement but no concrete driver yet                              |

---

## Foundation (shipped 2026-05-24)

* `done` — Layered `app/Services/Pdf/` architecture (Renderer, Document, Money, Validator, **Resolver**, ViewModels)
* `done` — Env-driven `CompanyInfo` so tenants can rebrand without template edits
* `done` — Six view models: **Estimate, Invoice, Receipt, Work Order, Service Completion Report, Proof Packet**
* `done` — Totals recomputed from line items inside each view model (meta strip ↔ charges table agree)
* `done` — `PdfDataValidator` blocks final render when required source data is missing; user sees a 422 page listing the missing fields
* `done` — `PdfTemplateResolver` maps doc-type → view model + redirect paths, so `DocumentController` is a thin orchestrator
* `done` — Data maps for every wired PDF type: [estimate.md](estimate.md), [invoice.md](invoice.md), [receipt.md](receipt.md), [work-order.md](work-order.md), [service-completion-report.md](service-completion-report.md), [proof-packet.md](proof-packet.md)
* `done` — Smoke-test script at [scripts/smoke-test-pdf.php](../../scripts/smoke-test-pdf.php) — exercises every view model and the validator's negative path

---

## Next up (PDF expansion)

* `next` — **Wire the remaining unrendered mockup document types.** The visual primitives in `PdfDocument` already cover them; adding each one is a single resolver entry + view model class + data map. No controller plumbing changes needed. A migration is added in the same commit when the schema needs it — we own the schema, nothing is externally blocked.

  Group A — **ship today, no schema change needed** (existing tables already carry the data):

  | Mockup                                                              | Existing source records                                                                                | New view model                  | New data map                              |
  | ------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ | ------------------------------- | ----------------------------------------- |
  | [01-waiver.html](../../mockups/01-waiver.html)                      | `customer_approvals` (approval_method, signature_file_id, ip_address, user_agent, latitude, longitude) | `WaiverPdfViewModel`            | `docs/pdf-data-maps/waiver.md`            |
  | [07-service-request.html](../../mockups/07-service-request.html)    | `service_requests` + joined `customers` / `vehicles` / `locations`                                     | `ServiceRequestPdfViewModel`    | `docs/pdf-data-maps/service-request.md`   |
  | [10-service-log.html](../../mockups/10-service-log.html)            | `service_requests.timeline()` (audit_logs) + `work_orders` + `service_completion_reports`              | `ServiceLogPdfViewModel`        | `docs/pdf-data-maps/service-log.md`       |
  | [09-hazard-assessment.html](../../mockups/09-hazard-assessment.html) | Could ship v0 against `work_orders.notes` as a JSON blob, or take Group B route with a real checklist table — design call | `HazardAssessmentPdfViewModel` | `docs/pdf-data-maps/hazard-assessment.md` |

  Group B — **needs a migration in the same commit** (table doesn't exist yet):

  | Mockup                                                                          | New table(s) to add                                                                                                                            | New view model                  | New data map                              |
  | ------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------- | ----------------------------------------- |
  | [02-change-order.html](../../mockups/02-change-order.html)                      | `change_of_service` (`customer_approvals.change_of_service_id` already references it; the table itself is missing — FK is currently dangling) | `ChangeOrderPdfViewModel`       | `docs/pdf-data-maps/change-order.md`      |
  | [04-customer-refusal.html](../../mockups/04-customer-refusal.html)              | A few columns on `service_requests` (`refused_at`, `refusal_reason`, `refused_by`) OR a `service_refusals` table — design call                | `CustomerRefusalPdfViewModel`   | `docs/pdf-data-maps/customer-refusal.md`  |
  | [05-technician-decline.html](../../mockups/05-technician-decline.html)          | Same shape as customer-refusal but recorded against `work_orders` (`declined_at`, `decline_reason`)                                            | `TechnicianDeclinePdfViewModel` | `docs/pdf-data-maps/technician-decline.md`|
  | [06-diagnostic-report.html](../../mockups/06-diagnostic-report.html)            | `diagnostic_reports` + `diagnostic_findings` (or reuse `service_completion_reports` with a `report_type` discriminator — design call)         | `DiagnosticReportPdfViewModel`  | `docs/pdf-data-maps/diagnostic-report.md` |
  | [08-vehicle-condition-report.html](../../mockups/08-vehicle-condition-report.html) | `vehicle_condition_reports` + per-panel rows (driver-front, passenger-front, etc., each with damage flag + photo FK)                          | `VehicleConditionPdfViewModel`  | `docs/pdf-data-maps/vehicle-condition.md` |
  | [11-tow-referral.html](../../mockups/11-tow-referral.html)                      | `tow_referrals` (tow_company, destination, contact_phone, referral_at, etc.)                                                                  | `TowReferralPdfViewModel`       | `docs/pdf-data-maps/tow-referral.md`      |
  | [12-incident-report.html](../../mockups/12-incident-report.html)                | `incident_reports` (incident_type, severity, narrative, injuries_flag, damage_flag, photo attachments)                                        | `IncidentReportPdfViewModel`    | `docs/pdf-data-maps/incident-report.md`   |
  | [13-truck-inspection.html](../../mockups/13-truck-inspection.html)              | `truck_inspections` + checklist rows (tires, fluids, M&B kit, mechanic kit) per shift                                                          | `TruckInspectionPdfViewModel`   | `docs/pdf-data-maps/truck-inspection.md`  |
  | [14-end-of-shift.html](../../mockups/14-end-of-shift.html)                      | `shift_reconciliations` (cash_in, card_in, mileage_out, equipment_returned_flag)                                                              | `EndOfShiftPdfViewModel`        | `docs/pdf-data-maps/end-of-shift.md`      |

  For Group A, no schema work is needed — pick one and ship it. For Group B, add the migration file to `database/migrations/` and the view model in the same commit.

* `next` — **Other document types the original agent brief listed but the app does not yet generate:** Dispatch (currently part of Work Order), Refund Receipt, Credit Memo, Customer Statement, Parts Warranty / Warranty Certificate, Vendor Bill outputs. Each needs its own data model + view model + map.

* `next` — **Persist the legal & boilerplate copy in config or a template file** (currently inline string literals inside each view model). Once Oregon-licensed counsel reviews the wording, store the approved text in one place so future legal updates don't require editing PHP.

* `next` — **Move `Estimate::TAX_RATE` / `Invoice::TAX_RATE` to config.** Today both are PHP constants set to 9.5%. The data maps already note this; an env var (`TAX_RATE` or per-jurisdiction lookup) is the minimum change. Adding multiple tax jurisdictions is a bigger redesign.

---

## Renderer improvements

* `nice-to-have` — **Logo XObject support** in `PdfRenderer` so the masthead can carry the real raster logo instead of the initials placeholder. Requires PDF Image XObject (JPEG via `DCTDecode` is easiest), font-side unchanged.
* `nice-to-have` — **Embed captured signature image on the Receipt PDF** when `customer_approvals.signature_file_id` is set. Blocked on the XObject work above.
* `nice-to-have` — **Times New Roman body font** to match the mockups exactly. Requires a Type-1 or TrueType font program in the PDF (`/Font /Subtype /TrueType` + `/FontFile2`); meaningfully larger blob and `PdfRenderer::measure` would need real AFM metrics.
* `nice-to-have` — **QR code on the Receipt PDF** pointing at the Google review URL. Needs a review-URL config field and a QR generator (current writer has no image XObject support).
* `nice-to-have` — **Better Helvetica width metrics.** `PdfRenderer::measure` uses a flat 0.5/0.55 multiplier today; loading the standard Helvetica AFM widths would tighten right-aligned columns and reduce truncation surprises for `WMW`-heavy strings.

---

## Hard rule for PDF copy — no unbacked integration claims

PDF body text MUST NOT imply the system does anything it doesn't actually
do. The customer reads these as factual statements about what the
business will do for them; a promise that turns out to be a lie is a
real liability.

**Forbidden phrasings until the matching integration ships:**

| Phrase                                      | Implies                                          | Status today           |
| ------------------------------------------- | ------------------------------------------------ | ---------------------- |
| "emailed / SMSed / texted to you"           | Automated email or SMS send                      | NOT IMPLEMENTED        |
| "we'll text/email you a reminder"           | Notification system                              | NOT IMPLEMENTED        |
| "scan this QR code to leave a review"       | Review URL configured + QR generator             | NOT IMPLEMENTED        |
| "tap to pay" / "Square Tap-to-Pay"          | Square API integration                           | NOT IMPLEMENTED — `square` is a manual payment-method label only |
| "Stripe-secured payment"                    | Stripe API integration                           | NOT IMPLEMENTED — `stripe` is a manual payment-method label only |
| "Telnyx-backed two-factor confirmation"     | Telnyx integration                               | NOT IMPLEMENTED        |
| "auto-syncs with QuickBooks"                | QuickBooks export                                | NOT IMPLEMENTED        |
| "VIN auto-decoded"                          | VIN decode service                               | NOT IMPLEMENTED        |
| "we'll dispatch the nearest technician"     | Dispatch / routing integration                   | NOT IMPLEMENTED        |
| "click the secure link in your text"        | SMS link delivery                                | NOT IMPLEMENTED        |

**Allowed phrasings** (these describe things the system actually does
or are factual contact info, not autonomous behavior):

* "Contact $phone or $email" — operator answers manually; fine.
* "Approval is captured on `customer_approvals`" — data field exists.
* "The customer portal page" — `/p/estimate/{token}` is wired.
* "Workmanship — 12 months or 12,000 miles" — business policy, not a system feature.
* "Make checks payable to $legalName" — instruction, not a feature claim.

**When writing a new view model:** before adding boilerplate copy, search
the codebase for the integration you're about to reference. If there's
no controller / service / vendor SDK call backing it, don't claim it.
Add the wording to the table above as `forbidden until shipped` and
move on.

This file (incident-causing case): 2026-05-24 — `EstimatePdfViewModel`
shipped with "Acceptance can be verbal, by text, or via the link
emailed/SMSed when this estimate was sent." The system has no SMS or
email sender. Caught by the user; rewritten to be factually accurate.

---

## Larger structural moves

* `deferred` — **Adopt a real PDF library (Dompdf or mPDF).** The view models would not change; `PdfDocument` would gain an HTML-emitting backend, and the layout would match the mockups much more closely (Times New Roman, raster logos, real CSS). Blocked on adding `composer.json` to the project — `README.md` explicitly says "No framework; plain OOP PHP". Worth a conversation with the project owner before doing.

* `deferred` — **Multi-tenant PDF templates.** Today `CompanyInfo::fromEnv` reads one tenant. If a future deployment runs multiple operators on one install, `CompanyInfo` would need a tenant-id lookup and the smoke-test would need a tenant fixture.

* `deferred` — **PDF regeneration job runner.** When boilerplate copy or company info changes, every previously-generated PDF freezes the old text. Bulk regenerate is a single SQL query + a job-queue loop, but the project has no queue today.

* `deferred` — **Telnyx SMS sender for the public-link approval flow.** Today the operator copies the `/p/estimate/{token}` URL out of the app and sends it via their own phone. Once Telnyx is wired (Sprint 19 — Integrations), the Estimate PDF footer can promise SMS delivery — and the data map's "What this PDF intentionally does NOT show" note can be relaxed. Until then, do not write copy that implies automated SMS.

* `deferred` — **Transactional email sender.** Same pattern as the SMS item — the public-link URL is shared manually today. Wire SES / Mailgun / Postmark / similar, then update Estimate / Invoice / Receipt footers.

* `deferred` — **Square / Stripe API integration.** Today both names are payment-method labels the operator picks manually when recording a payment (`Payment::METHODS`), with the auth code / charge ID typed into `payments.transaction_reference`. A real integration would auto-create payments + transaction_reference on capture. Until that ships, PDFs MUST NOT say "Square Tap-to-Pay" or similar.

* `deferred` — **Google review URL + QR code on the Receipt PDF.** Needs a `REVIEW_URL` env var (or config row) and a QR generator. Without it, do not include a "scan to review" block on receipts.

---

## Hygiene

* `nice-to-have` — Replace the hand-rolled `mockups/_styles.css` initials-shield placeholder in `PdfDocument::masthead` with whatever logo path the user ships when one is available.
* `nice-to-have` — Convert `scripts/smoke-test-pdf.php` into a PHPUnit test so it runs on CI when one is wired up.
* `nice-to-have` — `PdfRenderer::escapeString` currently transliterates non-CP1252 characters. If we ever ship outside the WinAnsi range (e.g. a customer with diacritics) we need full Unicode via TrueType embedding.
