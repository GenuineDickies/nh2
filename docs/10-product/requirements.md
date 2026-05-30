# Requirements

## Core Principles

- Keep it simple and easy to understand.
- Build in small tested steps.
- Keep the UI polished, practical, and mobile-first.
- Avoid unnecessary enterprise complexity.
- Avoid duplicate data entry.
- Preserve existing working flows once they exist.
- Keep accounting-related actions traceable.
- Make status transitions intentional and auditable.

## Core Workflow Requirements

- Intake records fast phone-call details.
- Intake can convert to a service request.
- Service request is the core job record.
- Estimate inherits customer, vehicle, location, and service request data.
- Approval is required for estimates over $200.
- Approval is required when final invoice differs from estimate by more than $200 or 10%, whichever is smaller.
- Work order manages field execution.
- Service completion report records what actually happened.
- Invoice is generated from approved or completed work.
- Payment reduces invoice balance.
- Completed payment creates receipt.
- Invoice and payment posting create accounting impact.
- Proof packet consolidates the job record.

## Mobile Requirements

- Major workflows must be usable on a phone.
- Field screen must show customer, call action, map action, service, vehicle, estimate total, approval status, dispatch actions, photo capture, VIN capture, notes, parts/materials, and completion action.
- Mobile actions should be clear, touch-friendly, and difficult to trigger accidentally.

## Document Requirements

- Estimates, approvals, invoices, receipts, and proof packets must look professional.
- Generated document records should be stored.
- Uploaded files and photos should be stored without overwriting existing files.

## Accounting Requirements

- Draft operational records do not post.
- Posting is explicit.
- Ledger entries must balance.
- Invoices post revenue, accounts receivable, and sales tax when applicable.
- Payments post cash or clearing account against accounts receivable.
- Vendor documents must be reviewed before posting.

