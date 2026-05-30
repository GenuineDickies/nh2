# Architecture

## Current State

The workspace has no existing application code. The first implementation step should scaffold a small PHP application.

## Recommended Structure

```text
app/
  Core/
  Controllers/
  Models/
  Services/
  Views/
public/
  index.php
  assets/
database/
  migrations/
  seeds/
storage/
  uploads/
  generated-pdfs/
docs/
```

## Application Pattern

- Route all web requests through public/index.php.
- Use a simple Router class.
- Use controller classes for page and action handling.
- Use model classes for data access around each main table.
- Use service classes for workflow logic, numbering, validation-heavy operations, posting, PDFs, file uploads, and proof packets.
- Use PDO for all database access.
- Use server-side validation for totals, statuses, approvals, and posting.

## UI Pattern

- Mobile-first HTML and CSS.
- Dark professional shell.
- Header, sidebar, and main content area.
- Independent sidebar and main scrolling.
- Button-based navigation.
- Clear active states.
- Practical tables, forms, badges, and action bars.

## Data Flow

Records should move forward through the lifecycle and carry forward the known data:

Intake -> Service Request -> Estimate -> Approval -> Work Order -> Service Completion Report -> Invoice -> Payment -> Receipt -> Accounting -> Proof Packet.

## Operational Safety

- Never trust browser-calculated totals.
- Audit meaningful status and money changes.
- Do not post drafts.
- Do not duplicate-post accounting records.
- Keep generated documents linked to source records.
- Keep public customer links tokenized and separate from internal IDs.

