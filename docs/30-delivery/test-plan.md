# Test Plan

## Current State

No application exists yet, so route, login, layout, and workflow testing cannot be performed until the foundation scaffold is created.

## Phase 0 Checks Completed

- Confirmed workspace contents.
- Confirmed no existing project files.
- Confirmed this folder is not a Git repository.
- Confirmed documentation scaffold was created.

## Standard Feature Test Set

Every feature should include tests or manual verification for:

1. Empty state.
2. Create record.
3. Edit record.
4. Invalid input.
5. Required validation.
6. Status transition.
7. Related document link.
8. Mobile layout.
9. Desktop layout.
10. Regression check on previous workflow.

## Critical Workflow Test

Before MVP completion, verify this full flow:

1. Create intake.
2. Convert to service request.
3. Create estimate.
4. Add line items.
5. Approve estimate.
6. Create work order.
7. Mark dispatched.
8. Mark arrived.
9. Complete service report.
10. Generate invoice.
11. Record payment.
12. Generate receipt.
13. View proof packet.
14. Confirm accounting entries.

## Sprint 1 Manual Tests

When Sprint 1 app foundation exists, run:

- Dashboard loads with empty database.
- Intake list loads empty.
- Intake create form works on desktop.
- Intake create form works on mobile width.
- Phone mask formats as (xxx) xxx-xxxx.
- Intake can be edited.
- Intake converts to service request.
- Existing customer is detected by phone.
- Duplicate customer is not created when phone matches.
- Vehicle basics are preserved.
- Location is preserved.
- Service request list loads.
- Service request view shows lifecycle timeline.
- Service request status transitions are audit logged.

