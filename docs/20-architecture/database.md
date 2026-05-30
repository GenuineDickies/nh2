# Database

## Current State

No existing database files, migrations, schemas, or connection logic were found in the workspace during Phase 0.

## Recommended Foundation

Use MySQL 8+ with PDO from PHP. Add a lightweight migration runner before adding feature tables.

Recommended folders:

- database/migrations
- database/seeds
- app/Core/Database.php

Recommended migration behavior:

- Keep migrations ordered by timestamp or sequence.
- Track applied migrations in a migrations table.
- Run migrations idempotently.
- Avoid destructive changes without an explicit migration and backup plan.

## Sprint 1 Tables

Sprint 1 should create these tables first:

- customers
- vehicles
- locations
- intakes
- service_requests
- audit_logs

## Future Core Tables

The complete workflow will also need:

- catalog_items
- estimates
- estimate_line_items
- customer_approvals
- work_orders
- dispatch_events
- service_completion_reports
- invoices
- invoice_line_items
- payments
- receipts
- vendors
- vendor_documents
- vendor_document_line_items
- accounting_accounts
- ledger_entries
- ledger_entry_lines
- file_attachments

## Important Rules

- Phone number is the main duplicate-detection field for customers.
- A vehicle is only complete when VIN is present.
- Invoice requires VIN unless no_vehicle_serviced_flag is checked.
- Service Request statuses are pending, accepted, completed, cancelled, and rejected.
- Work Order statuses are pending, dispatched, completed, cancelled, and invoiced.
- Estimates do not post to the ledger.
- Draft invoices do not post to the ledger.
- Completed payments create receipts and accounting impact.
- Ledger entries must balance debits and credits.
- Status transitions, approvals, postings, voids, cancellations, and edits to money fields must be audit logged.

## Numbering

Use this document number format:

TYPE-YYYYMMDD-###-V#

Required type examples:

- INT for Intake
- SER for Service Request
- EST for Estimate
- EAP for Estimate Approval
- WOR for Work Order
- DSP for Dispatch Event
- COS for Change of Service
- SCR for Service Completion Report
- INV for Invoice
- PAY for Payment
- RCT for Receipt
- PTW for Proof Packet

Use WOR, not WO. The date is the issue date of version 1. Version increments without changing the original date and sequence.

