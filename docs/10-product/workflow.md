# Workflow

## Mission

Build a mobile-first internal business application that helps a solo roadside assistance / mobile mechanic operator manage the complete job lifecycle from first call to paid receipt and clean accounting records.

The main product focus is:

Fast job capture + mobile execution + proof + payment + clean records.

## Core Lifecycle

1. Intake
2. Service Request
3. Estimate
4. Customer Approval
5. Work Order / Dispatch
6. Field Work
7. Service Completion Report
8. Invoice
9. Payment
10. Receipt
11. Accounting Posting
12. Proof Packet

Each stage should inherit data from the previous stage. The operator should not retype customer name, phone number, location, vehicle details, service requested, estimate line items, approved prices, parts, payment information, or document references.

## Primary Questions

The app should always help answer:

1. Who is the customer?
2. Where is the customer?
3. What vehicle or service is involved?
4. What was quoted?
5. Did the customer approve it?
6. What work was performed?
7. What proof was captured?
8. What parts or materials were used?
9. Was the invoice issued?
10. Was payment collected?
11. Was the receipt sent?
12. Were accounting records created correctly?

## MVP Workflow

The MVP is complete when the operator can:

1. Create an intake.
2. Convert it to a service request.
3. Create an estimate.
4. Get approval.
5. Create a work order.
6. Use a mobile field screen.
7. Capture VIN or mark no vehicle serviced.
8. Capture photos and notes.
9. Complete a service report.
10. Generate an invoice.
11. Record payment.
12. Generate a receipt.
13. See the job timeline.
14. See accounting entries.
15. Download a proof packet.
16. View basic reports.
17. Do all of this without duplicate data entry.

## Sprint 1 Scope

Sprint 1 should establish:

- Documentation.
- Database migrations.
- App shell.
- Dashboard skeleton.
- Customer basics.
- Vehicle basics.
- Location basics.
- Intake basics.
- Service request basics.
- Intake to service request conversion.
- Audit log for service request status changes.

Sprint 1 success criteria:

- A phone call can be entered as intake.
- Intake can convert to service request.
- Customer duplicate check by phone works.
- Basic vehicle info is preserved.
- Location is preserved.
- Service request timeline shows creation.
- Dashboard shows active and pending records.

