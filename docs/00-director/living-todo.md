# Living TODO

## Current Phase

Phase name: MVP Hardening - Authentication and deploy readiness
Current objective: Land the remaining real-world MVP blockers: authentication (done), source control init, MySQL production validation, and a backup story.
Current status: Authentication is in place. Every route except /login, /logout, and /setup now requires a session. First-run setup creates the initial admin. Audit log entries capture actor_user_id. Deploy and backup notes are documented under docs/30-delivery/deploy-and-backup.md. Source control init, MySQL verification, and a real-device mobile pass remain.

## Completed

- [x] Added user profile page with secure self-service updates and admin email role enforcement
  - Files changed:
    - app/Core/Csrf.php
    - app/Models/User.php
    - app/Controllers/ProfileController.php
    - app/Views/profile/show.php
    - app/Views/layouts/app.php
    - public/assets/css/app.css
    - public/index.php
    - database/migrations/202605270001_ensure_admin_email_role.php
  - Tests run:
    - PHP syntax check on changed files.
    - Attempted migration run via `php scripts/migrate.php` (blocked by local missing PDO SQLite driver + DB_DSN unset in this shell).
  - Manual checklist (TODO):
    - [ ] Authenticated user can open `/profile`.
    - [ ] User can update own name/email with validation and CSRF protection.
    - [ ] User can change own password only with correct current password.
    - [ ] Non-admin user gets 403 for `/users/{id}/profile` on another user.
    - [ ] Admin user can view/edit other user profiles and reset passwords.
    - [ ] Migration marks `admin@wkrllc.com` as `admin` idempotently.
  - Notes:
    - Profile UI uses existing dark admin panel conventions.
    - Avatar upload was intentionally not added because no existing user avatar storage pipeline exists yet.

- [x] Added admin Square settings status page (read-only)
  - Files changed:
    - app/Services/SquareConfigStatusService.php
    - app/Controllers/SquareSettingsController.php
    - app/Views/settings/square.php
    - app/Views/layouts/app.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed files.
  - Manual checklist (TODO):
    - [ ] Missing required vars shows warning banner.
    - [ ] All required vars present shows success banner.
    - [ ] Secrets are masked (token/signature never shown raw).
    - [ ] Non-admin user receives 403 Forbidden.
  - Notes:
    - No DB migration required.
    - Route added at /admin/settings/square and access is admin-only.

- [x] Initial workspace audit
  - Files changed:
    - docs/00-director/living-todo.md
    - docs/00-director/decisions.md
    - docs/10-product/workflow.md
    - docs/20-architecture/database.md
    - docs/30-delivery/test-plan.md
  - Tests run:
    - Listed project root contents.
    - Listed project files.
    - Checked for Git repository status.
  - Notes:
    - Workspace root was empty.
    - This folder is not currently a Git repository.
    - No existing application code was available to audit.

- [x] Created documentation structure
  - Files changed:
    - docs/00-director/
    - docs/10-product/
    - docs/20-architecture/
    - docs/30-delivery/
  - Tests run:
    - Confirmed folders and files were created.
  - Notes:
    - Added required Phase 0 docs plus roadmap, requirements, architecture, and traceability matrix.

- [x] Scaffolded PHP application foundation
  - Files changed:
    - .env.example
    - .gitignore
    - app/bootstrap.php
    - app/Core/Controller.php
    - app/Core/Database.php
    - app/Core/Env.php
    - app/Core/MigrationRunner.php
    - app/Core/Repository.php
    - app/Core/Router.php
    - app/Core/View.php
    - public/index.php
    - scripts/migrate.php
  - Tests run:
    - PHP syntax check across all PHP files.
    - Migration runner test.
  - Notes:
    - Local development defaults to SQLite when DB_DSN is not set.
    - MySQL is supported through .env configuration.

- [x] Added Sprint 1 migration tables
  - Files changed:
    - database/migrations/202605230001_create_sprint_1_tables.php
  - Tests run:
    - Ran migration successfully.
  - Notes:
    - Created customers, vehicles, locations, intakes, service_requests, audit_logs, and migrations table.

- [x] Built dashboard, intake, and service request foundation
  - Files changed:
    - app/Controllers/DashboardController.php
    - app/Controllers/IntakeController.php
    - app/Controllers/ServiceRequestController.php
    - app/Models/AuditLog.php
    - app/Models/Customer.php
    - app/Models/Intake.php
    - app/Models/Location.php
    - app/Models/ServiceRequest.php
    - app/Models/Vehicle.php
    - app/Services/NumberingService.php
    - app/Views/layouts/app.php
    - app/Views/layouts/error.php
    - app/Views/dashboard/index.php
    - app/Views/intake/index.php
    - app/Views/intake/new.php
    - app/Views/intake/show.php
    - app/Views/service-requests/index.php
    - app/Views/service-requests/show.php
    - public/assets/css/app.css
    - public/assets/js/app.js
  - Tests run:
    - Rendered dashboard route through PHP.
    - Rendered intake route through PHP.
    - Rendered service request route through PHP.
    - Created intake.
    - Converted intake to service request.
    - Confirmed duplicate customer check by phone.
    - Confirmed vehicle and location preservation.
    - Confirmed service request audit timeline.
    - Confirmed invalid intake validation.
  - Notes:
    - Browser server could not stay reachable in this sandbox, so route rendering and workflow were verified through direct PHP execution.

- [x] Cleaned workflow test records
  - Files changed:
    - storage/app.sqlite
  - Tests run:
    - Confirmed customers, intakes, and service_requests are back to zero records.
    - Confirmed clean dashboard renders.
  - Notes:
    - Migration history remains intact so the local database is ready for real entries.

- [x] Fixed CSS and JS asset paths
  - Files changed:
    - app/Core/View.php
    - app/Views/layouts/app.php
    - app/Views/layouts/error.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed files.
    - Render check for /assets/css/app.css when public is the web root.
    - Render check for /public/assets/css/app.css when opening through /public/index.php.
  - Notes:
    - Stylesheet exists at public/assets/css/app.css.
    - Layout now generates the correct asset URL for common local setups.

- [x] Added direct service request creation and status transitions
  - Files changed:
    - public/index.php
    - app/Controllers/ServiceRequestController.php
    - app/Models/Location.php
    - app/Models/ServiceRequest.php
    - app/Models/Vehicle.php
    - app/Views/service-requests/index.php
    - app/Views/service-requests/new.php
    - app/Views/service-requests/show.php
    - public/assets/css/app.css
  - Tests run:
    - PHP syntax check across all PHP files.
    - Rendered new service request form.
    - Confirmed invalid service request validation.
    - Created direct service request through models.
    - Confirmed customer, location, and vehicle links.
    - Updated service request status from pending to accepted.
    - Confirmed audit timeline includes create and status change events.
    - Cleaned test records afterward.
  - Notes:
    - Service Request statuses remain limited to pending, accepted, completed, cancelled, and rejected.

- [x] Added customer and vehicle review pages
  - Files changed:
    - public/index.php
    - app/Controllers/CustomerController.php
    - app/Controllers/VehicleController.php
    - app/Models/Customer.php
    - app/Models/Vehicle.php
    - app/Views/layouts/app.php
    - app/Views/customers/index.php
    - app/Views/customers/show.php
    - app/Views/vehicles/index.php
    - app/Views/vehicles/show.php
    - public/assets/css/app.css
  - Tests run:
    - PHP syntax check across all PHP files.
    - Rendered empty customer and vehicle states.
    - Created customer, vehicle, and service request test records.
    - Confirmed customer list/detail show service history.
    - Confirmed vehicle list/detail show customer and service history.
    - Cleaned test records afterward.
  - Notes:
    - Customer and vehicle records are still created through intake conversion or service request creation.

- [x] Improved dashboard command center
  - Files changed:
    - app/Controllers/DashboardController.php
    - app/Views/dashboard/index.php
    - public/assets/css/app.css
  - Tests run:
    - Rendered empty dashboard state.
    - Created test intake and service request records.
    - Confirmed latest intake and latest service request panels populate.
    - Confirmed counts and related customer/vehicle pages still render.
    - Cleaned test records afterward.
  - Notes:
    - Dashboard now includes customer, vehicle, missing VIN, and accepted job counts.

- [x] Added intake and service request edit flows
  - Files changed:
    - public/index.php
    - app/Controllers/IntakeController.php
    - app/Controllers/ServiceRequestController.php
    - app/Models/Customer.php
    - app/Models/Intake.php
    - app/Models/Location.php
    - app/Models/ServiceRequest.php
    - app/Models/Vehicle.php
    - app/Views/intake/edit.php
    - app/Views/intake/show.php
    - app/Views/service-requests/edit.php
    - app/Views/service-requests/show.php
    - public/assets/css/app.css
  - Tests run:
    - PHP syntax check across all PHP files.
    - Rendered intake edit page.
    - Updated intake data.
    - Rendered service request edit page.
    - Updated service request details.
    - Confirmed linked customer, location, and vehicle basics update.
    - Confirmed service request detail edits create audit log entry.
    - Confirmed service request edit validation.
    - Cleaned test records afterward.
  - Notes:
    - Converted intake records redirect back to the intake detail page instead of editing the already-converted lead.

- [x] Added catalog migration and catalog screens
  - Files changed:
    - database/migrations/202605230002_create_catalog_items_table.php
    - app/Controllers/CatalogController.php
    - app/Models/CatalogItem.php
    - app/Views/layouts/app.php
    - app/Views/catalog/index.php
    - app/Views/catalog/form.php
    - public/index.php
    - public/assets/css/app.css
  - Tests run:
    - PHP syntax check across all PHP files.
    - Ran catalog migration.
    - Rendered empty service catalog.
    - Rendered empty parts/materials catalog.
    - Rendered new service form.
    - Created service catalog item.
    - Created part catalog item.
    - Updated part catalog item to inactive.
    - Confirmed service list required columns.
    - Confirmed parts/materials list required columns.
    - Confirmed edit form renders.
    - Confirmed invalid catalog item validation.
    - Cleaned test records afterward.
  - Notes:
    - Visible catalog columns include Name, Part #, Category, Price, Price Type, Taxable, Status, and Short Description.
    - Vehicle Required was intentionally not added as a visible catalog column.

- [x] Added first estimate workflow
  - Files changed:
    - database/migrations/202605230003_create_estimates_tables.php
    - app/Controllers/EstimateController.php
    - app/Models/Estimate.php
    - app/Models/EstimateLineItem.php
    - app/Services/NumberingService.php
    - app/Views/layouts/app.php
    - app/Views/service-requests/show.php
    - app/Views/estimates/index.php
    - app/Views/estimates/new.php
    - app/Views/estimates/show.php
    - public/index.php
  - Tests run:
    - PHP syntax check across all PHP files.
    - Ran estimate migration.
    - Rendered new estimate page from service request.
    - Created draft estimate from service request.
    - Rendered empty estimate with no lines.
    - Added catalog service line.
    - Added catalog material line.
    - Added custom line.
    - Recalculated subtotal, tax, and total server-side.
    - Confirmed approval required indicator over $200.
    - Rendered estimate index.
    - Confirmed invalid estimate line validation.
    - Cleaned test records afterward.
  - Notes:
    - Tax rate is currently a simple internal constant for this first slice.
    - Estimate PDF, send, approve, and decline actions remain for the next estimate slice.

- [x] Added customer approval records and estimate status actions
  - Files changed:
    - database/migrations/202605230004_create_customer_approvals_table.php
    - app/Controllers/EstimateController.php
    - app/Models/CustomerApproval.php
    - app/Models/Estimate.php
    - app/Services/NumberingService.php
    - app/Views/estimates/show.php
    - public/index.php
  - Tests run:
    - PHP syntax check across all PHP files.
    - Confirmed customer_approvals migration is applied.
    - Rendered estimate approval UI.
    - Marked estimate sent.
    - Created estimate approval record.
    - Marked estimate approved with approved_at timestamp.
    - Displayed approval record on estimate page.
    - Logged estimate approval to service request timeline.
    - Confirmed approval validation.
    - Marked estimate declined.
    - Cleaned test records afterward.
  - Notes:
    - Approval methods are sms_link, email_link, phone_confirmed, and onsite_signature.
    - Estimate approval currently records internal approval metadata; public customer links come later.

- [x] Added work order creation and dispatch statuses
  - Files changed:
    - database/migrations/202605230005_create_work_orders_table.php
    - app/Controllers/WorkOrderController.php
    - app/Models/WorkOrder.php
    - app/Services/NumberingService.php
    - app/Views/layouts/app.php
    - app/Views/estimates/show.php
    - app/Views/work-orders/index.php
    - app/Views/work-orders/show.php
    - public/index.php
  - Tests run:
    - PHP syntax check across all PHP files.
    - Ran work order migration.
    - Confirmed approved estimate shows Create Work Order action.
    - Created work order from approved estimate.
    - Confirmed duplicate work order is not created for same estimate.
    - Confirmed work order inherits customer, service request, vehicle, and estimate context.
    - Rendered work order index.
    - Rendered work order detail.
    - Marked work order dispatched.
    - Marked arrived.
    - Marked completed.
    - Confirmed dispatch, arrived, and completed timestamps.
    - Cleaned test records afterward.
  - Notes:
    - Work Order statuses are pending, dispatched, completed, cancelled, and invoiced.
    - Field screen polish and service completion report remain next.

- [x] Added mobile field screen and service completion reports
  - Files changed:
    - database/migrations/202605230006_create_service_completion_reports_table.php
    - app/Controllers/ServiceReportController.php
    - app/Controllers/WorkOrderController.php
    - app/Core/Controller.php
    - app/Models/ServiceCompletionReport.php
    - app/Models/WorkOrder.php
    - app/Services/NumberingService.php
    - app/Views/service-reports/new.php
    - app/Views/service-reports/show.php
    - app/Views/work-orders/field.php
    - app/Views/work-orders/show.php
    - public/assets/css/app.css
    - public/index.php
  - Tests run:
    - PHP syntax check across related PHP files.
    - Ran service completion report migration.
    - Rendered mobile field screen.
    - Rendered new service report form.
    - Rendered service report detail page.
    - Created service report from work order through model.
    - Confirmed duplicate report is not created for same work order.
    - Created service report through POST route.
    - Confirmed VIN is stored back to vehicle.
    - Confirmed work order is marked completed.
    - Confirmed service report creation audit log.
    - Cleaned test records afterward.
  - Notes:
    - Service report requires actual work performed.
    - If a vehicle is linked, VIN must be captured unless no vehicle serviced is checked.

- [x] Added draft invoice generation from service reports
  - Files changed:
    - database/migrations/202605230007_create_invoices_tables.php
    - app/Controllers/InvoiceController.php
    - app/Models/Invoice.php
    - app/Models/InvoiceLineItem.php
    - app/Services/NumberingService.php
    - app/Views/invoices/index.php
    - app/Views/invoices/show.php
    - app/Views/layouts/app.php
    - app/Views/service-reports/show.php
    - public/assets/css/app.css
    - public/index.php
  - Tests run:
    - PHP syntax check across all PHP files.
    - Ran invoice migration.
    - Generated draft invoice from completed service report.
    - Copied estimate line items to invoice line items.
    - Calculated subtotal, tax, total, and balance due server-side.
    - Confirmed duplicate invoice is not created for same service report.
    - Rendered invoice index.
    - Rendered invoice detail.
    - Confirmed no-vehicle-serviced flag allows missing VIN.
    - Confirmed missing VIN is detected when no-vehicle-serviced flag is false.
    - Cleaned test records afterward.
  - Notes:
    - Ledger posting remains future work.

- [x] Added invoice issuing, payments, and receipts
  - Files changed:
    - database/migrations/202605230008_create_payments_receipts_tables.php
    - app/Controllers/InvoiceController.php
    - app/Controllers/PaymentController.php
    - app/Controllers/ReceiptController.php
    - app/Models/Invoice.php
    - app/Models/Payment.php
    - app/Models/Receipt.php
    - app/Services/NumberingService.php
    - app/Views/invoices/show.php
    - app/Views/layouts/app.php
    - app/Views/payments/index.php
    - app/Views/payments/new.php
    - app/Views/payments/show.php
    - app/Views/receipts/show.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed models, controllers, route file, and migration.
    - Ran payment and receipt migration.
    - Issued a draft invoice only after validation passed.
    - Blocked an overpayment.
    - Recorded a completed payment.
    - Updated invoice amount paid, balance due, and paid status.
    - Created a receipt record automatically.
    - Smoke-tested Invoices and Payments screens through the local app server.
    - Cleaned test records afterward.
  - Notes:
    - Payments are manual records for now.
    - Receipt PDF generation remains future work.

- [x] Added basic accounting ledger
  - Files changed:
    - database/migrations/202605230009_create_accounting_tables.php
    - app/Controllers/AccountingController.php
    - app/Controllers/InvoiceController.php
    - app/Controllers/PaymentController.php
    - app/Models/Account.php
    - app/Models/LedgerEntry.php
    - app/Services/AccountingService.php
    - app/Services/NumberingService.php
    - app/Views/accounting/accounts.php
    - app/Views/accounting/ledger.php
    - app/Views/accounting/ledger-entry.php
    - app/Views/layouts/app.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed migration, models, service, controller, and route file.
    - Ran accounting migration.
    - Seeded 23 default chart-of-accounts records.
    - Posted issued invoice to ledger.
    - Posted completed payment to ledger.
    - Confirmed invoice ledger debits equal credits.
    - Confirmed payment ledger debits equal credits.
    - Confirmed duplicate posting returns existing ledger entry.
    - Smoke-tested Chart of Accounts and Ledger screens through the local app server.
    - Cleaned test records afterward.
  - Notes:
    - Invoice posting debits Accounts Receivable and credits revenue plus Sales Tax Payable.
    - Payment posting debits Cash, Checking, or Square Clearing and credits Accounts Receivable.

- [x] Added proof packet and starter reports
  - Files changed:
    - app/Controllers/ReportController.php
    - app/Controllers/ServiceRequestController.php
    - app/Models/Report.php
    - app/Models/ServiceRequest.php
    - app/Views/layouts/app.php
    - app/Views/reports/index.php
    - app/Views/reports/missing-records.php
    - app/Views/reports/payments.php
    - app/Views/reports/revenue.php
    - app/Views/reports/unpaid.php
    - app/Views/service-requests/proof-packet.php
    - app/Views/service-requests/show.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed model, controller, and route files.
    - Created a complete job record through invoice, payment, receipt, and ledger entries.
    - Confirmed complete proof packet has no missing items.
    - Confirmed incomplete proof packet lists missing records.
    - Confirmed report summary calculates from invoices and payments.
    - Confirmed revenue and payments reports return rows.
    - Smoke-tested Reports, Revenue Report, and Jobs Missing Records screens through the local app server.
    - Cleaned test records afterward.
  - Notes:
    - Proof packet is currently a linked on-screen packet, not a generated PDF.
    - Reports currently include dashboard, revenue, payments, unpaid invoices, and jobs missing records.

- [x] Added file attachment records and generated document placeholders
  - Files changed:
    - database/migrations/202605230010_create_files_documents_tables.php
    - app/Controllers/DocumentController.php
    - app/Controllers/EstimateController.php
    - app/Controllers/InvoiceController.php
    - app/Controllers/ReceiptController.php
    - app/Models/FileAttachment.php
    - app/Models/GeneratedDocument.php
    - app/Models/ServiceRequest.php
    - app/Services/NumberingService.php
    - app/Views/estimates/show.php
    - app/Views/invoices/show.php
    - app/Views/receipts/show.php
    - app/Views/service-requests/proof-packet.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed migration, models, controllers, views, and route file.
    - Ran file/document migration.
    - Created estimate PDF placeholder.
    - Created invoice PDF placeholder.
    - Created receipt PDF placeholder.
    - Created proof packet PDF placeholder.
    - Confirmed duplicate document placeholder requests return the existing document.
    - Confirmed proof packet includes linked estimate, invoice, receipt, and proof packet document records.
    - Cleaned test records afterward.
  - Notes:
    - The file_attachments table is ready for uploaded photos, signatures, and real generated PDFs.
    - Generated documents currently track placeholder records only; real PDF rendering remains next.

- [x] Added service report proof uploads
  - Files changed:
    - app/Controllers/ServiceReportController.php
    - app/Models/FileAttachment.php
    - app/Models/ServiceRequest.php
    - app/Services/FileUploadService.php
    - app/Views/service-reports/show.php
    - app/Views/service-requests/proof-packet.php
    - public/index.php
  - Tests run:
    - PHP syntax check on changed service, model, controller, route, and view files.
    - Created a service report attachment record.
    - Confirmed service report attachments list returns the proof file.
    - Confirmed proof packet includes service report attachments.
    - Confirmed proof packet no longer flags photos/signatures as missing when attachment exists.
    - Smoke-tested the app through the local server.
    - Full PHP syntax sweep.
    - Cleaned test records afterward.
  - Notes:
    - Upload form accepts JPG, PNG, WebP, and GIF images up to 10 MB.
    - Stored files are renamed with random filenames under storage/uploads/YYYY/MM to avoid overwrites.

- [x] Generated real estimate, invoice, receipt, and proof packet PDFs
  - Files changed:
    - app/Controllers/DocumentController.php
    - app/Views/estimates/show.php
    - app/Views/invoices/show.php
    - app/Views/receipts/show.php
    - app/Views/service-requests/proof-packet.php
    - public/index.php
    - scripts/seed_pdf_test_job.php
    - scripts/verify_pdf_test.php
    - scripts/cleanup_pdf_test_job.php
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Seeded full job-to-cash record set through model methods.
    - POSTed to /estimates/{id}/documents/generate, /invoices/{id}/documents/generate, /receipts/{id}/documents/generate, and /service-requests/{id}/proof-packet/documents/generate through the foreground PHP server.
    - Confirmed each generated_documents row has status=generated, populated file_attachment_id, mime_type=application/pdf, and non-zero file_size.
    - Confirmed each stored PDF file exists on disk under storage/generated-pdfs/YYYY/MM, begins with %PDF-, and ends with %%EOF.
    - Confirmed four document_generated audit events recorded.
    - Confirmed proof packet view lists the generated documents with links to /documents/{id}/download.
    - Confirmed estimate, invoice, and receipt detail pages each link to their generated PDF.
    - Confirmed GET /documents/{id}/download streams the PDF inline by default and uses Content-Disposition: attachment when ?download=1.
    - Confirmed re-POSTing the generate endpoint is idempotent (no duplicate file_attachments row).
    - Cleaned scoped test records and removed the generated PDF files from disk.
  - Notes:
    - PdfService was already present; the controller now invokes it instead of stopping at placeholder creation.
    - Re-generation is intentionally a no-op if a file_attachment is already linked; this avoids regenerating bytes when nothing changed.
    - Inline streaming uses the local PHP server; production deployments should still serve storage/ through PHP, not directly through the web server, to keep access controls in one place.

- [x] Added Regenerate PDF action and polished document rows
  - Files changed:
    - app/Controllers/DocumentController.php
    - app/Views/estimates/show.php
    - app/Views/invoices/show.php
    - app/Views/receipts/show.php
    - app/Views/service-requests/proof-packet.php
    - public/assets/css/app.css
    - public/index.php
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Seeded a full job-to-cash record set; generated the initial four PDFs through the live HTTP routes.
    - POSTed /documents/{id}/regenerate for each of the four documents.
    - Confirmed each generated_documents row received a new file_attachment_id.
    - Confirmed total file_attachments count stayed at 4 (old attachments retired during regeneration).
    - Confirmed each new PDF file exists on disk and each old PDF file was removed.
    - Confirmed four document_regenerated audit events were recorded.
    - Confirmed estimate, invoice, receipt, and proof packet views render the Regenerate button next to the download link.
    - Cleaned all scoped test records and removed the test PDF files.
  - Notes:
    - Regenerate intentionally replaces the file in place and retires the prior attachment; the audit log preserves the regeneration history.
    - Versioned document history is not implemented yet; the dev plan section 28 numbering scheme allows for it as a future enhancement.

- [x] Polished location display with a shared View::address() helper
  - Files changed:
    - app/Core/View.php
    - app/Controllers/DocumentController.php
    - app/Controllers/ServiceRequestController.php
    - app/Views/customers/show.php
    - app/Views/service-requests/show.php
    - app/Views/service-requests/proof-packet.php
    - app/Views/vehicles/show.php
    - app/Views/work-orders/show.php
    - app/Views/work-orders/field.php
  - Tests run:
    - Rendered service request, proof packet, and work order views through the local PHP server.
    - Confirmed addresses now render as "12 PDF Test Lane, Testville, TX 75001" instead of space-joined strings.
    - PHP syntax sweep across all changed files.
  - Notes:
    - View::address() accepts any associative array with address_line_1, address_line_2, city, state, postal_code and returns a comma-separated US-style address. Falls back to a configurable label when nothing is captured.

- [x] Mobile layout verification for proof packet and reports
  - Files changed:
    - (none; verification only)
  - Tests run:
    - Confirmed the existing 560px breakpoint in public/assets/css/app.css now styles the new .document-row, .document-actions, and .document-actions button rules (gap collapses, action button stretches full width).
    - Confirmed the proof packet HTML at /service-requests/{id}/proof-packet has balanced <div> and <form> tags.
    - Confirmed the reports index HTML at /reports has balanced tags.
  - Notes:
    - I cannot drive a real mobile browser in this sandbox, so this verification is limited to CSS-rule presence and balanced markup. Live mobile browser verification on a real device is still recommended before declaring the proof packet and reports fully mobile-ready.

- [x] Added vendor directory (Sprint 5 step 1)
  - Files changed:
    - database/migrations/202605230011_create_vendors_table.php
    - app/Models/Vendor.php
    - app/Controllers/VendorController.php
    - app/Views/vendors/index.php
    - app/Views/vendors/form.php
    - app/Views/vendors/show.php
    - app/Views/layouts/app.php
    - app/Core/View.php
    - public/index.php
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Ran the vendors migration successfully.
    - Rendered empty vendors index.
    - Rendered new vendor form.
    - Confirmed missing-name validation.
    - Confirmed invalid email validation.
    - Confirmed website-must-start-with-http validation.
    - Created a vendor via POST /vendors and verified the redirect to /vendors/{id}.
    - Confirmed vendor show page renders all stored fields.
    - Confirmed vendor index lists the new vendor.
    - Confirmed edit form pre-fills all fields (after fixing the View::render variable-shadowing bug).
    - Updated the vendor (status -> inactive, notes changed) and confirmed the change shows up on the show page.
    - Confirmed vendor_created and vendor_updated audit events were recorded.
    - Smoke-tested all 14 top-level pages still return 200 after the View::render rename.
    - Cleaned the test vendor row and audit entries afterward.
  - Notes:
    - The vendors table includes a status column ('active' / 'inactive') so inactive suppliers can be hidden from selection later.
    - Fixed a latent shadowing bug in App\Core\View::render: the second positional parameter was named $data, which collided with any caller passing a 'data' key (such as the new vendor form). Renamed it to $__viewData; no view file referenced the parameter name.
    - Vendor documents (uploads, line-item categorization, posting to accounting) remain next.

- [x] Added vendor document upload, review, and line categorization (Sprint 5 steps 2-4)
  - Files changed:
    - database/migrations/202605230012_create_vendor_documents_tables.php
    - app/Models/VendorDocument.php
    - app/Models/VendorDocumentLineItem.php
    - app/Controllers/VendorDocumentController.php
    - app/Services/FileUploadService.php
    - app/Services/NumberingService.php
    - app/Views/vendor-documents/index.php
    - app/Views/vendor-documents/upload.php
    - app/Views/vendor-documents/show.php
    - app/Views/layouts/app.php
    - public/index.php
    - public/assets/css/app.css
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Ran the vendor documents migration successfully.
    - Confirmed VDC numbering prefix is registered in NumberingService.
    - Rendered empty vendor documents index.
    - Rendered upload form.
    - Confirmed missing-file validation.
    - Confirmed tax-cannot-exceed-total validation.
    - Uploaded a vendor receipt (PNG) tied to a seeded vendor and confirmed: 302 redirect to /vendor-documents/{id}, vendor_documents row, file_attachments row, and the file on disk under storage/uploads/YYYY/MM.
    - Added three line items with different categories (resold_part, consumable, meal_personal) and confirmed line totals and reviewed_flag persist.
    - Confirmed line validation (missing item_name -> Required, invalid category -> Choose a category).
    - Marked the document needs_review; confirmed the second invocation is a no-op (still one vendor_document_status_changed audit row).
    - Deleted one line and confirmed the table row went away and recalculate still ran cleanly.
    - Confirmed the audit log captured vendor_document_uploaded and vendor_document_status_changed events.
    - Smoke-tested 15 top-level pages; all returned HTTP 200.
    - Cleaned the test vendor + document + line items + attachment + audit rows, removed the uploaded receipt file, and verified counts returned to the pre-test baseline.
  - Notes:
    - The dev plan section 28 prefix table did not list a vendor document prefix; chose VDC. PTW from the dev plan is reserved for "Purchase To Work" tracking entries that link a purchase to a job, which is a separate concept.
    - FileUploadService now accepts PDF as well as images for file_type=document or receipt_image; existing photo/signature flow is unchanged.
    - The recalculate logic preserves the operator-entered header total whenever it is > 0 and only derives total from line sums when no header total has been recorded. Mismatch between header total and line sum is intentional - the operator may still be filling in lines or there may be vendor fees not itemized. A future polish should surface this mismatch on the show page.

- [x] Vendor document posting to accounting (Sprint 5 step 5)
  - Files changed:
    - app/Services/AccountingService.php
    - app/Controllers/VendorDocumentController.php
    - app/Views/vendor-documents/show.php
    - public/index.php
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Seeded a vendor and uploaded a vendor document (total $42.00, tax $2.00, payment method cash).
    - Added two reviewed lines (Brake Pads resold_part $25, Shop Rags consumable $15) plus one unreviewed line.
    - Marked needs_review and confirmed approve was blocked with "Review all 1 unreviewed line(s)".
    - Deleted the unreviewed line, approved successfully, confirmed status transitioned to approved.
    - Posted to ledger; confirmed status flipped to posted, posted_at recorded, and a balanced ledger entry was created.
    - Inspected the ledger entry: 5000 Parts COGS debit $25.00, 6030 Consumables debit $15.00, 6050 Office/Admin debit $2.00 (sales tax), 1000 Cash credit $42.00. Debits and credits both totaled $42.00.
    - Re-posted the same document; ledger_entries count stayed at 1 (idempotent via findBySource).
    - Verified audit events recorded: vendor_document_uploaded, vendor_document_status_changed, vendor_document_approved, vendor_document_posted.
    - Uploaded a second document with intentional line-sum-vs-total mismatch ($100 total, lines summed to $50). Confirmed approval was blocked with "Line sum $50.00 + tax $0.00 does not match document total $100.00" and the document stayed in needs_review.
    - Confirmed the posted document's show page exposes a View Ledger Entry link to /accounting/ledger/{id}.
    - Confirmed the ledger detail page renders for the vendor document entry.
    - Confirmed the ledger index includes the vendor document entry.
    - Smoke-tested 15 top-level pages; all returned HTTP 200.
    - Cleaned scoped test data: vendor, vendor documents, line items, file attachments, ledger entries and lines, and audit rows.
  - Notes:
    - Category-to-account map: resold_part -> 5000, inventory_part -> 1200, consumable -> 6030, tool_equipment -> 6020, ppe -> 6040, fuel -> 6010, meal_personal -> 6070, office -> 6050, other -> 6050 (fallback).
    - Payment-method-to-credit-account map: cash -> 1000, check -> 1010, card -> 2010, ach -> 1010, unpaid -> 2000, other -> 2000.
    - Sales tax on a vendor purchase is debited to 6050 Office/Admin. A future enhancement could be a dedicated "Sales Tax Paid" account or proportional allocation across line categories.
    - Posting requires (a) document status=approved, (b) all lines reviewed, (c) sum(line_total) + tax_total within $0.01 of header total.
    - Personal/non-business lines (meal_personal) are intentionally routed to 6070 Meals/Personal Non-Business Review, not a generic expense account, matching the dev plan's rule that personal items must be categorized explicitly.

- [x] Sprint 5 closure: gross margin, lead source revenue, tax summary, and proof packet attachment listing
  - Files changed:
    - app/Models/Report.php
    - app/Controllers/ReportController.php
    - app/Views/reports/index.php
    - app/Views/reports/gross-margin.php
    - app/Views/reports/lead-sources.php
    - app/Views/reports/tax-summary.php
    - app/Controllers/DocumentController.php
    - app/Models/ServiceRequest.php
    - public/index.php
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Reports index now exposes Gross Margin, Lead Sources, and Tax Summary quick-action links.
    - Each new report renders HTTP 200 against real existing invoice data: gross margin lists the two invoiced service requests, lead sources lists "Direct" as the source with revenue, tax summary lists the 2026-05 month row.
    - Seeded a full job with a stub photo attachment on the service report, generated the proof packet PDF through POST /service-requests/{id}/proof-packet/documents/generate, and confirmed the PDF body contains a PHOTOS & SIGNATURES section, the attachment's original filename ("before.jpg"), the caption ("Before tire change"), and the "Photo:" label.
    - Smoke-tested 22 top-level pages (including all seven report subpages); every page returned HTTP 200.
    - Cleaned the seeded full job, the proof packet PDF, and the stub photo attachment afterward.
  - Notes:
    - Tax summary aggregates by month in PHP rather than via strftime/DATE_FORMAT, so the query stays portable across SQLite and MySQL drivers.
    - Gross margin treats cost as the sum of posted vendor document lines linked to the service request whose category is resold_part, inventory_part, consumable, or material. Vendor document lines that are not linked to a service request (overhead/general parts) do not flow into any specific job's margin.
    - Proof packet attachment pairs now include the service_request, work_order, service_report, and invoice rows so any photo/signature attached at any phase of the job appears in the packet.
    - The proof packet PDF's missing_items list still treats "Photos or signatures" as missing when none are attached; the new Photos & Signatures section in the PDF body mirrors that.

- [x] Authentication and session management
  - Files changed:
    - database/migrations/202605230013_create_users_table.php
    - app/Models/User.php
    - app/Core/Auth.php
    - app/Controllers/AuthController.php
    - app/Views/layouts/blank.php
    - app/Views/auth/login.php
    - app/Views/auth/setup.php
    - app/Views/layouts/app.php
    - app/Models/AuditLog.php
    - public/index.php
    - public/assets/css/app.css
    - docs/30-delivery/deploy-and-backup.md
  - Tests run:
    - PHP syntax sweep across all PHP files.
    - Ran the users migration successfully.
    - Confirmed unauthenticated /dashboard redirects to /setup when no users exist.
    - Confirmed GET /setup renders the first-run form.
    - Confirmed /setup validation: short password (< 8 chars) and mismatched confirmation both rejected.
    - Confirmed /setup with an invalid email is rejected (FILTER_VALIDATE_EMAIL requires a real TLD).
    - Successful POST /setup created an admin user, signed them in (302 to /dashboard), and stamped audit log with actor_user_id.
    - Confirmed /setup with users existing redirects to /login (no second setup possible).
    - Layout now renders the user-chip with the user name and a Sign out button.
    - Confirmed POST /logout records a user_logged_out audit event with actor_user_id stamped BEFORE the session is destroyed.
    - Confirmed after logout /dashboard redirects to /login.
    - Confirmed bad password and unknown email both return "Invalid email or password" on /login.
    - Confirmed successful login redirects to /dashboard and authenticated access works for dashboard, intake, service-requests, vendors, vendor-documents, reports, and accounting/ledger.
    - Confirmed data-event audit logs (created_from_intake) are stamped with the actor.
    - Cleaned the test admin so the operator gets a fresh /setup on their first visit.
  - Notes:
    - First-run /setup is the only way to create the initial admin; once one user exists, /setup redirects to /login.
    - Sessions use HttpOnly + SameSite=Lax cookies. Cookie Secure flag is off so it works on http://localhost; turn it on at the web server / via config for production https.
    - Auth::user() caches the row per-request; if the user is deactivated mid-session their next request is logged out.
    - There is no /users UI yet for adding additional operators; that is a post-MVP polish item.
    - Password reset is also post-MVP; recovery today is to delete the user row and re-run /setup.

- [x] Versioned document history (PDF revisions instead of in-place replacement)
  - Files changed:
    - database/migrations/202605240002_add_version_to_generated_documents.php (new; idempotent ALTER that adds version INTEGER NOT NULL DEFAULT 1 and superseded_at DATETIME NULL via inspecting current columns first so re-runs are safe on both SQLite and MySQL)
    - app/Models/GeneratedDocument.php (added createNextVersion(existingRow) and markSuperseded(id); extracted insertRow() helper so createPlaceholder and createNextVersion share insert logic; findExisting() now scopes to superseded_at IS NULL so the idempotent initial-generate path keeps deduping on the CURRENT version even after older ones have been retired)
    - app/Controllers/DocumentController.php (regenerate() now: markSuperseded on the old row, createNextVersion to mint a fresh row, PdfService::generate writes a NEW file under the new doc id, attachFile binds the new file_attachments row to the new doc row; the audit log records both old and new {document_id, version, file_attachment_id}; removed retireOldAttachment() helper which was the in-place destroyer)
    - app/Views/partials/document_list.php (new shared partial that renders the four document-list panels; per-row label is "vN - Current" or "vN - Superseded <timestamp>"; Regenerate button only renders on current rows)
    - app/Views/estimates/show.php, invoices/show.php, receipts/show.php, service-requests/proof-packet.php (each swapped its inline 20-line document-list block for one View::render call to the new partial)
    - public/assets/css/app.css (added .document-row-superseded { opacity: .55 } and strike-through on title so prior versions are visually dimmer than the current one)
    - scripts/_smoke_doc_versioning.php (new; 20 model-layer + filesystem assertions covering version=1 on first generate, supersede flag on regen, file persistence across regens, version-number increment, idempotency of createPlaceholder selecting the current row not the superseded one, forRelated returns all versions, exactly one current after two regens, v1 download still resolvable; explicit cleanup of generated files + DB rows in FK-safe order)
  - Tests run:
    - PHP syntax sweep across 8 modified/new files; all clean.
    - scripts/_smoke_doc_versioning.php: 20/20 passed.
    - HTTP end-to-end against php -S: initial POST .../documents/generate created v1; idempotent re-POST still 1 row; POST .../regenerate created v2 with v1 superseded but its file still on disk; second regen on v2 created v3 with v1 + v2 both superseded; exactly 1 current row; GET /documents/{v1_id}/download returned 200 application/pdf 1489 bytes with %PDF-1.4 magic; estimate show page rendered "v3 - Current / v2 - Superseded / v1 - Superseded".
  - Notes:
    - Old PDFs are intentionally kept on disk indefinitely. If a future operator wants a retention/pruning policy (e.g. drop versions older than N days when a newer one exists), that goes on top of this without schema changes -- just a script that walks rows where superseded_at < cutoff and unlinks the file + drops the file_attachments row.
    - The /documents/{id}/download route is keyed by the GeneratedDocument id, so any historical version is still retrievable by anyone who saved its URL -- including customers who clicked a link from an earlier text/email. This is the actual product motivation for versioning (Phase 17 sent customer-facing links that pointed at specific document IDs).
    - Initial generate (handleGenerate path) is unchanged in behavior: still dedups on the current row and skips the file write if one already exists. Only the regenerate path changed.
    - The shared partial collapsed roughly 80 lines of duplicated view markup into one 35-line file, so any future tweak to how versions are displayed is a single edit.

- [x] Phase 17 slice 2: tokenized invoice view, status page, location confirmation
  - Files changed:
    - app/Controllers/PublicInvoiceController.php (new; GET /p/invoice/{token} renders read-only invoice; uses CustomerLinkToken::lookup for type='invoice', purpose='invoice_view')
    - app/Controllers/PublicStatusController.php (new; GET /p/status/{token}; whitelists a small allowlist of audit_log actions and rewrites each one to a customer-friendly label like "Job opened", "Estimate approved", "Technician arrived" -- internal-only actions like details_updated are silently filtered out)
    - app/Controllers/PublicLocationController.php (new; GET shows current address, POST /confirm just stamps used_at + writes 'location_confirmed' audit log, POST /update validates street+city, calls Location::updateBasic, writes 'location_updated' audit log with before/after snapshots, stamps used_at; transactional)
    - app/Views/public/invoice.php (new; read-only invoice with line items, totals, balance-due section, "contact operator" pay note when balance>0; "paid in full" when balance==0)
    - app/Views/public/status.php (new; SR header + timeline of friendly labels; "Check back soon" when no events match the allowlist)
    - app/Views/public/location.php (new; current address card + two separate forms -- Confirm Address (one-tap) and Update Address (street/city/state/zip with per-field error rendering); old/new values repopulate on validation failure)
    - app/Controllers/InvoiceController.php (added mintPublicLink action; show() now passes the latest invoice-view token to the view; 90-day expiry, reusable; audit-log records only last 8 chars of token)
    - app/Controllers/ServiceRequestController.php (added mintStatusLink + mintLocationLink; show() now passes both tokens; status link is reusable + 30-day, location is single-use + 7-day; audit-log records only last 8 chars of token)
    - app/Views/invoices/show.php (added "Public Invoice Link" panel with generate/regen + copyable URL + expiry indicator)
    - app/Views/service-requests/show.php (added a new detail-grid row with two side-by-side panels: Status Link and Location Confirmation Link; location panel shows "Used at ..." instead of the URL once consumed)
    - public/index.php (registered GET /p/invoice/{token}, GET /p/status/{token}, GET /p/location/{token}, POST /p/location/{token}/confirm, POST /p/location/{token}/update; plus operator-side POST /invoices/{id}/public-link, POST /service-requests/{id}/status-link, POST /service-requests/{id}/location-link)
    - scripts/_smoke_portal_full.php (new; 12 model-layer assertions covering type/purpose isolation, single_use vs reusable, markUsed behavior, latestForRelated ordering)
    - scripts/_smoke_portal_http.php (new; 32 live-HTTP assertions against php -S; seeds full SR->invoice chain, mints all three links, hits each portal page unauthenticated, exercises confirm/update/validation/garbage-token/cross-purpose-reuse paths, and verifies the DB side effects)
  - Tests run:
    - PHP syntax check across all 11 modified/new files; all clean.
    - scripts/_smoke_portal_full.php: 12/12 passed.
    - scripts/_smoke_portal_http.php against php -S 127.0.0.1:9881: 32/32 passed. Covered: each portal page returns 200 unauthenticated; invoice portal shows Balance due + service text + does NOT leak operator widgets (Sign out, Issue Invoice, Generate PDF); status portal renders Job opened + does NOT leak details_updated or Sign out; location portal shows current address + Confirm + Update; location update POST flips DB row and shows flash; location reuse after single-use returns 404 invalid-link page; blank address re-renders the form with "Street address is required"; confirm-without-update branch also stamps used_at; garbage tokens 404 on each portal; cross-purpose token reuse (invoice token on /p/status, status token on /p/invoice) returns 404; /p/ paths return 404 not 302 to /login.
  - Notes:
    - Invoice and status tokens are reusable (single_use=0) since the customer might re-open the link several times. Location is single-use because confirming/updating an address is a one-time act -- if they need to change again the operator generates a fresh link.
    - Status portal action allowlist lives in PublicStatusController::PUBLIC_TIMELINE_ACTIONS. Anything not on the list (details_updated, customer_link_minted, internal status churn) is invisible to the customer. Adding a new public action is one line.
    - Location update writes an audit log with before/after snapshots so the operator can see exactly what the customer changed.
    - The invoice "how to pay" section is intentionally a contact-the-operator copy; actual online payment is Phase 19 territory and would slot in cleanly as a new panel on the same view when a payment provider is wired.
    - Sticky-actions/empty-state polish for the public layout was deliberately not bolted on -- the customer pages are short single-column views and don't need it; the existing public CSS handles the small phone widths via the inherited responsive rules.

- [x] Phase 17 slice 1: tokenized estimate approval (customer portal)
  - Files changed:
    - database/migrations/202605240001_create_customer_link_tokens_table.php (new; token, related_type, related_id, purpose, single_use, expires_at, used_at, created_by, indexed on related_type+related_id)
    - app/Models/CustomerLinkToken.php (mint via random_bytes(24) -> 48 hex chars; lookup validates type/purpose/expiry/single-use; markUsed; latestForRelated)
    - app/Core/Auth.php (added PUBLIC_PREFIXES = ['/p/']; isPublicPath now accepts both exact paths and prefix matches)
    - app/Controllers/PublicEstimateController.php (new; show/approve/decline routes; writes CustomerApproval row via the existing model with method='sms_link', flips estimate status, stamps audit log with via='customer_portal', marks token used)
    - app/Controllers/EstimateController.php (added mintPublicLink action; show() now passes the latest token row into the view; AuditLog records only the last 8 chars of the token for privacy)
    - app/Views/layouts/public.php (new; minimal layout, noindex meta, no operator nav)
    - app/Views/public/estimate.php (new; read-only estimate view with Approve form requiring name + agreement checkbox, and a separate Decline POST form with confirm())
    - app/Views/public/invalid.php (new; rendered when token is missing/expired/used/wrong type/purpose)
    - app/Views/estimates/show.php (new "Public Approval Link" aside panel; shows generate button when no token, copyable URL when one exists, status line with expiry/used markers, and a "Generate New Link" button to mint a fresh one)
    - public/index.php (registered PublicEstimateController GET /p/estimate/{token}, POST /p/estimate/{token}/approve, POST /p/estimate/{token}/decline, and POST /estimates/{id}/public-link for operator-side minting)
    - public/assets/css/app.css (added .public-body, .public-shell, .public-header, .public-content, .public-totals, .public-flash, .public-footer, .inline-check styles)
    - scripts/_smoke_customer_portal.php (new; 11 transactional model-level assertions)
  - Tests run:
    - PHP syntax check across all 10 modified/new files; all clean.
    - scripts/_smoke_customer_portal.php: 11/11 passed -- mint returns 48-char hex; valid lookup returns row; wrong related_type rejected; wrong purpose rejected; non-hex token rejected; single-use token rejected after markUsed; expired token rejected; reusable token survives markUsed; latestForRelated returns newest; mint is unique.
    - HTTP end-to-end against php -S: operator logged in, POST /estimates/{id}/public-link returned 302, GET /estimates/{id} rendered the new panel and embedded the /p/estimate/{token} URL; unauthenticated GET /p/estimate/{token} returned 200 with the approval form and customer-facing service text; unauthenticated POST .../approve flipped status draft->approved, stamped approved_at on estimate and used_at on token, and created a CustomerApproval row with method=sms_link and the name from the form; second unauthenticated GET returned 404 invalid-link page; garbage token returned 404 invalid-link page; /p/ paths bypass the auth guard (do not redirect to /login).
  - Notes:
    - Tokens are 48-char lowercase hex (24 random bytes). Single-use by default; estimates use 30-day expiry. Reusable tokens (e.g. future status pages) survive markUsed.
    - Token character set is strictly [a-f0-9] and is validated at lookup time, so attacks like /p/estimate/'; DROP fail the regex before they touch the DB. PDO bound parameters protect the actual lookup query too.
    - Operator-side audit log only records the last 8 chars of the token (token_suffix) so the full secret never lives in the log.
    - Approval method is hard-coded to 'sms_link' for portal-driven approvals -- this matches the existing CustomerApproval::METHODS list and lets the proof packet treat them as customer-initiated.
    - Layout is intentionally separate (layouts/public.php) so the customer never sees operator nav, dashboard, or other customers' data. noindex/nofollow meta is set.
    - Remaining Phase 17 sub-tasks (invoice view, status page, location confirmation) reuse the same tokens table and /p/ prefix -- next slice will add PublicInvoiceController, PublicStatusController, PublicLocationController under that pattern.

- [x] Phase 20 polish slice: list search + sticky field actions on phones
  - Files changed:
    - app/Core/Controller.php (added query() helper for reading trimmed $_GET strings)
    - app/Models/Customer.php (added search(); all() now delegates)
    - app/Models/Vehicle.php (added search() covering VIN, plate, make/model/year/color, customer name/phone)
    - app/Models/ServiceRequest.php (added search() covering SR number, requested service, status, customer name/phone)
    - app/Models/Invoice.php (added search() covering invoice/SR number, status, customer)
    - app/Models/Vendor.php (added search() covering name, phone, email, address, status)
    - app/Models/WorkOrder.php (added search() covering WO/SR/estimate number, service, status, customer)
    - app/Models/Estimate.php (added search() covering estimate/SR number, service, status, customer)
    - app/Controllers/CustomerController.php, VehicleController.php, ServiceRequestController.php, InvoiceController.php, VendorController.php, WorkOrderController.php, EstimateController.php (index() reads ?q= and calls $model->search($q))
    - app/Views/customers/index.php, vehicles/index.php, service-requests/index.php, invoices/index.php, vendors/index.php, work-orders/index.php, estimates/index.php (added .list-search form, "X matching" counter, and a search-specific empty state separate from the no-records-yet state)
    - public/assets/css/app.css (added .list-search styles; on phones field-actions now re-pins to bottom with backdrop blur + shadow instead of dropping to position:static)
    - scripts/_smoke_search.php (new transactional smoke test; seeds two customers/vehicles/SRs + estimate + vendor, runs 20 search assertions, rolls back)
  - Tests run:
    - PHP syntax check across all 22 modified files; all clean.
    - scripts/_smoke_search.php passed all 20 assertions covering empty-query=all, name/phone/VIN/plate/make hits, status and number-prefix hits, and "no match" zero-row cases.
    - Started php -S, logged in, requested /customers, /customers?q=test, /vehicles?q=Honda, /service-requests?q=pending, /invoices?q=foo, /vendors, /work-orders?q=x, /estimates?q=z, /customers?q= ; all returned 200 with the .list-search form rendered, the matching/no-match empty states correct, and an empty ?q= falling through to the original no-records-yet empty state.
    - Confirmed search SQL is portable (no SQLite-only operators) by avoiding || concat in WHERE clauses; bound parameters use a single :q placeholder with '%' . $q . '%' so PDO escaping handles SQL injection.
  - Notes:
    - Search is per-list, GET-based, and stateless — bookmarkable URLs and back/forward all work.
    - Wildcards in user input (%, _) match literally; acceptable trade-off for a solo-operator MVP.
    - Validation messages and empty states were already in place from prior sprints; this slice intentionally left them alone.
    - Sticky show-page actions (estimates/invoices/service-requests/show) were considered and skipped — those layouts already collapse cleanly on a phone, and the work-order /field screen is the real "operator at the curb" target.

- [x] Source control initialized (git init + first commit)
  - Files changed:
    - .gitignore (extended to cover storage/uploads, storage/generated-pdfs, /storage/*.pid, /.claude/, OS noise, and the reserved /vendor/ path)
  - Tests run:
    - Confirmed `git check-ignore` rejects .env, storage/app.sqlite, storage/uploads, storage/generated-pdfs, and .claude.
    - Confirmed `git diff --cached --name-only | grep -iE '\.env$|\.sqlite|storage/uploads|storage/generated-pdfs|\.claude'` matched nothing before committing.
    - Created initial commit 1cfc706 with 140 tracked files; working tree clean.
  - Notes:
    - Repository is local only; no remote configured yet. Add an origin when the operator picks a host (GitHub, GitLab, self-hosted).
    - Storage subdirectories (uploads/, generated-pdfs/) are intentionally not tracked; PdfService and FileUploadService mkdir them on first write.

## In Progress

- [ ] MySQL production validation + real-device mobile pass
  - Files being changed:
    - docs/30-delivery/test-plan.md (will append MySQL verification steps after a real run)
  - Expected result:
    - MySQL deploy path validated end-to-end against a real database with the operator's credentials.
    - Field screen and proof packet verified on a real phone, not just CSS rule presence.
  - Test plan:
    - Set DB_DSN/DB_USER/DB_PASS for a real MySQL instance, load a single page so migrations run, then re-run scripts/seed_pdf_test_job.php, scripts/verify_pdf_test.php, and scripts/cleanup_pdf_test_job.php and confirm clean results.
    - Open /work-orders/{id}/field and /service-requests/{id}/proof-packet on a real phone and confirm the layout, sticky actions, and download links behave.
  - Blocked on:
    - MySQL DSN/credentials from the operator.
    - Operator access to a real mobile device.

## Next Up

- [x] Phase 17 customer portal: tokenized estimate approval, invoice view, status page, and location confirmation links (all four shipped 2026-05-24).
- [ ] Phase 18 notifications: SMS/email at lifecycle events (estimate sent, en route, arrived, payment received, etc.) with editable templates and delivery status.
- [ ] Phase 19 integrations: Square / Stripe payments, Telnyx SMS, Google Maps + address autocomplete, VIN decode API, QuickBooks export.
- [ ] Phase 20 polish and hardening: empty states (done prior sprints), validation messages (done prior sprints), sticky mobile field actions (done 2026-05-24), faster search (done 2026-05-24). Remaining: revisit if real-device testing surfaces new issues.
- [x] Implement versioned document history (PDF revisions instead of in-place replacement) -- done 2026-05-24.
- [ ] Live mobile browser verification on a real device for proof packet, work order field screen, and reports.
- [ ] Optional polish: surface header-vs-line-sum mismatch on vendor document show page.
- [ ] Optional polish: dedicated "Sales Tax Paid" account or proportional allocation across line categories instead of routing vendor sales tax to 6050 Office/Admin.

## Blockers

- Blocker: No existing application code was present.
  - Impact: Login and existing flow regression checks remain not applicable.
  - Proposed solution: Continue building the greenfield foundation in small tested steps.
  - Status (2026-05-23): Resolved. Auth and the full job-to-cash workflow are now in place.

- Blocker: Database credentials are not defined.
  - Impact: MySQL-specific execution cannot be verified yet.
  - Proposed solution: Use local SQLite for development tests and configure DB_DSN for MySQL deployment testing.

- Blocker: Detached PHP development server launch is restricted in this sandbox.
  - Impact: Long-running local browser review needs a foreground server during the test window.
  - Proposed solution: Continue using a foreground PHP server for smoke tests, or run the server from an interactive terminal for extended review.

## Decisions Made

- Decision: Treat the current workspace as a greenfield project.
  - Reason: The project folder was empty and not a Git repository.
  - Date: 2026-05-23

- Decision: Start with documentation before app scaffolding.
  - Reason: The development plan requires a living TODO and project docs as Phase 0 output.
  - Date: 2026-05-23

- Decision: Keep the product scope centered on solo roadside job-to-cash workflow.
  - Reason: The brief explicitly excludes towing fleet, ERP, impound, auction, and enterprise dispatch use cases.
  - Date: 2026-05-23

- Decision: Use SQLite as the local default when DB_DSN is not set.
  - Reason: No MySQL credentials are available yet, and the app needs immediate testability.
  - Date: 2026-05-23

- Decision: Keep code compatible with PHP 8.0.30 locally.
  - Reason: The local machine has PHP 8.0.30 installed, while PHP 8.2+ remains the deployment target.
  - Date: 2026-05-23

## Test Log

- Date: 2026-05-23
  - Test: Inspect project root contents.
  - Result: Passed.
  - Notes: No files or folders were present before documentation setup.

- Date: 2026-05-23
  - Test: Check Git repository status.
  - Result: Not applicable.
  - Notes: The workspace is not currently a Git repository.

- Date: 2026-05-23
  - Test: Create Phase 0 documentation files.
  - Result: Passed.
  - Notes: Required and recommended documentation files were added.

- Date: 2026-05-23
  - Test: PHP syntax check across all PHP files.
  - Result: Passed.
  - Notes: No syntax errors detected.

- Date: 2026-05-23
  - Test: Run database migrations.
  - Result: Passed.
  - Notes: Sprint 1 local SQLite tables were created.

- Date: 2026-05-23
  - Test: Intake to service request workflow.
  - Result: Passed.
  - Notes: Intake was created, converted to pending service request, duplicate customer was reused by phone, vehicle/location were preserved, and audit timeline was recorded.

- Date: 2026-05-23
  - Test: Render dashboard, intake, and service request routes through PHP.
  - Result: Passed.
  - Notes: Route output included expected page content.

- Date: 2026-05-23
  - Test: Invalid intake validation.
  - Result: Passed.
  - Notes: Missing required fields and bad phone format return validation messages.

- Date: 2026-05-23
  - Test: Local PHP server browser verification.
  - Result: Blocked.
  - Notes: The PHP built-in server did not stay reachable from this sandbox; no background server was left running.

- Date: 2026-05-23
  - Test: Retry local PHP server browser verification.
  - Result: Passed with foreground server.
  - Notes: Running php -S 127.0.0.1:8080 -t public in the foreground served /dashboard with HTTP 200. Detached background launch remains unreliable in this sandbox.

- Date: 2026-05-23
  - Test: Clean test data after workflow verification.
  - Result: Passed.
  - Notes: Test records were removed; clean dashboard render still passes.

- Date: 2026-05-23
  - Test: CSS asset path render.
  - Result: Passed.
  - Notes: The app now renders stylesheet paths correctly for both public web-root and /public/index.php access.

- Date: 2026-05-23
  - Test: Direct service request workflow.
  - Result: Passed.
  - Notes: Created a service request directly, linked customer/location/vehicle, changed status to accepted, and confirmed audit timeline entries.

- Date: 2026-05-23
  - Test: New service request form validation.
  - Result: Passed.
  - Notes: Missing required fields, bad phone format, and invalid priority return validation messages.

- Date: 2026-05-23
  - Test: Clean test data after direct service request verification.
  - Result: Passed.
  - Notes: Customer, intake, and service request counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Customer and vehicle pages.
  - Result: Passed.
  - Notes: Empty states, list pages, detail pages, and service history records render correctly.

- Date: 2026-05-23
  - Test: Dashboard latest-record panels.
  - Result: Passed.
  - Notes: Empty state and populated latest intake/service request panels render correctly.

- Date: 2026-05-23
  - Test: Clean test data after dashboard/customer/vehicle verification.
  - Result: Passed.
  - Notes: Customer, intake, service request, vehicle, location, and audit log counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Intake edit flow.
  - Result: Passed.
  - Notes: Intake edit page renders and updates caller, service, location, vehicle, lead source, and notes.

- Date: 2026-05-23
  - Test: Service request edit flow.
  - Result: Passed.
  - Notes: Service request edit updates service details plus linked customer, location, and vehicle basics.

- Date: 2026-05-23
  - Test: Service request edit audit log.
  - Result: Passed.
  - Notes: Detail edits create a details_updated audit event.

- Date: 2026-05-23
  - Test: Service request edit validation.
  - Result: Passed.
  - Notes: Missing required fields, bad phone format, and invalid priority return validation messages.

- Date: 2026-05-23
  - Test: Clean test data after edit verification.
  - Result: Passed.
  - Notes: Customer, intake, service request, vehicle, location, and audit log counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Catalog migration.
  - Result: Passed.
  - Notes: catalog_items table migration applied successfully.

- Date: 2026-05-23
  - Test: Catalog screens and validation.
  - Result: Passed.
  - Notes: Empty states, new service form, service list, parts/materials list, edit form, item updates, and invalid input validation passed.

- Date: 2026-05-23
  - Test: Clean test data after catalog verification.
  - Result: Passed.
  - Notes: catalog_items, customers, intakes, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Estimate migration.
  - Result: Passed.
  - Notes: estimates and estimate_line_items tables were created.

- Date: 2026-05-23
  - Test: Estimate creation and line items.
  - Result: Passed.
  - Notes: Draft estimate from service request, catalog lines, custom lines, totals, estimate index, and validation passed.

- Date: 2026-05-23
  - Test: Estimate approval-required indicator.
  - Result: Passed.
  - Notes: Estimate total over $200 displayed Approval Required.

- Date: 2026-05-23
  - Test: Clean test data after estimate verification.
  - Result: Passed.
  - Notes: estimate_line_items, estimates, catalog_items, customers, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Customer approval migration.
  - Result: Passed.
  - Notes: customer_approvals table migration is applied.

- Date: 2026-05-23
  - Test: Estimate status and approval workflow.
  - Result: Passed.
  - Notes: Sent, approved, declined, approval record display, and approval validation passed.

- Date: 2026-05-23
  - Test: Service request timeline receives estimate approval.
  - Result: Passed.
  - Notes: estimate_approved audit event appears on the related service request timeline.

- Date: 2026-05-23
  - Test: Clean test data after approval verification.
  - Result: Passed.
  - Notes: customer_approvals, estimate_line_items, estimates, audit_logs, customers, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Work order migration.
  - Result: Passed.
  - Notes: work_orders table was created.

- Date: 2026-05-23
  - Test: Work order creation from approved estimate.
  - Result: Passed.
  - Notes: Work order was created with WOR numbering and inherited service request, customer, vehicle, and estimate context.

- Date: 2026-05-23
  - Test: Work order dispatch statuses.
  - Result: Passed.
  - Notes: Dispatched, arrived, and completed actions set the expected timestamps.

- Date: 2026-05-23
  - Test: Clean test data after work order verification.
  - Result: Passed.
  - Notes: work_orders, estimate_line_items, estimates, audit_logs, customers, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Service completion report migration.
  - Result: Passed.
  - Notes: service_completion_reports table was created.

- Date: 2026-05-23
  - Test: Field and service report pages.
  - Result: Passed.
  - Notes: Field screen, new report form, and report detail page render.

- Date: 2026-05-23
  - Test: Service report creation.
  - Result: Passed.
  - Notes: Service report can be created from work order, avoids duplicate report for same work order, and preserves work order context.

- Date: 2026-05-23
  - Test: Service report POST route.
  - Result: Passed.
  - Notes: POST route creates report, updates vehicle VIN, marks work order completed, and logs service_report_created.

- Date: 2026-05-23
  - Test: Clean test data after service report verification.
  - Result: Passed.
  - Notes: service_completion_reports, work_orders, estimates, audit_logs, customers, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Invoice migration.
  - Result: Passed.
  - Notes: invoices and invoice_line_items tables were created.

- Date: 2026-05-23
  - Test: Draft invoice generation from service report.
  - Result: Passed.
  - Notes: Invoice was created from completed service report, estimate lines were copied, totals calculated, and duplicate invoice was prevented.

- Date: 2026-05-23
  - Test: Invoice VIN validation.
  - Result: Passed.
  - Notes: Missing VIN is allowed when no_vehicle_serviced_flag is true and detected when false.

- Date: 2026-05-23
  - Test: Clean test data after invoice verification.
  - Result: Passed.
  - Notes: invoice_line_items, invoices, service_completion_reports, work_orders, estimates, customers, and service_requests counts are zero after cleanup.

- Date: 2026-05-23
  - Test: Payment and receipt migration.
  - Result: Passed.
  - Notes: payments and receipts tables were created.

- Date: 2026-05-23
  - Test: Invoice issue and completed payment flow.
  - Result: Passed.
  - Notes: Draft invoice issued successfully, full payment marked the invoice paid, balance due became zero, and receipt was created.

- Date: 2026-05-23
  - Test: Payment validation.
  - Result: Passed.
  - Notes: Overpayment was blocked before recording payment.

- Date: 2026-05-23
  - Test: Invoice and payment page smoke test.
  - Result: Passed.
  - Notes: Invoices and Payments screens loaded through the local app server.

- Date: 2026-05-23
  - Test: Clean test data after payment verification.
  - Result: Passed.
  - Notes: Temporary customer, vehicle, service request, work order, report, invoice, payment, and receipt records were removed after verification.

- Date: 2026-05-23
  - Test: Accounting migration.
  - Result: Passed.
  - Notes: accounting_accounts, ledger_entries, and ledger_entry_lines tables were created.

- Date: 2026-05-23
  - Test: Default chart of accounts seed.
  - Result: Passed.
  - Notes: 23 default accounts are present.

- Date: 2026-05-23
  - Test: Invoice ledger posting.
  - Result: Passed.
  - Notes: Issued invoice posted to a balanced ledger entry with Accounts Receivable, revenue, and Sales Tax Payable lines.

- Date: 2026-05-23
  - Test: Payment ledger posting.
  - Result: Passed.
  - Notes: Completed payment posted to a balanced ledger entry with cash/clearing and Accounts Receivable lines.

- Date: 2026-05-23
  - Test: Duplicate posting protection.
  - Result: Passed.
  - Notes: Reposting the same invoice or payment returned the existing ledger entry instead of creating a duplicate.

- Date: 2026-05-23
  - Test: Accounting page smoke test.
  - Result: Passed.
  - Notes: Chart of Accounts and Ledger screens loaded through the local app server.

- Date: 2026-05-23
  - Test: Proof packet completeness.
  - Result: Passed.
  - Notes: Complete job record showed no missing proof items and included invoice, payment, receipt, and two ledger entries.

- Date: 2026-05-23
  - Test: Proof packet missing-item detection.
  - Result: Passed.
  - Notes: Incomplete service request listed missing estimate, approval, work order, service report, invoice, payment, receipt, accounting entries, and VIN.

- Date: 2026-05-23
  - Test: Starter reports.
  - Result: Passed.
  - Notes: Report summary, revenue rows, payment rows, unpaid invoices, and missing-record jobs were calculated from local data.

- Date: 2026-05-23
  - Test: Report page smoke test.
  - Result: Passed.
  - Notes: Reports, Revenue Report, and Jobs Missing Records screens loaded through the local app server.

- Date: 2026-05-23
  - Test: File/document migration.
  - Result: Passed.
  - Notes: file_attachments and generated_documents tables were created.

- Date: 2026-05-23
  - Test: Generated document placeholders.
  - Result: Passed.
  - Notes: Estimate, invoice, receipt, and proof packet PDF placeholder records were created.

- Date: 2026-05-23
  - Test: Duplicate document placeholder prevention.
  - Result: Passed.
  - Notes: Recreating the same estimate PDF placeholder returned the existing document record.

- Date: 2026-05-23
  - Test: Proof packet document references.
  - Result: Passed.
  - Notes: Proof packet included linked estimate, invoice, receipt, and proof packet document records.

- Date: 2026-05-23
  - Test: Full PHP syntax sweep after document work.
  - Result: Passed.
  - Notes: All PHP files passed syntax checks.

- Date: 2026-05-23
  - Test: Service report attachment metadata.
  - Result: Passed.
  - Notes: Service report photo attachment record was created and returned by the attachment list.

- Date: 2026-05-23
  - Test: Proof packet attachment references.
  - Result: Passed.
  - Notes: Proof packet included service report attachment and did not flag photos/signatures as missing.

- Date: 2026-05-23
  - Test: Full PHP syntax sweep after upload work.
  - Result: Passed.
  - Notes: All PHP files passed syntax checks.

- Date: 2026-05-23
  - Test: Full PHP syntax sweep after PDF rendering work.
  - Result: Passed.
  - Notes: All PHP files under app/, public/, scripts/, and database/ passed syntax checks.

- Date: 2026-05-23
  - Test: Generate estimate, invoice, receipt, and proof packet PDFs through the live HTTP routes.
  - Result: Passed.
  - Notes: Foreground PHP server served four 302 redirects, generated_documents rows flipped to status=generated with populated file_attachment_id, and four PDF files were created under storage/generated-pdfs.

- Date: 2026-05-23
  - Test: PDF file format sanity.
  - Result: Passed.
  - Notes: All four generated files begin with %PDF- and end with %%EOF.

- Date: 2026-05-23
  - Test: Document download endpoint.
  - Result: Passed.
  - Notes: GET /documents/{id}/download returns 200 with Content-Type application/pdf, inline disposition by default, and attachment disposition when ?download=1 is set.

- Date: 2026-05-23
  - Test: Document generation idempotency.
  - Result: Passed.
  - Notes: Re-POSTing /estimates/{id}/documents/generate left the file_attachments count unchanged.

- Date: 2026-05-23
  - Test: View links to generated PDFs.
  - Result: Passed.
  - Notes: Estimate, invoice, receipt, and proof packet views each render a /documents/{id}/download link when a generated PDF is attached.

- Date: 2026-05-23
  - Test: Audit log for PDF generation.
  - Result: Passed.
  - Notes: Four document_generated audit events were recorded for the seeded service request, estimate, invoice, and receipt.

- Date: 2026-05-23
  - Test: Clean test data after PDF rendering verification.
  - Result: Passed.
  - Notes: Scoped cleanup removed only the seeded test rows and the four generated PDF files; pre-existing customer/vehicle/location/estimate/invoice counts remained intact.

- Date: 2026-05-23
  - Test: Document regenerate flow.
  - Result: Passed.
  - Notes: Re-seeded a full job and POSTed /documents/{id}/regenerate for each generated PDF. Each generated_documents row received a new file_attachment_id, the prior PDF files were removed from disk, total file_attachments count stayed at four, and four document_regenerated audit events were recorded.

- Date: 2026-05-23
  - Test: Regenerate UI on document rows.
  - Result: Passed.
  - Notes: Estimate, invoice, receipt, and proof packet views each show a Regenerate button alongside the download link when a file is attached, and only the placeholder rendering when none exists yet.

- Date: 2026-05-23
  - Test: Polished location display.
  - Result: Passed.
  - Notes: Service request, proof packet, work order, work order field, customer detail, and vehicle detail views now render the address with commas via View::address(), and the proof packet PDF picks up the same formatted location.

- Date: 2026-05-23
  - Test: Mobile layout - CSS audit and markup balance.
  - Result: Partial pass (sandbox limitation).
  - Notes: Confirmed the 560px breakpoint styles the new .document-row, .document-actions, and .document-actions button rules and that the proof packet and reports HTML have balanced tags. Live mobile browser verification on a real device is still pending.

- Date: 2026-05-23
  - Test: Clean test data after regenerate verification.
  - Result: Passed.
  - Notes: Scoped cleanup left pre-existing customer/vehicle/location/estimate/invoice counts intact; generated_documents and file_attachments returned to their prior counts.

- Date: 2026-05-23
  - Test: Vendors migration.
  - Result: Passed.
  - Notes: vendors table was created.

- Date: 2026-05-23
  - Test: Vendor CRUD smoke test.
  - Result: Passed.
  - Notes: Empty index, new form, validation errors (missing name, invalid email, malformed website URL), create, show, edit prefill, update with status change, and audit events all passed through the live HTTP server.

- Date: 2026-05-23
  - Test: View::render shadowing fix regression smoke.
  - Result: Passed.
  - Notes: Renamed the View::render second parameter from $data to $__viewData to stop it from masking caller-supplied 'data' keys. Smoke-tested 14 top-level pages; all returned HTTP 200.

- Date: 2026-05-23
  - Test: Clean test data after vendor verification.
  - Result: Passed.
  - Notes: Removed the seeded vendor row and the vendor_created and vendor_updated audit entries; vendors and audit_logs tables returned to zero.

- Date: 2026-05-23
  - Test: Vendor documents migration.
  - Result: Passed.
  - Notes: vendor_documents and vendor_document_line_items tables were created.

- Date: 2026-05-23
  - Test: Vendor document upload, validation, and file persistence.
  - Result: Passed.
  - Notes: PNG file uploaded successfully, vendor_documents row created with VDC numbering, file_attachments row created, and the file is stored under storage/uploads/YYYY/MM. Missing-file and tax-cannot-exceed-total validations returned the form with field errors.

- Date: 2026-05-23
  - Test: Vendor document line item categorization.
  - Result: Passed.
  - Notes: Three lines with different categories (resold_part, consumable, meal_personal) were added; line totals computed (qty * unit_cost); reviewed_flag persisted; required-item-name and unknown-category validations caught bad input.

- Date: 2026-05-23
  - Test: Vendor document status transition and line delete.
  - Result: Passed.
  - Notes: Mark needs_review transitioned the document status and logged vendor_document_status_changed. Second invocation was a no-op as expected. Deleting a line removed it from the table and re-ran recalculate cleanly.

- Date: 2026-05-23
  - Test: Regression smoke after vendor documents wiring.
  - Result: Passed.
  - Notes: All 15 top-level pages still return HTTP 200.

- Date: 2026-05-23
  - Test: Clean test data after vendor document verification.
  - Result: Passed.
  - Notes: Removed vendor, vendor document, line items, file_attachment row, and the receipt file on disk; counts returned to the pre-test baseline.

- Date: 2026-05-23
  - Test: Vendor document approval guardrails.
  - Result: Passed.
  - Notes: Approve was blocked when one line was unreviewed and when sum(line_total) + tax_total did not match header total. Both error messages were surfaced in the show view alert region.

- Date: 2026-05-23
  - Test: Vendor document posting to ledger.
  - Result: Passed.
  - Notes: Approved a $42.00 vendor receipt and posted it. The ledger entry contained four lines: 5000 Parts COGS debit $25.00, 6030 Consumables debit $15.00, 6050 Office/Admin debit $2.00 (sales tax), 1000 Cash credit $42.00. Debits totaled $42.00, credits totaled $42.00.

- Date: 2026-05-23
  - Test: Vendor document posting idempotency.
  - Result: Passed.
  - Notes: Re-POSTing /vendor-documents/{id}/post did not create a second ledger entry (LedgerEntry::findBySource returned the existing entry).

- Date: 2026-05-23
  - Test: Posted vendor document UI integration.
  - Result: Passed.
  - Notes: The show page on a posted document hides the action buttons and renders a View Ledger Entry link pointing at /accounting/ledger/{id}. The ledger detail page renders the entry, and the ledger index lists it.

- Date: 2026-05-23
  - Test: Regression smoke after vendor document posting wiring.
  - Result: Passed.
  - Notes: All 15 top-level pages still return HTTP 200.

- Date: 2026-05-23
  - Test: Clean test data after vendor document posting verification.
  - Result: Passed.
  - Notes: Scoped cleanup removed the seeded vendor, both test vendor documents, their line items, file attachments, ledger entry, ledger lines, and audit rows. Pre-existing customer/vehicle/location/estimate/invoice counts remained intact.

- Date: 2026-05-23
  - Test: Gross margin, lead source revenue, tax summary report render.
  - Result: Passed.
  - Notes: Reports index lists the three new quick-action links; each report returns HTTP 200 and populates rows from real existing invoice data (gross margin lists 2 jobs, lead sources lists Direct, tax summary lists 2026-05).

- Date: 2026-05-23
  - Test: Proof packet PDF includes photos and signatures.
  - Result: Passed.
  - Notes: Seeded a service report with a stub photo attachment, regenerated the proof packet PDF, and inspected the PDF body: PHOTOS & SIGNATURES section present, the attachment filename "before.jpg" appears, the caption "Before tire change" appears, and the "Photo:" label appears.

- Date: 2026-05-23
  - Test: Regression smoke after Sprint 5 closure.
  - Result: Passed.
  - Notes: 22 top-level pages all returned HTTP 200, including the new gross-margin, lead-sources, and tax-summary report pages.

- Date: 2026-05-23
  - Test: Clean test data after Sprint 5 closure verification.
  - Result: Passed.
  - Notes: Scoped cleanup removed the seeded job, the proof packet PDF, and the stub photo attachment.

- Date: 2026-05-23
  - Test: Users migration and auth guard.
  - Result: Passed.
  - Notes: users table created. Unauthenticated requests to protected routes redirect to /setup when no users exist and to /login once at least one user exists.

- Date: 2026-05-23
  - Test: First-run /setup flow.
  - Result: Passed.
  - Notes: Short password, mismatched confirmation, and invalid email were all rejected with field errors. A successful submission created an admin user, signed them in, and stamped the user_created audit event with actor_user_id.

- Date: 2026-05-23
  - Test: Login and logout flow.
  - Result: Passed.
  - Notes: Bad credentials returned "Invalid email or password" without leaking which field was wrong. Successful login redirected to /dashboard. Logout cleared the session and recorded user_logged_out with the actor stamped before session destruction.

- Date: 2026-05-23
  - Test: Audit actor stamping on data events.
  - Result: Passed.
  - Notes: Converting an intake while logged in recorded created_from_intake with actor_user_id=1.

- Date: 2026-05-23
  - Test: Clean test data after auth verification.
  - Result: Passed.
  - Notes: Removed the test intake, the converted service request, the test customer/location, all related audit rows, and the test admin so the operator gets a fresh /setup on their first visit.

- Date: 2026-05-23
  - Test: Git initialized and initial commit.
  - Result: Passed.
  - Notes: `git init -b main`; extended .gitignore to cover storage/uploads, storage/generated-pdfs, /storage/*.pid, /.claude/, OS noise; verified nothing sensitive was staged; created initial commit 1cfc706 with 140 files.
