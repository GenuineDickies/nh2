---
title: "Operations Document Book"
subtitle: "Standard Operating Procedures, Forms, and Workflow Reference"
author: "White Knight Roadside, LLC · Portland, Oregon"
date: "Document Version 2.0 — Effective Date: ____________________"
toc: true
toc-depth: 3
numbersections: false
documentclass: article
geometry: "margin=1in"
fontsize: 11pt
linestretch: 1.15
---

\newpage

# About This Document

This Document Book is the operational reference for **White Knight Roadside, LLC**. It defines how mobile automotive service requests are intaked, dispatched, executed, documented, and closed across both unscheduled roadside calls and scheduled mobile services. It contains the standard operating procedures (SOPs), customer-facing forms, internal logs, and risk-management documents needed to run the business consistently and defensibly.

Every employee, contractor, and dispatcher acting on behalf of White Knight Roadside is expected to follow the procedures and use the forms contained in this book. Deviations require prior written approval from the Owner.

| | |
|---|---|
| **Document Title** | White Knight Roadside Operations Document Book |
| **Version** | 2.0 |
| **Effective Date** | ____________________ |
| **Reviewed By** | ____________________ |
| **Confidentiality** | Internal use only — not for distribution outside the company |

\newpage

# 1. Strategic Overview

## 1.1 Purpose

This Document Book is the operational reference for White Knight Roadside, LLC. It defines how the company intakes, dispatches, executes, documents, and closes every mobile service — across unscheduled roadside calls and scheduled mobile services. Every employee, contractor, and dispatcher follows the procedures and uses the forms in this book. Deviations require prior written approval from the Owner.

## 1.2 Scope of Services

The company operates two service categories — **unscheduled Roadside services** dispatched in response to stranded-motorist calls, and **scheduled Mobile services** booked by appointment. Both run on the same six-document workflow chain (§1.3) but have different SLAs and intake patterns.

### Roadside Services (unscheduled, light-duty, non-towing)

- **Battery Jump Start** — Restoring electrical start capability to a stranded vehicle using a portable jump pack.
- **Tire Change** — Removing a flat or damaged tire and installing a customer-provided spare or temporary.
- **Vehicle Lockout** — Non-destructive entry into a locked passenger vehicle to retrieve keys or unlock for the owner.
- **Fuel Delivery** — Delivering up to 2 gallons of customer-purchased gasoline or diesel to a stranded vehicle.

### Scheduled Mobile Services

- **Mobile Mount & Balance** — On-site replacement of a damaged tire. White Knight supplies a used, fitment-matched tire purchased from a third-party supplier en route, OR installs a customer-supplied tire at a discounted service rate. Includes mount, bubble balance, valve stem replacement, and TPMS service. Typically a single-tire service; multi-tire jobs supported on request. White Knight handles disposal of the removed tire as part of the next job's tire purchase trip.
- **Mobile Mechanic** — On-site light-duty automotive repair: battery, alternator, starter, brake, oil-change, belt, hose, plug, filter, sensor, and bulb services. See §3.6 for the full scope-in / scope-out list.

### Out of Scope

Towing, recovery, winching, off-road extraction, and heavy-duty service should be referred to a tow provider. When these are needed, the technician provides the customer with tow company contact information (§12); the customer arranges directly. Engine internals, transmission internals, body work, A/C refrigerant handling, and alignment are also out of scope and must not be performed; see §3.6 for the complete Mobile Mechanic exclusion list.

## 1.3 The Six-Document Workflow Chain

Every service request flows through a chain of up to six documents. Each document is generated from data in the previous one, creating a complete audit trail from intake to payment.

| # | Document | Trigger | Owner |
|---|---|---|---|
| 1 | Service Request | Customer call or app submission | Dispatcher / Intake |
| 2 | Estimate | Service Request approved for dispatch | Dispatcher |
| 3 | Work Order | Technician accepts assignment | Technician |
| 4 | Change Order | Scope or price changes on-scene | Technician (customer-signed) |
| 5 | Invoice | Service marked complete | Billing |
| 6 | Receipt | Payment received | Billing |

> **When records are created.** A formal `Vehicle` record is **not** created at intake. During intake and dispatch, only basic vehicle information (year, make, model, color, fuel type) lives on the Service Request. The technician captures the license plate, state, and VIN on scene as part of the Vehicle Condition Report (§7). The `Vehicle` entity is created in the system at the **Invoice** stage, populated from the basic info on the Service Request plus the plate, state, and VIN captured on scene.

## 1.4 Workflow Stages

Each Service Request moves through the stages below. Stage transitions are logged with a timestamp and the responsible party.

| Stage | Action | Documents Created or Updated | Responsible |
|---|---|---|---|
| Intake | Capture caller data, location, vehicle, and service type | Service Request | Dispatcher / AI Agent |
| Dispatch | Assign technician, generate Estimate, send Work Order | Estimate, Work Order | Dispatcher |
| En Route | Technician acknowledges, departs, sends ETA | Dispatch log entry | Technician |
| On Scene | Verify customer, perform hazard assessment, obtain signed waiver | Liability Waiver, Vehicle Condition Report | Technician |
| Execution | Perform service per SOP, photograph before/during/after | Service Log, Photo Set | Technician |
| Completion | Verify success with customer, obtain sign-off, collect payment | Invoice, Receipt, Customer Sign-Off | Technician / Billing |
| Closed | Reconcile cash, archive paperwork, mark request closed | End-of-Shift Log | Billing / Owner |

## 1.5 Roles and Responsibilities (RACI)

**R** = Responsible (does the work) · **A** = Accountable (final approver) · **C** = Consulted · **I** = Informed.

| Activity | Owner | Dispatcher | Technician | Billing |
|---|:---:|:---:|:---:|:---:|
| Receive intake call | I | R / A | — | — |
| Quote price and ETA | A | R | C | I |
| Assign and dispatch | I | R / A | I | — |
| Hazard assessment on scene | I | C | R / A | — |
| Obtain liability waiver | A | I | R | — |
| Perform service | I | I | R / A | — |
| Document with photos | A | I | R | C |
| Collect payment | A | I | R | C |
| Reconcile and close | A | I | I | R |
| Investigate damage claim | R / A | C | C | I |

## 1.6 Service Time Targets (Internal SLAs)

Roadside services are dispatched in real time and held to tight response targets. Scheduled services are booked by appointment and measured against on-time arrival and time-on-job targets.

### Roadside (unscheduled)

| Stage | Target | Escalation Trigger |
|---|---|---|
| Intake to Dispatch | ≤ 5 minutes | 10 minutes |
| Dispatch to En Route | ≤ 5 minutes | 10 minutes |
| En Route to On Scene | ≤ 30 minutes (urban) | 45 minutes |
| On Scene to Service Start | ≤ 5 minutes | 10 minutes |
| Service Start to Completion (Jump Start) | ≤ 15 minutes | 25 minutes |
| Service Start to Completion (Tire Change) | ≤ 25 minutes | 40 minutes |
| Service Start to Completion (Lockout) | ≤ 20 minutes | 35 minutes |
| Service Start to Completion (Fuel Delivery) | ≤ 15 minutes | 25 minutes |

### Scheduled Mobile

| Stage | Target | Escalation Trigger |
|---|---|---|
| On-Time Arrival (vs. quoted window) | Within stated 1-hour window | Window missed |
| Customer Notification of Delay | ≥ 30 min prior to window | Notification missed |
| Time on Job (Mount & Balance, single tire) | ≤ 60 minutes (after en-route tire purchase) | 90 minutes |
| Each Additional Tire (Mount & Balance) | + 20 minutes | + 30 minutes |
| En-Route Tire Purchase Stop (W-K-supplied) | ≤ 25 minutes | 40 minutes |
| Time on Job (Mobile Mechanic — Diagnostic) | ≤ 60 minutes | 90 minutes |
| Time on Job (Mobile Mechanic — Repair) | Per Estimate ± 25% | Estimate + 50% |
| Estimate Delivery After Diagnostic | ≤ 24 hours | 48 hours |

Escalation triggers require a customer status update and a logged note explaining the delay. For scheduled services, an Estimate-overrun trigger requires the technician to **STOP** and produce a Change Order before continuing work.

## 1.7 Cancellation, Refusal, and Refund Policy

Fee treatment depends on **who** decides not to proceed and **at what point** in the workflow. The principle is simple: if White Knight is the reason the service didn't happen, the customer is made whole; if the customer is the reason, the dispatch costs we already absorbed are recovered.

| Scenario | Fees Charged | Refund Policy |
|---|---|---|
| Customer cancels before technician is dispatched | None | Full refund of any pre-paid amount |
| Customer cancels after dispatch but before technician arrives on scene | None | Full refund of any pre-paid amount |
| Customer changes mind / cancels after technician arrives on scene | Dispatch fee applies | Refund of any amount above the dispatch fee |
| Technician declines service for any reason (safety, identity, scope, equipment, judgment) | None | Full refund of any pre-paid amount, including dispatch fee |
| Service is attempted but fails for technical reasons (e.g., jump start unsuccessful → moves to diagnostic / repair quote) | Per quoted service or successfully attempted per the agreed quote | Customer pays only for what is delivered |

This policy is **non-discretionary**. The technician does not negotiate, prorate, or waive at the customer's request. Any deviation requires Owner approval logged in the Service Request.

## 1.8 Customer Signatures, Data, and the Receipt

White Knight operates on four principles that simplify the customer experience, limit company liability, and keep the operation aligned with Oregon consumer-protection law: the customer signs exactly two documents (the Waiver before work and the Receipt at completion); the company shares no customer data with third parties; the Receipt anchors warranty and liability; and any price increase that crosses the Oregon Change Order threshold requires a customer-signed Change Order before work continues.

### Principle 1 — Two Customer Signatures, Bookending the Job

The customer signs **two** documents: the Service Authorization, Charges & Liability Waiver (§6) before any work begins, and the Receipt (§11) at completion. These signatures bookend the job.

The Waiver consolidates three things into one signature: (1) authorization to perform the service, (2) agreement to the agreed charges as recorded on the Waiver, and (3) acknowledgement of risks and release of liability. The Receipt confirms the work was completed to the customer's satisfaction and locks in the financial record. **No other document in this workbook requires a customer signature.** Multiple signature requests beyond these two make customers uncomfortable and slow the work without adding meaningful protection.

If the customer refuses to sign the Waiver, no work is performed. If the customer cancels or refuses service after arrival but before work, the technician documents and signs internally — the customer signs nothing. If the customer refuses to sign the Receipt at completion, the technician notes the refusal on the company-retained copy, provides the customer with their copy regardless, and applies payment terms as normal.

### Principle 2 — No Third-Party Data Sharing

White Knight does **not** share customer information with any third party — not tow companies, not parts suppliers, not insurance partners, not motor clubs (except for verification of coverage on inbound jobs they themselves dispatched). When a customer needs a tow, the technician provides the customer with publicly-available tow company contact information; the customer makes their own arrangements directly. This avoids the entire category of consent paperwork that would otherwise be required under privacy law and prevents the company from being implicated in a third party's service quality or pricing.

### Principle 3 — The Receipt Is the Liability Anchor

Liability for completed work attaches at the issuance of the Receipt (§11). If no Receipt is issued, no work was billed, and no warranty or liability claim can attach. This makes the Receipt the single most important document at the close of every job — it triggers the warranty terms in the Waiver, locks in the agreed amount, and creates the financial record. The Service Log (§9) is internal documentation of what was done; the Receipt is the customer-facing document that completes the transaction.

### Principle 4 — The Oregon Change Order Threshold

Oregon consumer-protection law requires the customer's signature on any change to the agreed price that exceeds **the lesser of 10% of the agreed total or $200**. White Knight applies this rule across all services and uses it as the bright line for when a Change Order signature is required.

- **Change is at or below the threshold** (≤ 10% **and** ≤ $200): the technician may proceed without a Change Order signature, but **must** verbally inform the customer of the change and log it in the Service Log.
- **Change exceeds the threshold** (> 10% **or** > $200, whichever applies first): the technician **must STOP** work, produce a written Change Order capturing the new total, and obtain the customer's signature before resuming. The Change Order amendment becomes the new TOTAL AGREED.

> **Worked example.** TOTAL AGREED on the Waiver is $400. The 10% threshold is $40; the $200 threshold is $200; the lesser is $40. Any cost increase above $40 requires a customer-signed Change Order. If TOTAL AGREED were $3,000, 10% would be $300 but the $200 cap kicks in first, so any increase above $200 requires a signature. **The smaller of the two thresholds always controls.**

This rule is non-discretionary. Performing work that pushes the total above the threshold without a signed Change Order is grounds for the customer to refuse payment of the unauthorized amount and may constitute an Oregon Unlawful Trade Practices Act violation. **When in doubt, get the signature.**

\newpage

# 2. Data Schema (Logic Model)

The data schema below defines the entities and fields that back every form, log, and dashboard in the operation. It is also the source of truth for the RoadRunner Admin platform integration. Field names are written in PascalCase to match the database convention.

## 2.1 Entity Map

| Entity | Purpose | Created At |
|---|---|---|
| Customer | Person or company requesting service | Intake |
| ServiceRequest | Single intake event for a specific service | Intake |
| Dispatch | Assignment of a request to a technician | Dispatch |
| Technician | Field employee performing service | Hire (master record) |
| ServiceLog | Time-stamped record of work performed | On scene |
| Photo | Image evidence linked to a Service Log | On scene |
| Vehicle | Vehicle being serviced (formal record) | Invoice |
| Transaction | Financial record for a request | Invoice |
| Incident | Damage, injury, or safety event | When event occurs |

> **Vehicle record lifecycle.** The `Vehicle` entity is created at **Invoice** generation, **not** at intake. Until Invoice, vehicle information lives as fields directly on the `ServiceRequest`. Intake-captured fields are basic identifiers (year, make, model, color, fuel type). License plate, state, and VIN are captured by the technician on-scene during the Vehicle Condition Report (§7) and stored on the `ServiceRequest` until Invoice. At Invoice time, the system creates the `Vehicle` record from the intake-captured basic fields plus the on-scene-captured plate, state, and VIN.
>
> Three reasons for this approach: it prevents duplicate `Vehicle` records from accumulating in the system; it keeps the intake process fast and simple, avoiding question fatigue with already-flustered customers; it does not require the customer to read fine details off the vehicle while in a potentially dangerous position — on a highway shoulder, in the dark, in weather, or under stress. The technician handles those captures safely on-scene.

## 2.2 Customer Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `CustomerID` | UUID | Y | System-generated |
| `FirstName` | string(60) | Y | Customer's first name |
| `LastName` | string(60) | Y | Customer's last name |
| `PhonePrimary` | E.164 | Y | Used for SMS dispatch updates |
| `PhoneSecondary` | E.164 | N | Optional second contact |
| `Email` | string(160) | N | For receipt delivery |
| `AccountType` | enum | Y | `Retail` \| `Fleet` \| `InsurancePartner` \| `MotorClub` |
| `FleetID` | string | N | Required if `AccountType = Fleet` |
| `DataConfidence` | enum | Y | `Verified` \| `Unconfirmed` \| `Flagged` |
| `FirstSeen` | datetime | Y | Timestamp of first contact |

## 2.3 Vehicle Object

The `Vehicle` entity is created at Invoice generation. It is populated from the basic vehicle fields on the `ServiceRequest` (§2.4) combined with the VIN captured on-scene by the technician. Once created, the `Vehicle` record persists and can be linked to future `ServiceRequest`s for repeat customers.

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `VehicleID` | UUID | Y | System-generated at Invoice |
| `CustomerID` | FK | Y | Owning customer |
| `CreatedFromRequestID` | FK | Y | ServiceRequest that produced this record |
| `Year` | int(4) | Y | Sourced from `ServiceRequest.VehicleYear` |
| `Make` | string(40) | Y | Sourced from `ServiceRequest.VehicleMake` |
| `Model` | string(60) | Y | Sourced from `ServiceRequest.VehicleModel` |
| `Trim` | string(40) | N | Captured on-scene if relevant |
| `Color` | string(20) | N | Sourced from `ServiceRequest.VehicleColor` |
| `LicensePlate` | string(10) | N | Captured on-scene by technician |
| `State` | string(2) | N | Captured on-scene by technician |
| `VIN` | string(17) | N | Captured on-scene during Condition Report |
| `FuelType` | enum | N | Sourced from `ServiceRequest.VehicleFuelType` |
| `CreatedAt` | datetime | Y | Timestamp of Invoice generation |

## 2.4 ServiceRequest Object

The `ServiceRequest` carries the customer's basic vehicle information directly as fields. There is no `Vehicle` FK at this stage — the `Vehicle` entity does not exist yet. The VIN is collected on-scene by the technician (§7) and stored on the `ServiceRequest` until Invoice generation, at which point it is migrated into the new `Vehicle` record.

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `RequestID` | UUID | Y | Customer-facing short ID also generated |
| `CustomerID` | FK | Y | |
| `VehicleYear` | int(4) | Y | Captured at intake |
| `VehicleMake` | string(40) | Y | Captured at intake |
| `VehicleModel` | string(60) | Y | Captured at intake |
| `VehicleColor` | string(20) | N | Captured at intake |
| `VehicleFuelType` | enum | N | `Gas` \| `Diesel` \| `Hybrid` \| `EV` |
| `VehiclePlateOnScene` | string(10) | N | Captured on-scene by technician (§7); flows into `Vehicle` record at Invoice |
| `VehicleStateOnScene` | string(2) | N | Captured on-scene by technician (§7); flows into `Vehicle` record at Invoice |
| `VINCapturedOnScene` | string(17) | N | Captured on-scene by technician (§7); flows into `Vehicle` record at Invoice |
| `VehicleIDCreated` | FK | N | Set at Invoice when `Vehicle` record is generated |
| `ServiceType` | enum | Y | `JumpStart` \| `TireChange` \| `Lockout` \| `FuelDelivery` \| `MountBalance` \| `MobileMechanic` |
| `ServiceCategory` | enum | Y | `Roadside` \| `Scheduled` — drives SLA group, intake flow, and pricing |
| `Status` | enum | Y | See §2.10 |
| `Priority` | enum | Y | `Standard` \| `Urgent` \| `Hazard` |
| `LocationLat` | decimal(9,6) | Y | |
| `LocationLong` | decimal(9,6) | Y | |
| `LocationAddress` | string(240) | Y | Reverse-geocoded or caller-provided |
| `LocationDescription` | string(500) | N | e.g., "NB shoulder MM 287 I-5" |
| `HazardFlag` | boolean | Y | Default false; true triggers safety protocol |
| `RequestedAt` | datetime | Y | Timestamp of intake |
| `DispatchedAt` | datetime | N | |
| `OnSceneAt` | datetime | N | |
| `CompletedAt` | datetime | N | |
| `IntakeChannel` | enum | Y | `Phone` \| `App` \| `Web` \| `Partner` |
| `IntakeAgent` | string | Y | Dispatcher name or AI agent ID |

## 2.5 Dispatch Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `DispatchID` | UUID | Y | |
| `RequestID` | FK | Y | |
| `TechID` | FK | Y | |
| `AssignedAt` | datetime | Y | |
| `AcceptedAt` | datetime | N | |
| `EnRouteAt` | datetime | N | |
| `ETAMinutes` | int | Y | Estimated minutes to arrival |
| `RouteDistance` | decimal | N | Miles |

## 2.6 Technician Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `TechID` | UUID | Y | |
| `FirstName` | string(60) | Y | |
| `LastName` | string(60) | Y | |
| `Phone` | E.164 | Y | |
| `TruckID` | string | Y | Identifier for assigned service vehicle |
| `EquipmentKit` | JSON | Y | `{ jumpPack: bool, lockoutKit: bool, jack: bool, fuelCan: bool }` |
| `CertExpires` | date | N | Driver, OSHA, etc. |
| `CurrentStatus` | enum | Y | `Available` \| `EnRoute` \| `OnScene` \| `OffShift` |

## 2.7 ServiceLog Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `LogID` | UUID | Y | |
| `RequestID` | FK | Y | |
| `TechID` | FK | Y | |
| `EntryType` | enum | Y | `Note` \| `StatusChange` \| `Photo` \| `Signature` \| `Refusal` |
| `EntryText` | text | N | |
| `Timestamp` | datetime | Y | Server time, immutable |
| `GeoLat` | decimal | N | Captured from device when available |
| `GeoLong` | decimal | N | |

## 2.8 Transaction Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `TransactionID` | UUID | Y | |
| `RequestID` | FK | Y | |
| `BaseFee` | decimal(10,2) | Y | |
| `Surcharges` | JSON | N | `{ afterHours, mileage, hazard }` |
| `PartsCost` | decimal(10,2) | N | e.g., fuel |
| `Subtotal` | decimal(10,2) | Y | |
| `Tax` | decimal(10,2) | Y | |
| `Total` | decimal(10,2) | Y | |
| `PaymentMethod` | enum | Y | `Cash` \| `Card` \| `Invoice` \| `Waived` |
| `PaymentStatus` | enum | Y | `Pending` \| `Paid` \| `Refunded` \| `Disputed` |
| `ProcessorRef` | string | N | Square or other reference |

## 2.9 Incident Object

| Field | Type | Required | Notes |
|---|---|:---:|---|
| `IncidentID` | UUID | Y | |
| `RequestID` | FK | N | Linked request if applicable |
| `IncidentType` | enum | Y | `PropertyDamage` \| `VehicleDamage` \| `Injury` \| `NearMiss` \| `Theft` |
| `Severity` | enum | Y | `Low` \| `Medium` \| `High` \| `Critical` |
| `Description` | text | Y | |
| `ReportedBy` | string | Y | |
| `OccurredAt` | datetime | Y | |
| `ReportedAt` | datetime | Y | |
| `ResolutionNotes` | text | N | |
| `Status` | enum | Y | `Open` \| `Investigating` \| `Resolved` \| `Closed` |

## 2.10 Status Enums

| Enum | Values |
|---|---|
| `RequestStatus` | `New`, `Quoted`, `Scheduled`, `Diagnosed`, `AwaitingParts`, `Dispatched`, `EnRoute`, `OnScene`, `InProgress`, `Completed`, `CustomerNoShow`, `Cancelled`, `Failed`, `Referred` |
| `TechStatus` | `Available`, `EnRoute`, `OnScene`, `Unavailable`, `OffShift` |
| `PaymentStatus` | `Pending`, `Paid`, `PartialPaid`, `Refunded`, `Disputed`, `Waived` |
| `DataConfidence` | `Verified`, `Unconfirmed`, `Flagged` |

\newpage

# 3. Standard Operating Procedures

Each SOP defines the equipment, prerequisites, step sequence, safety hold-points, and completion criteria for one service type. **Technicians follow these SOPs in order.** Skipping a step requires a logged justification on the Service Log.

## 3.1 Jump Start SOP

### Equipment Required

- NOCO Boost Pro GB150 (or equivalent) jump pack, charged ≥ 75%
- Insulated mechanic gloves (Class 0 or higher)
- ANSI Z87.1 safety eyewear
- Multimeter (for diagnostic verification)
- Battery terminal cleaner / wire brush
- Microfiber towel

### Prerequisites

- Signed Service Authorization, Charges & Liability Waiver
- Hazard assessment complete (§8)
- Vehicle in Park (auto) or Neutral with parking brake set (manual)
- All vehicle accessories OFF (HVAC, radio, headlights, interior lights)

### Procedure

1. Confirm with the customer that the vehicle is exhibiting a no-start condition consistent with battery failure (slow crank, click-no-crank, no electrical response).
2. Open the hood and locate the battery (or remote jump terminals if equipped).
3. Visually inspect the battery for cracking, bulging, leaking electrolyte, or visible terminal corrosion. If any of these conditions are present, **STOP** and proceed to Step 12.
4. Test battery voltage with the multimeter. Record the reading on the Service Log.
5. If voltage reads below 9.6 V or above 14.0 V at rest, the battery is likely failed; advise the customer and **STOP** unless they request the jump attempt anyway (record the request in writing on the Service Log).
6. Connect the **RED (positive)** clamp to the battery positive terminal, or to the dedicated positive jump post.
7. Connect the **BLACK (negative)** clamp to a clean, unpainted engine ground point at least 12 inches from the battery.
8. Verify clamps are secure and the jump pack indicates correct polarity.
9. Attempt to start the vehicle for no more than 5 seconds. If unsuccessful, wait 30 seconds and try once more (**maximum 2 attempts** per SOP).
10. Once the vehicle starts, disconnect the **BLACK clamp first**, then the **RED clamp**.
11. Allow the vehicle to run for at least 2 minutes with the technician on-scene before clearing the call.
12. **If the vehicle does not start after 2 attempts**, the issue is most likely a failed battery, a failed starter, or a fuel-delivery problem. Move into diagnostic mode:
    a. Load-test the battery to confirm or rule out battery failure.
    b. Listen at the key for the starter solenoid click and check starter power.
    c. Verify fuel pump prime (key-on listen at the tank, or fuel-pressure check if equipped).

    Based on findings, present the customer with the appropriate option and a written quote: on-scene battery replacement (Mobile Mechanic — §3.6, in scope); on-scene starter replacement if externally accessible (Mobile Mechanic, in scope); fuel pump or fuel pump relay replacement, including in-tank pump if access conditions are workable on-site (Mobile Mechanic, in scope); or Tow Referral (§12) if the vehicle is in an unsuitable location for the required repair, the diagnosis is inconclusive, or the customer prefers off-site repair. **The customer authorizes the chosen path before any further work begins.**

### Photo Requirements

- Battery condition before service (terminals, case)
- Multimeter reading at rest
- Clamp placement (both terminals)
- Multimeter reading post-start (engine running)

### Completion Criteria

- Vehicle starts and idles independently for at least 2 minutes
- Customer confirms vehicle running
- Service Log updated with voltage readings
- Customer signs Service Completion section of Work Order

> **⚠ Safety Hold-Point.** Never connect the negative clamp directly to the battery negative terminal on a flooded lead-acid battery. Hydrogen gas accumulating in the battery can ignite from arcing. Always ground to a remote engine point.

## 3.2 Tire Change SOP

### Equipment Required

- Floor jack (3-ton minimum, rated for vehicle weight) and rated jack stands
- Lug wrench set (17 mm, 19 mm, 21 mm, 22 mm) and torque wrench
- Wheel chocks (minimum 2)
- Cordless impact gun (½" drive) with extension
- Reflective triangles or LED safety flares (3)
- Class II high-visibility safety vest

### Prerequisites

- Customer has a usable spare tire OR a temporary inflator kit
- Vehicle is on stable, level ground (**never** perform on incline, soft shoulder, or active traffic lane)
- If on a roadway, reflective triangles deployed at 10 ft, 100 ft, and 200 ft behind the vehicle
- Customer and any passengers moved to a safe location off the roadway

### Procedure

1. Set the parking brake. Place wheel chocks on the wheel diagonally opposite the flat tire.
2. Verify spare tire condition: inflation pressure, tread depth, sidewall integrity, and matching bolt pattern. **If the spare is unsafe, STOP and offer Tow Referral.**
3. Loosen lug nuts on the flat tire one half-turn each (do not remove) while the wheel is still on the ground.
4. Locate the manufacturer-designated jack point nearest the flat tire. Refer to owner's manual or under-vehicle factory markings.
5. Position the floor jack under the manufacturer-designated jack point and raise the vehicle until the flat tire clears the ground by 2–3 inches. **Place a rated jack stand under a structural point as a safety backup before working under or near the wheel.**
6. Remove lug nuts and place them in a magnetic tray or hubcap to prevent loss.
7. Remove the flat tire and set it aside. Sliding it flat under the rocker panel is fine and keeps it out of your work area; just don't rely on it as the load-bearing support — the jack and jack stand do that work.
8. Mount the spare tire and hand-tighten lug nuts in a star pattern.
9. Lower the vehicle until the tire just contacts the ground.
10. Torque lug nuts to manufacturer specification (typically 80–100 ft-lbs for passenger vehicles, 100–140 ft-lbs for light trucks) in a star pattern.
11. Lower the vehicle fully and remove the jack.
12. Verbally and in writing inform the customer: temporary spare maximum speed is 50 mph, maximum range 70 miles; have the flat tire repaired or replaced as soon as possible; lug nuts should be re-torqued after 50–100 miles of driving.

### Photo Requirements

- Damaged tire showing failure mode
- Spare tire condition prior to mounting (tread + pressure gauge)
- Jack placement under vehicle
- Mounted spare with lug nuts torqued

> **⚠ Roadside Tire Change Refusal.** Decline tire change service in any of the following conditions: vehicle is on the active travel lane of a highway with no usable shoulder; surface is soft, uneven, or sloped greater than 5°; vehicle has multiple flat tires; vehicle weight exceeds 9,000 lbs GVWR; or weather conditions create unacceptable risk (heavy rain, ice, electrical storm). In any of these cases, advise the customer that towing is the safer option and use the Tow Referral form (§12).

## 3.3 Vehicle Lockout SOP

### Equipment Required

- Professional lockout kit (long-reach tool, wedge set, inflatable wedge)
- Microfiber barrier cloth to protect paint and weatherstripping
- Flashlight

### Prerequisites

- **Identity verification.** Customer present at the vehicle, ID checked before unlocking OR registration matched after the door is open. Use best judgment; when in doubt, decline.
- Signed Service Authorization, Charges & Liability Waiver acknowledging risk of cosmetic damage.
- **Emergency exception.** If a child, incapacitated adult, or animal is locked inside the vehicle: **proceed with the lockout immediately.** Do not delay. The technician is on scene with the tools to open the door — that is the help being summoned. Once the vehicle is open, assess the occupant and call 911 for medical evaluation if there is any sign of distress (heat, cold, unconsciousness, breathing difficulty, dehydration). For animals, get them out and into shade/water; advise the customer to seek vet care if needed. Document the emergency lockout on the Service Log; **the company does not charge an emergency lockout fee for these situations.**

### Procedure

1. Visually identify the vehicle and confirm match with customer-provided details (VIN, plate, color).
2. Photograph all four sides of the vehicle and all door surfaces near the work area **before** starting work.
3. Place the inflatable wedge between the door frame and the door at the upper corner.
4. Slowly inflate the wedge to create a small gap (no more than ½ inch). **Never overinflate** — doing so can crack glass or warp the door frame.
5. Insert the protective barrier cloth between the wedge and the door paint.
6. Insert the long-reach tool through the gap and locate the unlock control (button, manual lock post, or door handle).
7. Operate the unlock control. If the unlock requires pulling the inside door handle, pull just enough to release the lock — the door will usually unlock before it physically opens. **Stop pulling as soon as you hear or feel the lock disengage.** Do not continue the motion into door-open.
8. **Confirm the door is unlocked without opening it.** Lightly pull the outside door handle and feel for the resistance change: a locked door has no tension in the handle pull, while an unlocked door produces a distinct tension about halfway through the pull (that's the latch mechanism trying to disengage). Stop the pull as soon as you feel that tension — do not complete the motion. If you feel the latch tension, the door is unlocked and ready for equipment removal. If you feel no tension, the unlock did not take; reposition and try again.
9. Remove the long-reach tool first, then deflate the wedge, then remove all equipment from the door gap. **Only after all equipment is fully removed should the door be opened.**
10. Open the door by hand.
11. Photograph the door surface in the same locations as Step 2 to document any cosmetic changes.
12. Have the customer test the door operation, locks, and window function in your presence.

> **⚠ Never open the door with equipment still in place.** Once the door is unlocked, the wedge and long-reach tool **must** come out before the door opens. Pulling the handle while the inflatable wedge is still in the door gap will snap the door cable. This is the most expensive avoidable mistake in lockout work. The discipline: unlock → remove long-reach → deflate wedge → remove wedge → **then** open. If the unlock requires pulling the inside door handle, pull just enough to disengage the lock and stop. The lock typically releases before the door physically opens; use that gap to remove the equipment, then open the door by hand.

> **⚠ Identity Verification — The Real Rule.** The customer **must be present at the vehicle**. Never unlock an unattended vehicle. Verify identity by checking ID before unlocking, or by matching registration after the door is open. Use gut feeling. **When in doubt, walk.**

## 3.4 Fuel Delivery SOP

### Equipment Required

- Two clean DOT-approved metal fuel containers (2.5 gallon and 5 gallon)
- Fuel funnel with mesh filter
- Nitrile gloves
- Spill containment pad / absorbent
- Class B fire extinguisher (mounted in service truck)

### Prerequisites

- Confirm correct fuel type with customer (gasoline regular, gasoline premium, or diesel)
- Customer has paid for fuel cost in addition to delivery service fee
- Service vehicle parked at least 20 feet from the customer vehicle (where possible) with engine OFF
- No open flames, smoking, or active electrical work within 25 feet

### Procedure

1. Verify the customer's stated fuel type by inspecting the fuel door label or owner's manual. Diesel vehicles are usually marked **DIESEL** on the fuel door, but always verify.
2. **If fuel type cannot be confirmed, do not add fuel.** Misfueling causes severe and expensive engine damage.
3. Open the customer's fuel door and remove the fuel cap.
4. Place the spill containment pad on the ground beneath the fuel filler.
5. Insert the funnel into the fuel filler neck.
6. Pour fuel slowly and steadily. **Do not exceed 2 gallons per delivery** (this is a starter quantity to reach a fuel station, not a fill).
7. Remove the funnel and replace the fuel cap until it clicks (gasoline) or seats firmly (diesel).
8. Have the customer attempt to start the vehicle. If it does not start within 60 seconds of cranking, stop and reassess (a vehicle that has run completely dry may need to bleed air from fuel lines or have the fuel pump primed).
9. Once the vehicle is running, advise the customer of the nearest fuel station and the recommended driving range.

> **⚠ Fuel Type Mismatch.** Adding gasoline to a diesel engine, or diesel to a gasoline engine, can cause catastrophic engine damage. **If you are not 100% certain of the fuel type, decline the service** and offer Tow Referral to a station where the customer can verify.

## 3.5 Mobile Mount & Balance SOP

On-site replacement of a damaged tire. The customer has a flat or otherwise unusable tire and needs a working replacement. White Knight Roadside dispatches a technician who, in the typical case, purchases a used fitment-matched tire from a third-party supplier en route and then proceeds to the customer to perform the dismount, mount, and bubble balance. A reduced-rate option is available when the customer supplies their own tire.

This service is typically a single-tire job, although multi-tire jobs are supported on request (with corresponding per-tire pricing and time). **Tire size must be captured at intake** — it is required to purchase the right tire and to confirm the vehicle's wheel size. Mount & Balance work is performed using the company's custom-built leverage-operated tire machine and self-leveling bubble balancer; none of the equipment is hydraulic, pneumatic, or computerized.

### Service Pricing (Internal Reference)

This pricing is internal-only and is the basis for the dispatcher's quote at intake.

| Scenario | Service Fee | Tire Cost (to customer) | Notes |
|---|---|---|---|
| W-K-supplied tire, under 18" | $150 | $50 (typical) | W-K acquisition cost ≈ $40; markup covers shopping time |
| W-K-supplied tire, 18" and above | $150 | Custom rate | Tech contacts dispatcher with supplier quote before purchase |
| Customer-supplied tire | $100 | — | No tire cost; customer assumes part responsibility |
| Each additional tire (same job, W-K-supplied, under 18") | + $50 | + $50 | Service fee adds per additional tire |
| Each additional tire (customer-supplied) | + $35 | — | |

The dispatcher quotes the total at intake based on tire size confirmation. If the actual purchase cost exceeds the quote (e.g., a 18"+ tire has unexpected pricing at the supplier) and the increase crosses the Oregon Change Order threshold (lesser of 10% of TOTAL AGREED or $200, per §1.8), the technician contacts dispatch **before** purchasing and the customer is re-quoted. A signed Change Order is required before the over-threshold purchase proceeds. Smaller increases are logged and verbally communicated.

### Equipment Required

- Custom-built leverage-operated tire mount/demount machine, truck-mounted (no hydraulic, pneumatic, or electric drive)
- Level (bubble) balancer (self-leveling design — compensates for minor surface unevenness internally) with cone set and adapters
- Manual leverage bead breaker
- Tire mount/demount lever bars (matched set, in good condition with no burrs)
- Replacement valve stems (rubber snap-in for non-TPMS; metal clamp-in for TPMS-equipped)
- TPMS service kits (sensor seals, grommets, nuts) and TPMS relearn tool
- Valve core tool, tire lubricant / mounting paste
- Inflator with regulated supply and accurate gauge
- Wheel weights — clip-on (steel wheels) and adhesive (alloy wheels)
- Floor jack (4-ton minimum) and rated jack stands
- Wheel chocks, lug wrench, calibrated torque wrench
- Tread depth gauge
- Wire brush for rim bead seat cleaning
- ANSI Z87.1 safety eyewear (mandatory for bead seating)
- Mechanic's gloves (snug-fit; no loose cuffs that can catch on lever bars)

### Prerequisites

- Tire size captured at intake (recorded on Service Request) AND verified against the vehicle's door-jamb specification before purchase
- Quote presented to the customer at intake with the total: service fee + tire cost (or service-only fee if customer-supplied)
- Customer authorization to proceed (verbal or via app)
- Signed Service Authorization, Charges & Liability Waiver, including the AS-IS Used Tire Acknowledgement (§6) if W-K-supplied
- Tire purchased en route (if W-K-supplied) — fitment verified at the supplier before payment
- Vehicle is on a stable, hard, level surface — driveway, garage floor, or commercial lot
- Hazard assessment complete (§8)

### En-Route Tire Purchase Procedure (W-K-Supplied Only)

Performed by the technician between dispatch and customer arrival. Skipped entirely for customer-supplied tire jobs.

1. Confirm the tire size on the Service Request matches the vehicle's expected size. If unsure, call the customer to verify size markings on the door jamb or an existing tire sidewall before proceeding to the supplier.
2. Travel to the preferred used-tire supplier. Inspect candidate tires using two tiers of criteria.

    **Preferences** (use judgment; newer and more tread is better, but a serviceable tire beats leaving the customer stranded): DOT date code, with newer preferred; tread depth, with more than 4/32" preferred where available.

    **Safety Disqualifiers** (do **not** install regardless of customer pressure or scarcity):

    - Mismatched size
    - Visible sidewall damage, bulges, or cuts
    - Dry rot or weather-checking deep enough to expose cord
    - Plug or patch repairs in the sidewall or shoulder
    - Any visible cord exposure
    - Obvious previous run-flat damage
    - Bald or near-bald tread (under 2/32")

    The judgment call is on age and depth; the safety disqualifiers are not negotiable.

3. If no acceptable tire is available at the first supplier, try a second. If two suppliers have no acceptable tire, contact dispatch — the customer will be offered a re-quote (longer ETA), refund, or alternative service.
4. If the supplier price is within the quoted budget, purchase the tire. Photograph the tire's sidewall (size + DOT code) and tread depth at the supplier as proof-of-condition before installation.
5. **If the supplier price exceeds the quoted budget** (typical for 18"+ tires), **STOP**. Call dispatch with the quote. Dispatch contacts the customer for re-authorization. **No purchase without re-authorization.**
6. Load the purchased tire securely. Proceed to the customer.

### On-Scene Procedure (per tire being replaced)

7. Greet the customer; confirm vehicle and the tire to be replaced.
8. **If W-K-supplied:** present the purchased tire to the customer. Show them the size markings, DOT date code, tread depth, and any visible cosmetic features. Walk them through the AS-IS Used Tire Acknowledgement (§6) and obtain signature **before** installation.
9. **If customer-supplied:** inspect the customer's tire for fitment to the vehicle and for any safety-disqualifying defects (sidewall damage, severe dry rot, cord exposure). If the tire is not safely usable, decline to install and explain why. The customer-provided parts acknowledgement (§6) applies.
10. Verify tire size markings match the vehicle's door-jamb spec and the wheel diameter, width, and load rating. **STOP** if mismatched.
11. Inspect the affected wheel for cracks, bends, hub-centric damage, or compromised lug seats. Photograph any concerns and notify the customer before proceeding.
12. Inspect the tire being removed for its DOT date and condition (for the dispute-protection record only — this tire is being discarded).
13. Set the parking brake; chock the wheels not being serviced.
14. Loosen lug nuts on the target wheel before lifting.
15. Lift the vehicle at the manufacturer-designated jack point and place a rated jack stand. **Never rely solely on the jack.**
16. Remove the wheel and transport to the mount machine.
17. Deflate fully — remove the valve core.
18. Break the inner bead, then the outer bead, using the manual leverage bead breaker. Apply steady, controlled pressure — **never strike with a hammer or apply impact loading**, which can crack alloy rims.
19. Mount the wheel on the leverage tire machine, properly centered and clamped to avoid rim damage. Verify clamps are secure before applying any leverage.
20. Demount the old tire using the lever bars with adequate tire lubricant. Work the bead progressively around the rim — never force a section past its natural travel. With manual leverage, fatigue is the enemy: take a break and reposition rather than forcing a stuck bead.
21. Inspect the rim bead seat. Wire-brush rust and debris; document significant pitting that may prevent a clean seal.
22. **Always replace the valve stem — never reuse.** For TPMS-equipped wheels, install the OEM-compatible service kit and torque the sensor mounting nut to the manufacturer specification.
23. Apply tire lubricant generously to both new tire beads.
24. Mount the new tire on the rim using the lever bars, working evenly around the circumference. With leverage tools, the technique is **patience over force**: short, controlled strokes around the bead rather than long pulls in one location.
25. **Bead seating:** inflate while monitoring pressure closely. Beads should seat with audible "pops" on each side. **Never exceed 40 psi during bead seating.** If beads will not seat by 40 psi, deflate, reposition, re-lubricate, and try again.
26. Once beads are seated, install the valve core and inflate to the door-jamb-spec pressure (cold).
27. Set up the balancer on a flat, reasonably level surface — driveway, garage floor, or commercial lot. The balancer is self-leveling and compensates for minor surface unevenness internally; a perfectly level surface is not required, but avoid significant slope or unstable ground.
28. Mount the wheel on the balancer with the proper centering cone. The cone must seat fully and the wheel must spin freely on the post.
29. Allow the wheel to settle without spinning. The heavy spot will rotate to the bottom; the bubble in the indicator will offset toward the heavy spot. Note the position.
30. Place test weights on the rim opposite the heavy spot (at the 12 o'clock position relative to the heavy spot). Adjust weight quantity and exact angular placement until the bubble centers in the indicator ring.
31. Once the bubble is centered with test weights, transfer to permanent weights at the same locations: clip-on weights for steel wheels, adhesive weights for alloy wheels (placed on the inner barrel for cosmetic finish).
32. Recheck balance after permanent weights are applied. The bubble must remain centered. If it drifts, refine weight placement or quantity until centered.
33. Lift the wheel off the cone and remount to verify repeatability. If the bubble centers consistently across two mountings, balance is acceptable.
34. Return the wheel to the vehicle. Hand-tighten lug nuts in a star pattern.
35. Lower until the tire just contacts the ground.
36. Torque to manufacturer specification (typically 80–100 ft-lbs passenger, 100–140 ft-lbs light truck) in a star pattern with a calibrated torque wrench.
37. Lower fully and remove the jack and stand.
38. Repeat for additional tires if the job is multi-tire.
39. After all tires are installed: verify pressures on all four; perform TPMS relearn procedure if equipped.
40. Load the removed (old) tire into the service truck for disposal. The old tire travels with the technician until the next M&B job's tire-purchase trip, where it is delivered to the supplier for disposal as a courtesy along with the new purchase. Record the old tire's disposition on the Service Log.
41. Final walk-around with the customer; advise: re-torque lug nuts after 50–100 miles of driving; recheck pressure after 24 hours; the TPMS warning light may persist briefly until first drive cycle completes; **and** review the static-balance disclosure with the customer (see callout below). For W-K-supplied used tire: re-confirm AS-IS, no warranty.

### Photo Requirements

- DOT date code on each new tire (4-digit week/year)
- Old tire wear pattern and any sidewall damage
- Each wheel before mounting (rim condition)
- Rim bead seat after cleaning
- Bubble centered in the indicator after final weights applied — one photo per wheel
- Torque wrench in use on at least one lug per wheel
- All four installed wheels with valve stems visible

### Completion Criteria

- All wheels statically balanced — bubble centered consistently across two test mountings
- All lug nuts torqued to spec with a calibrated wrench
- All tire pressures verified at door-jamb spec
- TPMS reset (if equipped) — warning light acknowledged
- Customer advised in writing: 50–100 mile re-torque, 24-hour pressure check, TPMS relearn, AND static-balance limitations (signed disclosure)

> **⚠ Bead-Seating Hazard.** Bead seating is the most dangerous step in tire service. A tire that fails during seating can launch the rim, the tire, or both with lethal force. **Never exceed 40 psi while seating beads.** Always wear ANSI Z87.1 eye protection. Stand to the side, never directly over the wheel. If beads will not seat at 40 psi, the tire-to-rim fit is wrong; do not force it — deflate completely and reassess.

> **⚠ Leverage Equipment Discipline.** The mount/demount machine and bead breaker are entirely manually powered. Three rules: (1) **Patience over force** — if a bead is not yielding, the technique is wrong, not the strength. Reposition, add lubricant, take a break. (2) **Watch the lever path** — a slipped lever bar travels with the leverage you applied. Never position your face, hand, or another body part in the lever's potential travel arc. (3) **Inspect the equipment at the start of every shift.** Bent lever bars, cracked welds, or stripped threads on a custom-built rig fail without warning under load. Document any equipment concerns and red-tag affected components until repaired.

> **⚠ TPMS Sensor Handling.** TPMS sensors cost $40–$200 each and break easily during demount. Always use proper demount technique (start opposite the sensor, never near it). Always replace the rubber grommet/seal kit during service. After service, verify all four sensor signals are reading at the dash before clearing the call. A missing or damaged sensor must be disclosed to the customer immediately, in writing, with photo evidence.

> **📋 Static vs. Dynamic Balance — Required Customer Disclosure.** The bubble balancer used in this service performs **static balance only** — it corrects mass distribution as viewed from the side of the wheel. It does **not** correct dynamic imbalance, which is mass distribution across the **width** of the wheel. Static balance is sufficient for most light-duty passenger and light-truck applications and for typical urban and highway speeds. However, customers driving sustained 70+ mph, operating performance vehicles, or with previously dynamic-balanced wheels may notice a vibration that static balance does not eliminate. Before clearing every Mount & Balance job, the technician must verbally explain this distinction to the customer and obtain a signed acknowledgment on the Service Log. Customers reporting persistent vibration after service should be advised to seek dynamic balancing at a shop equipped with a computerized spin balancer.

## 3.6 Mobile Mechanic SOP

On-site light-duty automotive repair. Unlike the other SOPs in this section, Mobile Mechanic is a **category of work**, not a single procedure. This SOP defines the scope of services included, the diagnostic-and-quote workflow, parts procurement rules, on-scene execution standards, multi-visit handling, and warranty terms. The specific manufacturer service procedure for each repair is the authoritative reference for the actual work — this SOP governs how the job is run end-to-end.

### 3.6.1 Services In Scope

- Battery replacement (including testing, terminal cleaning, and disposal)
- Alternator replacement (where externally accessible without major disassembly)
- Starter replacement (where externally accessible)
- Brake pad and rotor replacement, front and rear (drum service if accessible)
- Oil and filter change (conventional, synthetic blend, full synthetic)
- Engine air filter and cabin air filter replacement
- Spark plug replacement (where accessible — not on engines requiring intake removal)
- Serpentine belt replacement and tensioner R&R
- Radiator and heater hose replacement (with coolant top-up or flush)
- Coolant flush and fill
- Fuse and relay replacement; bulb replacement (headlight, taillight, signal, interior)
- Accessible sensor replacement (O2, MAF, MAP, coolant temp, crank/cam where externally accessible)
- Fuel pump replacement (in-tank or external) and fuel pump relay replacement
- Mobile diagnostics: OBD-II scan, code interpretation, basic electrical and charging-system testing
- Pre-purchase inspection (PPI) at customer-specified location
- Wiper blade replacement, washer fluid, top-up of accessible fluids

### 3.6.2 Services Out of Scope (DO NOT PERFORM)

- Engine internals: head gaskets, timing belts/chains, valve cover gasket on transverse engines, internal seals
- Transmission internals (any work inside the transmission case)
- Body work, paint, frame, or structural welding
- A/C system service requiring EPA Section 609 certification (refrigerant recovery, recharge)
- Anything requiring a pit or shop press
- Wheel alignment
- Fuel injector R&R requiring fuel rail removal (accessible single-injector swaps may be done on a case-by-case basis with Owner approval)
- Suspension overhaul (struts, control arms — except for simple sway-bar end links if accessible)
- Warranty-only work that requires dealer software or proprietary tools
- Diesel after-treatment system (DPF, DEF, EGR cooler) repair
- Hybrid/EV high-voltage system work without certified technician on-site

### 3.6.3 Diagnostic and Quote Workflow

Every Mobile Mechanic job follows this sequence. **Skipping the diagnostic step on a customer-described symptom is a violation of policy.**

1. **Customer contact.** Dispatcher captures the customer's reported symptom, vehicle, and location. A Service Request is created with `ServiceType = MobileMechanic`.
2. **Initial triage.** Dispatcher determines whether the request can be scope-matched immediately (e.g., "replace this battery, here's the part number") or requires a diagnostic visit first.
3. **If scope is clear:** produce an Estimate immediately, schedule the repair visit, proceed to Step 6.
4. **If scope is unclear:** schedule a diagnostic visit. The diagnostic fee applies regardless of whether the customer proceeds with repairs.
5. **Diagnostic visit.** Technician performs OBD-II scan, visual inspection, and any necessary functional tests. Output: a written **Diagnostic Report** (§18.3) with findings, recommendations, and an Estimate for the recommended repair.
6. **Customer reviews and signs the Estimate** before any repair work begins. The Estimate is the authorization document for Mobile Mechanic work — it is **not** the same as the Liability Waiver, and **both** are required before work proceeds.
7. **Parts procurement** (§3.6.4) and scheduled repair visit.

### 3.6.4 Parts Procurement

Parts may be sourced from three places. Each has different rules and warranty implications.

- **Truck stock.** Technician carries common consumables (oil, common batteries, common filters, common bulbs and fuses, brake fluid, coolant, basic gaskets). Truck-stock parts are White-Knight-supplied and carry full company warranty.
- **Local supplier.** Technician orders from NAPA, O'Reilly, AutoZone, or a parts house and either picks up or has delivered to the job site. Supplier-sourced parts are White-Knight-supplied and carry full company warranty.
- **Customer-provided.** Customer supplies the part. Technician must inspect at delivery and may decline if the part is wrong, damaged, or counterfeit. Customer-provided parts carry **no White Knight warranty** (workmanship only — see §3.6.7). The customer is informed of and signs acknowledgment of this limitation.

### 3.6.5 On-Scene Execution

1. Verify Estimate matches conditions found. If symptoms or vehicle condition differs from the Diagnostic Report, **STOP** and reassess before opening tools.
2. **Pre-work assessment:** walk-around inspection for new damage or symptoms not previously noted; document with photos.
3. **If scope changes** (additional work needed, parts not as expected, customer changes mind): **STOP**. If the change pushes the total above the Oregon threshold (greater than the lesser of 10% of TOTAL AGREED or $200, per §1.8), produce a written Change Order, get customer signature, then resume. Smaller changes are logged on the Service Log and verbally communicated to the customer.
4. Set up the mobile work area: drop cloth or fender cover, fluid containment, parts tray, tool layout.
5. **Perform the repair per the manufacturer service procedure.** The factory or AllData/Mitchell procedure for the specific year/make/model/engine is the authoritative reference.
6. Use a calibrated torque wrench for all critical fasteners. Document torque values applied on the Service Log.
7. Capture photos at: pre-work, mid-work (key step — e.g., old vs. new part side-by-side), and post-work.
8. **Verify:** start the vehicle; observe operation of the affected system; clear any related codes; confirm no new codes have set.
9. Road test where appropriate and safe (brake jobs, drivability concerns). Document road test in Service Log.
10. Clean the work area: dispose of fluids and old parts per §3.6.8; remove drop cloths; return the customer's space to its original state.
11. **Customer walk-through:** show old parts, explain work performed, demonstrate operation, deliver Invoice and warranty documentation.

### 3.6.6 Multi-Visit Workflow

Some Mobile Mechanic jobs require more than one visit (separate diagnostic, parts on order, customer scheduling, work that requires daylight or dry conditions). Each visit is logged separately under a master Job ID. Diagnostic and repair visits may be billed separately. The customer signs a Work Order for each visit.

### 3.6.7 Warranty Terms

- **Workmanship.** 12 months or 12,000 miles, whichever comes first, on labor performed.
- **White-Knight-supplied parts.** Pass-through of manufacturer warranty, with White Knight handling the warranty claim on behalf of the customer.
- **Customer-provided parts.** No warranty from White Knight on the part. Workmanship-only warranty applies — if the part fails and the failure is determined to be a part defect rather than installation, the customer pays for the new part and labor to replace it.
- **Comeback policy.** If the customer reports an issue within the warranty period that appears related to warranted work, the technician returns to assess at no charge. If the issue is determined to be related to the warranted work, repair is at no charge. If the issue is unrelated, standard rates apply (and the customer is informed before work begins).
- **Warranty does not cover:** damage from accident, abuse, neglect, modification by others, or operation outside manufacturer specifications.

### 3.6.8 Fluid and Parts Disposal

- **Used motor oil:** stored in sealed container in service truck; transferred to certified recycler weekly.
- **Used coolant:** stored separately from oil in labeled container; recycled or disposed at certified facility.
- **Used batteries:** returned to parts supplier for core credit (always).
- **Used filters and brake parts:** bagged and disposed per Oregon DEQ guidance.
- **Brake fluid (DOT 3/4):** captured in dedicated container; disposed at HHW facility.
- **Never pour fluids on the ground, in storm drains, or in customer trash.**

> **⚠ Scope-Creep Discipline.** Mobile Mechanic work invites scope creep. The customer asks "while you're in there, can you also...?" — and the technician feels pressure to say yes. The answer is always: **STOP**, evaluate against the Oregon Change Order threshold (the lesser of 10% of TOTAL AGREED or $200, per §1.8). If the additional work crosses that threshold, write a written Change Order and get a signature before proceeding. Smaller add-ons are logged and communicated verbally. Performing work above the threshold without a signed Change Order is grounds for the customer to refuse payment of the unauthorized amount and may constitute an Oregon Unlawful Trade Practices Act violation.

> **⚠ Customer-Provided Parts.** Customer-provided parts — especially online-sourced batteries, brake pads, and electrical parts — are increasingly counterfeit or wrong-fit. Inspect every customer-provided part at delivery: confirm fitment, check date codes where applicable, look for counterfeit markers (poor packaging, misspellings, missing trademarks). When in doubt, decline the part. Document the inspection in the Service Log.

\newpage

# 4. Phone Intake Script and Procedures

This script is the standard for inbound service calls. The dispatcher's job is to capture complete, accurate data within 90 seconds, calm the caller, and set realistic expectations. Deviations are acceptable for caller welfare, but **all required fields must still be captured before the call ends.**

## 4.1 Call Opening

> *"Thank you for calling White Knight Roadside, this is [NAME]. Are you in a safe location right now?"*

- **If caller answers no** or describes immediate danger (active travel lane, smoke, fire, accident with injuries): instruct them to call 911 first. Stay on the line if requested.
- **If safe:** proceed to §4.2.

## 4.2 Service Identification

> *"What's going on with the vehicle?"* — or, for scheduled callers, *"What service are you looking to book?"*

Listen for keywords. Roadside services are dispatched immediately; scheduled services are booked into a future window.

### Roadside keywords

- "Won't start" / "clicking" / "dead battery" → **Jump Start**
- "Flat tire" / "blowout" / "low tire" → **Tire Change**
- "Locked out" / "keys inside" / "can't get in" → **Lockout**
- "Out of gas" / "empty" / "ran out" → **Fuel Delivery**
- "Won't move" / "in a ditch" / "accident" → **Refer (out of scope)**

### Scheduled keywords

- "Need new tires installed" / "have my tires" / "swap winter tires" / "tire balance" / "tires shaking" → **Mobile Mount & Balance**
- "Brakes" / "need an oil change" / "check engine light" / "alternator" / "starter" / "belt" / "want a mechanic to come out" / "pre-purchase inspection" → **Mobile Mechanic**
- If symptom is unclear or customer is uncertain → schedule a **Mobile Mechanic Diagnostic** visit, **not** a repair visit.

## 4.3 Required Data Capture

Capture the following in order. Read each value back to the caller for confirmation.

1. Caller name and best callback number.
2. Exact location (street address, intersection, freeway with milepost, or GPS coordinates). If the caller cannot accurately state their location, capture verbally to the best of their ability, then dispatch the technician using the best available description.
3. Vehicle year, make, model, and color (license plate, state, and VIN are captured on-scene by the technician).
4. Specific service requested.
5. **If service is Mobile Mount & Balance:** tire size from sidewall markings (e.g., "225/65R17"), number of tires needed, and whether White Knight or the customer is supplying the tire(s). Quote presented to customer with both service fee and tire cost (if W-K-supplied).
6. Any hazards (low fuel + on shoulder, animals or children in vehicle, flat in dangerous location, intoxicated occupants, hostile bystanders).
7. Payment method (card on file, card to take now, fleet account, motor club coverage).

## 4.4 Quote and ETA

Provide the published price for the service plus any applicable surcharges (after-hours, mileage outside service area). Provide an honest ETA based on current technician availability and traffic. **Never quote below the published rate without Owner approval.**

## 4.5 Closing the Call

> *"I'm dispatching [TECH NAME] now. Their ETA is [X] minutes. The technician will call you when they're 5 minutes out. Stay in your vehicle with your hazards on. Anything else I can help with?"*

## 4.6 Call Documentation

Within 60 seconds of ending the call, the dispatcher must enter all captured data into the Service Request system. The intake call recording (if any) is retained for 90 days per company policy.

\newpage

# 5. Service Request Form

Form completed at intake. May be filled by dispatcher (phone) or auto-populated from app/web submission. **All required fields must be present before dispatch.**

```
SERVICE REQUEST
White Knight Roadside, LLC · Portland, OR

Request ID: ____________________   Date / Time: ____________________
Intake Channel: ____________________   Intake Agent: ____________________
```

### Customer Information

```
First Name: ____________________   Last Name: ____________________
Phone (Primary): ____________________   Phone (Alt): ____________________
Email: ________________________________________________________

Account Type:
☐ Retail   ☐ Fleet   ☐ Insurance Partner   ☐ Motor Club
Fleet / Account #: ____________________   Coverage Verified? ____________________
```

### Vehicle Information

> Capture **basic** vehicle information from the caller. License plate, state, and VIN are **not** collected at intake — the technician will capture them on-scene during the Vehicle Condition Report (§7).

```
Year: ____________________   Make: ____________________
Model: ____________________   Color: ____________________

Fuel Type:
☐ Gasoline (Reg)   ☐ Gasoline (Prem)   ☐ Diesel   ☐ Hybrid   ☐ EV
```

### Location

```
Street Address or Intersection: __________________________________________
City / ZIP: ____________________________________________________________
GPS Lat: ____________________   GPS Long: ____________________

Location Description (landmark, parking lot, freeway direction, milepost):
____________________________________________________________
____________________________________________________________
```

### Service Requested

```
☐ Jump Start             ☐ Tire Change            ☐ Lockout
☐ Fuel Delivery          ☐ Mobile Mount & Balance ☐ Mobile Mechanic

Priority:
☐ Standard   ☐ Urgent   ☐ Hazard
```

### Hazard Screen

```
☐ Vehicle is on an active travel lane or unsafe shoulder
☐ Children, elderly, or pets are in or near the vehicle
☐ Caller reports a medical issue, intoxication, or impairment
☐ Caller reports a hostile party, theft attempt, or domestic situation
☐ Severe weather (storm, flooding, ice, extreme heat) on scene
☐ None of the above
```

### Quote and Payment

```
Service Fee Quoted: ____________________   Surcharges: ____________________
Estimated Total: ____________________      ETA Quoted: ____________________

Payment Method:
☐ Card (now)   ☐ Card on File   ☐ Cash on Arrival   ☐ Fleet Invoice
```

### Dispatch

```
Assigned Technician: ____________________   Dispatched At: ____________________
Truck #: ____________________               Estimated Arrival: ____________________

Dispatcher Signature: __________________________________________________
```

\newpage

# 6. Service Authorization, Charges, and Liability Waiver

Customer-facing legal document. **Must be signed before any service work begins.** The technician retains the signed original; a copy is given to the customer.

> **⚖ Legal Notice.** The waiver language below is a working draft and should be reviewed by Oregon-licensed legal counsel prior to commercial use. Do not modify the language without attorney approval. State-specific disclosures may be required.

```
SERVICE AUTHORIZATION, CHARGES & LIABILITY WAIVER
White Knight Roadside, LLC · Portland, Oregon

Job ID: ____________________            Date: ____________________
Time of Service: ____________________   Technician: ____________________
```

### Customer and Vehicle

```
Customer First Name (printed): ____________________
Customer Last Name (printed): ____________________
Address: _______________________________________________________________
Phone: ____________________   Email: ____________________

Vehicle (Year / Make / Model / Color):
____________________________________________________________

License Plate / State: ____________________   VIN: ____________________
```

### Service Requested

```
☐ Jump Start             ☐ Tire Change            ☐ Lockout
☐ Fuel Delivery          ☐ Mobile Mount & Balance ☐ Mobile Mechanic

Description of work to be performed:
____________________________________________________________
____________________________________________________________
```

### Authorization to Perform Service

I, the undersigned, certify that I am the registered owner of the vehicle described above, or am otherwise lawfully authorized to request service on this vehicle. I authorize White Knight Roadside, LLC and its technician to perform the service indicated above. I understand the published rate for the service, surcharges that may apply, and I agree to pay the **Total Agreed** amount upon completion.

### Customer-Provided Parts Acknowledgement (if applicable)

If I have provided parts for installation, I acknowledge that White Knight Roadside has not sourced, vetted, or warranted these parts. White Knight provides a workmanship warranty on installation only. If a customer-provided part fails, is incorrect, or proves defective, I am responsible for the cost of replacement parts and the labor to re-install. I have been offered the option of having White Knight source parts under company warranty, and I have declined that option for this job.

```
☐ Not applicable — no customer-provided parts
☐ Applicable — initials required: _______
```

### Used Tire AS-IS Acknowledgement (W-K-Supplied Tire Only)

If White Knight Roadside is supplying a tire for this service, I acknowledge **all** of the following:

- The tire is **used**, not new.
- White Knight purchased the tire from a third-party used-tire supplier en route to this service.
- The tire has been verified to **match** the size specification of my vehicle. Brand, model, and tread pattern may differ from my other tires. Brand match is **not** guaranteed; only fitment match is guaranteed.
- The tire is sold **AS-IS, with no warranty of any kind from White Knight Roadside.** White Knight makes no representation about the tire's remaining life, performance characteristics, or freedom from latent defects beyond the on-arrival visual inspection performed in my presence.
- The DOT date code, visible tread depth, and any visible defects have been disclosed to me, and I have been shown the tire prior to installation.
- White Knight will dispose of my removed tire as a courtesy through the company's tire-supplier relationship.

I have inspected the tire prior to installation and accept it for use on my vehicle.

```
☐ Not applicable — customer-supplied tire
☐ Not applicable — service did not include tire
☐ Applicable — signature below

Tire DOT Date Code (recorded by technician): ____________________
Tread Depth in 32nds (recorded by technician): ____________________
Brand / Model if visible (recorded by technician): ____________________
```

### Post-Service Responsibilities (if applicable)

Following tire service (mount, balance, or change), I understand that I am responsible for: re-torquing lug nuts after 50–100 miles of driving; rechecking tire pressures within 24 hours; observing the TPMS warning light through the relearn cycle. Following any mechanical repair, I understand that I should not exceed manufacturer-specified break-in conditions for newly installed components (e.g., bedding-in new brake pads per manufacturer guidance) and that any unusual noise, vibration, or warning indicator should be reported to White Knight promptly.

### Release of Liability

I understand that mobile automotive services, by their nature, are performed in field conditions on a vehicle whose pre-existing condition is unknown to the technician. In consideration of the service provided, I release White Knight Roadside, LLC, its owners, employees, and agents from any and all claims arising from or related to:

- Minor cosmetic damage incidental to lockout, tire change, or mount-and-balance services.
- Failure of parts not supplied by White Knight.
- Preexisting damage and damage caused as a result of preexisting damage.
- Damage that results because I provided incorrect information about the vehicle (for example, the wrong fuel type).
- Indirect or secondary costs that may arise from the service — such as towing, rental car, lost wages, or missed appointments.
- Additional costs if the scope of service changes.
- Reduced or no warranty on parts I provided.
- Additional dispatch-related charges if a defect is not present at the time of service and a return visit is required.

White Knight's liability is limited to the direct cost of the work performed. **This release does not cover damages caused by gross negligence or willful misconduct.**

### Photo Documentation Consent

I consent to the technician photographing the vehicle, work area, and work performed for service documentation. Photos are retained by White Knight Roadside as part of the service record and are not shared publicly.

### Agreed Charges

The technician records the agreed charges below, in the customer's presence, before the customer signs. The customer's signature on this Waiver authorizes payment of these amounts on completion of the work described above.

| Description | Amount |
|---|---:|
| Service Fee | $ |
| Surcharges (after-hours, mileage, hazard, etc.) | $ |
| Parts / Tire / Fuel (W-K-supplied, if any) | $ |
| Subtotal | $ |
| Tax | $ |
| **TOTAL AGREED** | **$** |

If the scope of work or required parts changes after this Waiver is signed, the technician will **STOP** work. Per Oregon law and company policy (§1.8), a written Change Order signed by the customer is required for any cost increase that exceeds the **lesser of 10% of the TOTAL AGREED above or $200**. Smaller changes are logged and verbally communicated but do not require a signature. **No charges beyond the TOTAL AGREED above (or a customer-signed Change Order amendment, where required) will appear on the final Receipt.**

I agree to pay the TOTAL AGREED amount upon completion of service. I understand that disputed charges, charge-backs without notice, or non-payment may result in collection action and may be reported to credit reporting agencies. **A returned-payment fee of $35 applies to dishonored payments.**

### Arbitration and Venue

Any dispute arising from this agreement shall first be addressed through good-faith negotiation. Disputes that cannot be resolved within 30 days shall be submitted to binding arbitration in Multnomah County, Oregon, under the rules of the American Arbitration Association. Both parties waive the right to jury trial.

### Customer Signature — Authorization, Charges, and Waiver

By signing below, I acknowledge that I have read, understood, and agreed to all terms above. With this single signature I am: (1) **authorizing** White Knight Roadside, LLC to perform the service described above on the vehicle described above; (2) **agreeing** to pay the TOTAL AGREED amount on completion (or any Change Order amendment I sign); and (3) **accepting** the disclosures, risk acknowledgements, and release of liability set out in this document.

```
Customer Signature: ___________________________________________________
Customer Printed Name: ________________________________________________
Date: ____________________   Time: ____________________
```

### Technician Attestation

I confirm that I reviewed this authorization with the customer prior to commencing work, and that the customer signed in my presence.

```
Technician Signature: __________________________________________________
Technician Printed Name: ____________________   Date / Time: ____________________
```

\newpage

# 7. Pre-Service Vehicle Condition Report

Completed by the technician on-scene before any work begins. Documents existing damage, body condition, and equipment present. **Protects the company from claims that pre-existing damage was caused during service.**

```
VEHICLE CONDITION REPORT
Pre-Service Inspection — Required Before Work Begins

Job ID: ____________________   Date / Time: ____________________
Vehicle (Year / Make / Model / Color):
____________________________________________________________
```

### On-Scene Vehicle Identification (required)

> License plate, state, VIN, and odometer are **not** collected at intake. The technician captures them on-scene before any work begins. These identifiers are the foundation of the formal `Vehicle` record that will be created at Invoice.

```
License Plate: ____________________   State: ____________________
Odometer (if visible): ____________________________________________________________
VIN (17 characters): ____________________________________________________________

VIN Location Used:
☐ Dashboard (windshield)   ☐ Driver door jamb   ☐ Engine bay
☐ Customer-provided (registration / insurance)   ☐ Unable to capture (note reason)

If unable to capture, reason: ______________________________________________
```

### Exterior Condition

Mark the condition of each panel. Note location of any pre-existing damage with description (scratch, dent, paint chip, prior repair).

| Panel | OK | Damaged | Description / Location |
|---|:---:|:---:|---|
| Front Bumper / Grille | ☐ | ☐ | |
| Hood | ☐ | ☐ | |
| Driver Front Fender | ☐ | ☐ | |
| Passenger Front Fender | ☐ | ☐ | |
| Driver Front Door | ☐ | ☐ | |
| Passenger Front Door | ☐ | ☐ | |
| Driver Rear Door | ☐ | ☐ | |
| Passenger Rear Door | ☐ | ☐ | |
| Driver Rear Quarter | ☐ | ☐ | |
| Passenger Rear Quarter | ☐ | ☐ | |
| Trunk / Tailgate | ☐ | ☐ | |
| Rear Bumper | ☐ | ☐ | |
| Roof | ☐ | ☐ | |
| Wheels (note any curb rash) | ☐ | ☐ | |
| Glass (windshield, side, rear) | ☐ | ☐ | |

### Mechanical Observations

```
☐ Battery shows visible damage (cracking, bulging, leaking, heavy corrosion)
☐ Tires show pre-existing damage (sidewall bulge, cord exposure, dry rot)
☐ Wheel hardware shows corrosion, cross-threading, or aftermarket modification
☐ Fluid leaks visible under vehicle (specify type and location below)
☐ Aftermarket modifications (lift kit, large tires, performance battery)
☐ None observed

Notes: __________________________________________________________________
        __________________________________________________________________
```

### Items Visible Inside Vehicle (Lockout Only)

Document items of value visible from outside the vehicle prior to entry.

```
__________________________________________________________________
__________________________________________________________________
```

### Photos Taken (check all)

```
☐ Front       ☐ Rear            ☐ Driver Side   ☐ Passenger Side
☐ Damage Area Close-Up           ☐ Battery       ☐ Affected Tire
☐ Door Surface (lockout)

Technician Signature: __________________________________________________
```

\newpage

# 8. On-Scene Hazard Assessment

Required before commencing service at any location. Technician assesses environmental, traffic, and personal safety risks. **If any high-risk condition is present, work must not proceed until mitigated, or the call must be aborted with written justification.**

```
HAZARD ASSESSMENT CHECKLIST
Complete Before Service Begins

Job ID: ____________________   Date / Time: ____________________
Technician: ____________________   Location Type: ____________________
```

### Traffic Hazards

```
☐ Vehicle is on a parking lot, driveway, or low-traffic surface — LOW RISK
☐ Vehicle is on a residential street with light traffic — LOW RISK
☐ Vehicle is on a commercial road or arterial with moderate traffic — MEDIUM RISK
☐ Vehicle is on a highway shoulder with light traffic and good visibility — MEDIUM RISK
☐ Vehicle is on a highway shoulder with heavy traffic, poor visibility, or near a curve — HIGH RISK
☐ Vehicle is on or partially in an active travel lane — DO NOT PROCEED. Call for traffic control or police escort.
```

### Environmental Hazards

```
☐ Heavy rain or standing water at work area
☐ Ice, snow, or frozen surface
☐ Lightning or active thunderstorm
☐ Extreme heat (above 95°F) or extreme cold (below 25°F)
☐ Poor lighting (sunset to sunrise without adequate scene lighting)
☐ Soft, sloped, or unstable surface (may compromise jack stability)
```

### Vehicle and Scene Hazards

```
☐ Visible fluid leaks (especially fuel)
☐ Smoke, smell of burning, or warning lights indicating fire risk
☐ Vehicle is loaded with hazardous materials
☐ Vehicle has been in a collision (any visible recent damage)
☐ Damaged power lines, fallen trees, or other infrastructure hazards near vehicle
```

### Personal Safety

```
☐ Customer appears intoxicated, agitated, or impaired
☐ Hostile bystanders or threatening behavior present
☐ Suspicious circumstances (vehicle does not match customer description, customer cannot verify ownership)
☐ Domestic dispute or law enforcement situation evident
```

### Mitigation Actions Taken

```
☐ Reflective triangles deployed
☐ Service truck positioned as a barrier (where safe)
☐ LED safety beacons activated
☐ High-visibility vest worn
☐ Customer moved off roadway
☐ 911 / Oregon State Police contacted
☐ Tow Referral offered
```

### Decision

```
☐ Proceed with service
☐ Proceed after mitigation (note below)
☐ Abort — Tow Referral
☐ Abort — Safety / Law Enforcement

Notes / Justification: _________________________________________________
                       _________________________________________________

Technician Signature: __________________________________________________
Date: ____________________   Time: ____________________
```

\newpage

# 9. Service Log

Time-stamped record of work performed. Completed by the technician during and immediately after service. **Becomes part of the permanent service record.**

```
SERVICE LOG
Technician's Record of Work Performed

Job ID: ____________________      Service Date: ____________________
Technician: ____________________   Truck #: ____________________
Customer / Vehicle: __________________________________________________
```

### Time Log

| Event | Time | GPS Verified? |
|---|---|:---:|
| Dispatch received | | ☐ |
| En route | | ☐ |
| On scene | | ☐ |
| Hazard assessment complete | | ☐ |
| Waiver signed | | ☐ |
| Service started | | ☐ |
| Service completed | | ☐ |
| Payment collected | | ☐ |
| Cleared scene | | ☐ |

### Work Performed

```
Service Type:
☐ Jump Start             ☐ Tire Change            ☐ Lockout
☐ Fuel Delivery          ☐ Mobile Mount & Balance ☐ Mobile Mechanic

Description of work performed (be specific — what, where, how):
____________________________________________________________
____________________________________________________________
____________________________________________________________
____________________________________________________________
```

### Measurements / Readings

(Use as applicable to service type.)

```
Battery voltage at rest: ____________________   Battery voltage running: ____________________
Tire pressure (spare): ____________________      Lug nut torque (ft-lbs): ____________________
Fuel delivered (gal): ____________________       Fuel type: ____________________

Other observations: ____________________________________________________
```

### Parts and Supplies Used

| Item | Qty | Customer Cost |
|---|---|---:|
| | | $ |
| | | $ |
| | | $ |

### Photo Inventory

```
Confirm photos uploaded for this job:
☐ Pre-service condition   ☐ During service   ☐ Post-service result
Total photos: ____________________   Photos uploaded? ____________________
```

### Outcome

```
☐ Service successful   ☐ Partial — see notes
☐ Service refused by customer   ☐ Service unable to complete

If unsuccessful, reason and next step (Tow Referral, parts needed, etc.):
____________________________________________________________
____________________________________________________________

Technician Signature: __________________________________________________
```

\newpage

# 10. Customer Refusal and Decline-of-Service Forms

## 10.1 When to Use

- Customer changes their mind after the technician arrives but before work begins.
- Customer refuses to sign the Liability Waiver.
- Customer refuses the recommended approach (e.g., insists on jump start when battery is visibly leaking).
- Technician must decline service due to safety, identity verification failure, or scope-of-service issue.

## 10.2 Customer Refusal of Service Form

```
CUSTOMER REFUSAL OF SERVICE
Job ID: ____________________   Date / Time: ____________________
Technician: ____________________   Vehicle: ____________________
```

### Reason for Refusal

```
☐ Customer changed their mind
☐ Customer disputes the price
☐ Customer will not sign liability waiver
☐ Customer refused recommended approach
☐ Customer requested service outside our scope
☐ Other (describe below)

Details: _____________________________________________________________
         _____________________________________________________________
```

### Charges

Per company policy (see §1.7), a dispatch fee of $______ applies when the customer changes their mind or cancels **after** the technician has arrived on scene. Any pre-paid amount above the dispatch fee is refunded. **This is non-discretionary; waiver requires Owner approval logged in the Service Request.**

```
☐ Dispatch fee charged   ☐ Owner-approved waiver
Owner approval (if waived): ___________________________________________
```

### Technician Attestation

I attest that the customer declined the service offered by White Knight Roadside before any work was performed on the vehicle. The customer was informed that no warranty or guarantee applies and that the dispatch fee (if applicable per §1.7) covers our arrival on scene.

```
Technician Signature: __________________________________________________
Date: ____________________   Time: ____________________
```

## 10.3 Technician Decline-of-Service Form

Used when the **technician** (not the customer) makes the decision to decline service. The technician documents and signs; **no customer signature is required**. If the customer wants to be referred to a tow company, the technician provides contact information directly to the customer (see §12).

```
TECHNICIAN DECLINE OF SERVICE
Job ID: ____________________   Date / Time: ____________________
Technician: ____________________   Vehicle: ____________________
```

### Reason for Decline

```
☐ Hazard assessment indicates unacceptable risk (see §8)
☐ Vehicle condition makes service unsafe or impossible (e.g., leaking battery, blown sidewall, multiple flats)
☐ Identity could not be verified (lockout)
☐ Scope of work exceeds light-duty roadside services
☐ Customer behavior makes service unsafe (intoxication, hostility)
☐ Equipment limitation (e.g., vehicle exceeds jack capacity)

Detailed explanation: _________________________________________________
                      _________________________________________________
                      _________________________________________________
```

### Alternative Offered

```
☐ Tow Referral   ☐ Reschedule   ☐ None applicable

Notes: ______________________________________________________________
```

### Charges and Refund

When the technician declines service, **no fees are charged** — including no dispatch fee. Any pre-paid amount is refunded in full. **This is non-discretionary policy (see §1.7).**

```
☐ No pre-payment was taken — no refund needed
☐ Pre-payment refunded in full (record method and date below)

Refund Amount: ____________________   Refund Method: ____________________
Refund Date: ____________________     Processed By: ____________________

Technician Signature: __________________________________________________
Owner / Dispatcher Notification (name): _________________________________
```

\newpage

# 11. Customer Receipt

Provided to the customer at completion of service. A digital copy is also sent via SMS or email per the customer's preference.

```
OFFICIAL SERVICE RECEIPT
White Knight Roadside, LLC · Portland, Oregon

Receipt #: ____________________   Job ID: ____________________
Date: ____________________        Time: ____________________

Customer: _____________________________________________________________
Vehicle (Year / Make / Model / Plate): _________________________________
Service Address: ______________________________________________________
```

### Service Performed

```
☐ Jump Start             ☐ Tire Change            ☐ Lockout
☐ Fuel Delivery          ☐ Mobile Mount & Balance ☐ Mobile Mechanic

Description: __________________________________________________________
              __________________________________________________________
```

### Technician

```
Technician Name: ____________________   Truck #: ____________________
Time on Scene: ____________________     Time Cleared: ____________________
```

### Charges

| Description | Amount |
|---|---:|
| Service Fee | $ |
| After-Hours Surcharge | $ |
| Mileage Surcharge | $ |
| Hazard / Difficulty Surcharge | $ |
| Parts / Fuel | $ |
| Subtotal | $ |
| Tax | $ |
| **TOTAL PAID** | **$** |

### Payment

```
☐ Visa   ☐ MC   ☐ AMEX   ☐ Discover   ☐ Cash   ☐ Fleet Account
Last 4 of Card: ____________________   Authorization Code: ____________________
```

### Reviews Help Small Businesses Like Ours

If you'd like to share your experience, here is the link — no pressure either way.

| | |
|---|---|
| **Google Review** | `https://g.page/r/`**`WhiteKnightRoadsidePDX`**`/review` |
| **Or scan** | *[QR code rendered here on the digital receipt]* |

This is the only place we'll ever ask. We won't call, text, or email you about a review.

### Warranty and Comeback Policy

- **Workmanship.** 12 months or 12,000 miles, whichever comes first, on labor performed.
- **White-Knight-supplied parts.** Pass-through of the manufacturer warranty, with White Knight handling the warranty claim on your behalf.
- **Comeback policy.** If you experience a warranty-period issue that appears related to our work, call (503) 764-3154 — we will return to assess at no charge. If the issue is our work, repair is free; if unrelated, standard rates apply (you'll be informed first).

Warranty does not cover damage from accident, abuse, neglect, modification by others, or operation outside manufacturer specifications. Customer-provided parts: workmanship-only warranty applies.

### Customer Signature

By signing below, I acknowledge that the service described above has been completed to my satisfaction and that I have received this receipt as my record of the work performed.

```
Customer Signature: __________________________________________________
Date: ____________________   Time: ____________________
Technician Signature: ________________________________________________
```

> *Thank you for choosing White Knight Roadside. Drive safely!*
>
> Questions about your service? Call (503) 764-3154 or email service@wkrllc.com. Please retain this receipt for your records.

\newpage

# 12. Tow Referral — Information Provided Log

Used when a service cannot be completed within scope and the customer would benefit from towing. **White Knight does not share customer information with any third party.** The technician provides the customer with publicly-available contact information for one or more tow companies; the customer makes their own arrangements directly. **There are no current tow partners — referrals are courtesy information only.**

This form is internal documentation that the referral was offered. **The customer signs nothing.** The technician documents and signs.

```
TOW REFERRAL — INFORMATION PROVIDED
Service Could Not Be Completed Within Scope

Job ID: ____________________   Date / Time: ____________________
Technician: ____________________   Vehicle: ____________________
```

### Reason for Referral

```
☐ Vehicle requires towing — cannot be repaired on-site
☐ Diagnosis inconclusive on-scene
☐ Repair scope exceeds Mobile Mechanic capability
☐ Unsafe location requires professional recovery
☐ Customer preferred off-site repair
☐ Other: __________________________________________________________
```

### Information Provided to Customer

Record the tow company contact information that was given to the customer (verbal, written, or both). The customer arranges their own towing directly.

```
Tow Company Name(s): ___________________________________________________
Phone Number(s): _______________________________________________________

Method of Delivery:
☐ Verbal only   ☐ Written/handed to customer   ☐ Texted to customer
```

### Customer Acknowledgement (Technician-Observed)

```
☐ Customer accepted the information
☐ Customer declined the information
☐ Customer already had a tow company in mind

Technician Signature: __________________________________________________
Date: ____________________   Time: ____________________
```

\newpage

# 13. Incident and Damage Reports

## 13.1 When to File

- Any time the customer alleges damage caused by service (immediate or post-service).
- Any time the technician observes damage occurring during service, regardless of severity.
- Any injury to technician, customer, or bystander.
- Any near-miss with significant safety implication.
- Any property damage to third-party property (other vehicles, structures, landscaping).

## 13.2 Filing Timeline

- **Initial report:** within 1 hour of incident, by phone to Owner.
- **Written report:** within 24 hours, using the form below.
- **Photos:** uploaded immediately at scene where possible, before vehicle is moved or scene is altered.

## 13.3 Incident Report Form

```
INCIDENT REPORT
Incident ID: ____________________   Date / Time: ____________________
Reported By: ____________________   Reported At: ____________________
Linked Job ID: ____________________   Severity: ____________________
```

### Incident Type

```
☐ Vehicle Damage   ☐ Property Damage   ☐ Injury
☐ Near Miss        ☐ Theft / Allegation ☐ Other
```

### People Involved

```
Technician(s): _________________________________________________________
Customer: ______________________________________________________________
Other parties (with contact info): _____________________________________
                                    _____________________________________
Witnesses (with contact info): _________________________________________
                               _________________________________________
```

### Location

```
Address: _______________________________________________________________
GPS: ____________________   Type of Location: ____________________
```

### What Happened

Provide a complete factual narrative. Include sequence of events, equipment in use, weather, and any factors believed to have contributed.

```
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
```

### Damage / Injury Description

```
Description of damage or injury: _______________________________________
                                 _______________________________________
                                 _______________________________________

Estimated cost (if known): _____________________________________________
Medical attention required? ____________________________________________
```

### Evidence Collected

```
☐ Scene photos      ☐ Vehicle photos    ☐ Damage photos
☐ Witness statements ☐ Police report    ☐ Dashcam footage

Notes: _________________________________________________________________
```

### Initial Customer Communication

What was communicated to the customer at the scene:

```
________________________________________________________________________
________________________________________________________________________
```

> **⚠ Do not admit fault, accept liability, or offer compensation in the field.** Direct all damage discussions to the Owner.

### Follow-Up Required

```
☐ Insurance notified   ☐ Owner notified   ☐ Customer follow-up scheduled
☐ Police filed        ☐ Legal counsel notified

Technician Signature: __________________________________________________
Owner Acknowledgement: _________________________________________________
Date Acknowledged: ____________________   Status: ____________________
```

\newpage

# 14. Daily Truck Inspection

Completed by each technician at the start of every shift. Identifies vehicle and equipment issues before they cause a service failure or safety incident. **The truck must not leave the yard with any "Fail" item unless approved by the Owner.**

```
DAILY TRUCK INSPECTION
Date: ____________________      Shift Start Time: ____________________
Technician: ____________________   Truck #: ____________________
Odometer: ____________________   Fuel Level (¼, ½, ¾, F): ____________________
```

### Vehicle — Exterior

| Item | Pass | Fail | Notes |
|---|:---:|:---:|---|
| Body / panels (no new damage) | ☐ | ☐ | |
| All lights working (head, tail, brake, turn, hazard) | ☐ | ☐ | |
| Roof beacon / amber lights operational | ☐ | ☐ | |
| Windshield free of cracks | ☐ | ☐ | |
| Tires (tread + pressure) | ☐ | ☐ | |
| License plate visible and current | ☐ | ☐ | |

### Vehicle — Interior

| Item | Pass | Fail | Notes |
|---|:---:|:---:|---|
| Cab clean and organized | ☐ | ☐ | |
| Mirrors adjusted, clean | ☐ | ☐ | |
| Horn operational | ☐ | ☐ | |
| Wipers / washer fluid | ☐ | ☐ | |
| Registration & insurance present | ☐ | ☐ | |
| Phone mount and charger | ☐ | ☐ | |

### Fluids and Mechanical

| Item | Pass | Fail | Notes |
|---|:---:|:---:|---|
| Engine oil level | ☐ | ☐ | |
| Coolant level | ☐ | ☐ | |
| Brake fluid | ☐ | ☐ | |
| No visible leaks under truck | ☐ | ☐ | |
| Brakes feel firm | ☐ | ☐ | |

### Roadside Equipment Inventory

| Item | Present | Working | Notes |
|---|:---:|:---:|---|
| Jump pack (charge level: ____ %) | ☐ | ☐ | |
| Lockout kit complete (wedges, long-reach, cloths) | ☐ | ☐ | |
| Tire change kit (jack, lug wrench, torque wrench, chocks) | ☐ | ☐ | |
| Impact gun + sockets | ☐ | ☐ | |
| Fuel cans (2.5 + 5 gal, both clean) | ☐ | ☐ | |
| Fuel funnel with filter | ☐ | ☐ | |
| Multimeter | ☐ | ☐ | |
| Reflective triangles (3) | ☐ | ☐ | |
| LED safety beacons | ☐ | ☐ | |
| Class B fire extinguisher (charge: ____) | ☐ | ☐ | |
| First aid kit | ☐ | ☐ | |
| High-visibility vest | ☐ | ☐ | |
| Safety glasses + gloves | ☐ | ☐ | |
| Spill containment pads / absorbent | ☐ | ☐ | |
| Flashlight + spare batteries | ☐ | ☐ | |

### Mount & Balance Equipment (if equipped for this shift)

| Item | Present | Working | Notes |
|---|:---:|:---:|---|
| Custom leverage tire mount/demount machine — frame, welds, lever bars inspected | ☐ | ☐ | |
| Manual bead breaker — leverage arm, hinge, pads | ☐ | ☐ | |
| Level (bubble) balancer — self-leveling mechanism functional, bubble visible, post true, cones present | ☐ | ☐ | |
| Mount/demount lever bars (matched set, no burrs) | ☐ | ☐ | |
| Valve stems — rubber (qty ≥ 8) | ☐ | ☐ | |
| Valve stems — TPMS clamp-in (qty ≥ 4) | ☐ | ☐ | |
| TPMS service kits (qty ≥ 4) | ☐ | ☐ | |
| TPMS relearn tool | ☐ | ☐ | |
| Wheel weights — clip-on assortment | ☐ | ☐ | |
| Wheel weights — adhesive assortment | ☐ | ☐ | |
| Tire lubricant / mounting paste | ☐ | ☐ | |
| Inflator with regulated supply | ☐ | ☐ | |
| Tire pressure gauge (calibrated) | ☐ | ☐ | |
| Tread depth gauge | ☐ | ☐ | |
| Floor jack (4-ton min) + rated jack stands | ☐ | ☐ | |
| Calibrated torque wrench (range covers lug spec) | ☐ | ☐ | |
| ANSI Z87.1 safety eyewear (mandatory) | ☐ | ☐ | |

### Mobile Mechanic Equipment (if equipped for this shift)

| Item | Present | Working | Notes |
|---|:---:|:---:|---|
| OBD-II scanner with current software | ☐ | ☐ | |
| Digital multimeter (auto-ranging) | ☐ | ☐ | |
| Battery load tester / conductance tester | ☐ | ☐ | |
| Mechanic's tool roll (sockets, wrenches, screwdrivers, pliers) | ☐ | ☐ | |
| Brake service tools (caliper compressor, hub puller) | ☐ | ☐ | |
| Oil drain pan + transfer pump | ☐ | ☐ | |
| Funnel set (filtered) | ☐ | ☐ | |
| Coolant flush/fill kit | ☐ | ☐ | |
| Belt tension gauge | ☐ | ☐ | |
| Calibrated torque wrenches (¼", ⅜", ½") | ☐ | ☐ | |
| Truck stock — common batteries (Group 24F, 35, 65, 75, 78) | ☐ | — | |
| Truck stock — common oil filters | ☐ | — | |
| Truck stock — motor oil (5W-20, 5W-30, 0W-20) | ☐ | — | |
| Truck stock — coolant (universal + Dex-Cool) | ☐ | — | |
| Truck stock — brake fluid (DOT 3/4) | ☐ | — | |
| Truck stock — common bulbs and fuses | ☐ | — | |
| Used-fluid containers (oil, coolant, brake) — capacity remaining | ☐ | ☐ | |
| Drop cloth / fender covers | ☐ | — | |
| Parts tray / magnetic tray | ☐ | — | |
| Disposable gloves (nitrile, qty ≥ 20) | ☐ | — | |

### Failed Items / Out-of-Service Triggers

List any failed items, action taken, and Owner notification.

```
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________

Technician Signature: __________________________________________________
Time Completed: ____________________   Truck Released for Service? ____________________
```

\newpage

# 15. End-of-Shift Reconciliation

Completed at the end of each shift. Reconciles cash, payments, jobs, and equipment. **Submitted to the Owner before the technician's shift is considered closed.**

```
END-OF-SHIFT RECONCILIATION
Date: ____________________      Shift End Time: ____________________
Technician: ____________________   Truck #: ____________________
Shift Start Odometer: ____________________   Shift End Odometer: ____________________
Total Miles: ____________________   Fuel Added: ____________________
```

### Jobs Completed

| Job ID | Service Type | Total $ | Payment Method |
|---|---|---:|---|
| | | $ | |
| | | $ | |
| | | $ | |
| | | $ | |
| | | $ | |
| | | $ | |
| **TOTAL** | | **$** | |

### Cash Reconciliation

```
Cash Float at Start: ____________________   Cash Float at End: ____________________
Cash Collected (jobs): ____________________   Cash Spent (fuel, supplies): ____________________

Notes / Discrepancy explanation: _______________________________________
```

### Card Payments

```
Total Card Payments: ____________________   Square Batch Closed? ____________________
Refunds processed (with reason): _______________________________________
```

### Outstanding Items

```
Jobs not completed (and reason): _______________________________________
                                 _______________________________________
Customer follow-ups required: __________________________________________
                              __________________________________________
Equipment issues / supplies needed: ____________________________________
                                    ____________________________________
Incidents or notable events: ___________________________________________
                             ___________________________________________
```

### Vehicle and Equipment Check-Out

```
☐ Truck refueled to ≥ ½ tank
☐ Trash removed from cab
☐ Jump pack on charger
☐ Fuel cans empty and properly stored
☐ Equipment counted and accounted for
☐ All paperwork submitted

Technician Signature: __________________________________________________
Owner / Dispatcher Acknowledgement: ____________________________________
```

\newpage

# 16. Photo Documentation Protocol

Photos are the company's primary defense against false damage claims and the primary record that work was performed correctly. **Every job requires a baseline photo set; specific service types require additional photos as listed below.**

## 16.1 Universal Requirements (Every Job)

1. Vehicle exterior — front, rear, driver side, passenger side. Capture entire vehicle in frame.
2. Any pre-existing damage observed during the Vehicle Condition Report.
3. Service location wide-angle (shows context, surroundings, weather).
4. Customer-signed liability waiver (photograph the signed page).
5. Final result confirming completed work (running engine via dash photo, mounted spare, opened door, fuel cap secured, etc.).

## 16.2 Service-Specific Additions

### Jump Start

- Battery condition before service
- Multimeter reading at rest (showing voltage)
- Clamp placement on both terminals
- Multimeter reading with engine running

### Tire Change

- Damaged tire with failure mode visible
- Spare tire pressure reading and tread
- Jack placement under designated jack point
- Mounted spare with all lug nuts visible
- Torque wrench in use (action shot)

### Lockout

- All four sides of vehicle **before** entry attempt
- Door surface near work area **before** entry attempt
- Customer ID matching registration (do not retain — view only)
- Door surface near work area **after** entry
- Customer testing door operation post-service

### Fuel Delivery

- Fuel door label confirming fuel type
- Fuel can(s) being used
- Funnel inserted in fuel filler neck
- Fuel cap secured after delivery
- Vehicle started and running (dash showing fuel gauge moved)

### Mobile Mount & Balance

- Damaged tire still on vehicle (sidewall size markings visible) — proof of size at the time of intake
- Old tire wear pattern and any sidewall damage / failure mode
- If W-K-supplied: candidate tire at the supplier — sidewall size markings clearly visible
- If W-K-supplied: DOT date code on the purchased tire (4-digit week/year)
- If W-K-supplied: tread depth measurement on the purchased tire (gauge in frame)
- Each rim before mounting (curb damage, corrosion, hub-centric area)
- Rim bead seat after wire-brushing
- Each new valve stem installed (and TPMS sensor if applicable)
- Bubble centered in the indicator with final weights applied — one photo per wheel
- Torque wrench in use on at least one lug per wheel
- Installed tire on vehicle (final shot, valve stem visible)
- TPMS dash indicator showing all four sensors reading post-relearn
- Old (removed) tire loaded in service truck for disposal
- Signed customer disclosures: AS-IS Used Tire (if W-K-supplied) AND static-balance limitations

### Mobile Mechanic

- Pre-work walk-around (4 sides) before opening hood or starting work
- OBD-II scan results screen (codes present and freeze-frame data)
- Failed/old part in place before removal
- Old part removed, side-by-side with new part for comparison
- Critical fastener torque (action shot with calibrated wrench)
- New part installed in place
- Post-work scan showing codes cleared / no new codes
- For oil change: drain plug torqued, new filter installed, oil-life reset on dash
- For brakes: pad thickness on old vs. new, rotor surface (old vs. new), caliper slide condition
- For battery: voltage test before, voltage test after, terminal cleanliness, hold-down secured
- Customer-provided parts (if any): packaging, part number, condition at delivery
- Work area at completion (clean, no fluids, customer's space restored)

## 16.3 Photo Quality Standards

- In focus, well-lit, subject centered
- Timestamps and GPS metadata enabled on device
- Minimum resolution 1920 × 1080
- Each photo uploaded to the job record before clearing the scene

## 16.4 Storage and Retention

All job photos are retained in the RoadRunner Admin system for a minimum of **7 years**. Photos linked to incidents are retained **indefinitely** or until any related claim is fully resolved, whichever is longer. Photos are never shared publicly or used for marketing without separate written customer consent.

\newpage

# 17. SMS Customer Location Capture (Telnyx + 10DLC Compliant)

## 17.1 Purpose

Many roadside callers cannot accurately describe their location — especially on freeways, in unfamiliar areas, or when stressed or injured. White Knight Roadside uses an SMS-driven location capture system: the dispatcher sends a one-tap text to the customer that opens a webpage hosted on `whiteknightroadside.com`. The customer grants location permission in the browser, and their precise GPS coordinates are returned to the dispatch system within seconds.

This section governs how the SMS is sent, what the message says, what the webpage does, how customer consent is obtained, and how the operation maintains 10DLC compliance with U.S. wireless carriers. **Every dispatcher must follow this procedure exactly.** Deviations are not authorized; non-compliant SMS practices put the company's entire messaging capability at risk of carrier suspension.

## 17.2 Compliance Foundations

This section is operational. Complete 10DLC compliance documentation — brand registration with The Campaign Registry (TCR), campaign approval, use-case selection, and throughput tier — is maintained separately in the **10DLC Compliance Workbook**. Dispatchers do not need to understand the registration mechanics, only follow this procedure.

The following constraints govern every SMS sent through the system. **Each is non-negotiable.**

- The campaign is registered as a **Customer Care** use case for transactional, operational messages tied to an active service request. SMS may **not** be used for promotional, marketing, or broadcast purposes through this number.
- Every recipient must have given **prior express consent** before any SMS is sent. Consent must be documented, time-stamped, and tied to the Service Request.
- Every message must identify the sender ("White Knight Roadside"), include opt-out language ("Reply STOP to opt out"), and include help language ("HELP for help") on the first message in any session.
- **STOP, STOPALL, UNSUBSCRIBE, CANCEL, END,** and **QUIT** keywords must be honored automatically and immediately. Once a customer opts out, no further SMS may be sent to that number from any campaign without re-consent.
- Message content must not include SHAFT-C content (Sex, Hate, Alcohol, Firearms, Tobacco, Cannabis), public URL shorteners (bit.ly, tinyurl, t.co), or content that could appear unsolicited or misleading.
- SMS may not be sent outside the customer's local time hours of **8 AM – 9 PM** (per TCPA), unless the customer initiated contact within the last 30 minutes — which is the standard case for an active roadside service request.

## 17.3 Customer Consent

Consent must be captured before the first SMS is sent. There are two acceptable consent paths.

### Path A — Verbal Consent During Phone Intake

The dispatcher reads the consent script verbatim during the intake call. After the customer gives a clear affirmative response, the dispatcher checks the "Verbal SMS Consent Granted" attestation on the Service Request screen. The system timestamps the check, captures the dispatcher's user ID and the inbound CallerID, and stores this record as documented prior express consent. **This attestation is the consent record.**

Call recording is **not** required for compliance. If White Knight separately chooses to record intake calls for quality assurance, training, or dispute resolution, callers should be informed at the start of the call as a courtesy and best practice. Oregon is a one-party consent state, so the dispatcher's awareness of the recording is legally sufficient — but informing the caller is good form and reduces complaints. QA recordings are an operational practice, not a compliance artifact.

> **Verbal Consent Script (read verbatim).**
>
> *"Before I send a text with a location link, I need your permission to send you SMS messages about this service request. White Knight Roadside will only text you about this job. Standard message and data rates may apply, and you can reply STOP at any time to opt out. Do you give consent to receive these messages?"*

The customer must respond with a clear "yes," "I consent," or equivalent affirmative. **Ambiguous responses** ("I guess so," "if you have to," "sure whatever") must be clarified — re-ask the question. If the customer declines or remains ambiguous, **do not send the SMS.** Capture location verbally instead.

### Path B — App or Web Form Submission

Customers who submit a service request through the White Knight app or web form opt in by checking a clearly-labeled consent checkbox at submission. The checkbox text must read:

> *"I agree to receive SMS messages from White Knight Roadside about this service request. Reply STOP to opt out. Standard message rates apply."*

The checkbox **must not be pre-checked**. A submission with the box unchecked is a non-consent — no SMS may be sent for that request.

## 17.4 Approved Message Templates

Only the message templates below may be sent through the location-capture system. **Variations require campaign re-approval through TCR.** Variable substitution is performed by the dispatcher tool — never by hand.

### Template L1 — Initial Location Request (first SMS in session)

```
White Knight Roadside: Your service request #{JOBID} is being dispatched. Tap to share
your exact location with your technician: {LINK}. Msg&data rates may apply. Reply STOP
to opt out, HELP for help.
```

### Template L2 — Location Reminder (auto-sent if no response in 5 minutes)

```
White Knight Roadside: We still need your location for job #{JOBID}. Tap: {LINK}.
Reply STOP to opt out.
```

### Template L3 — Technician En Route

```
White Knight Roadside: {TECH_NAME} is en route in a {TRUCK_DESCRIPTION}. ETA {ETA_MIN}
minutes. Job #{JOBID}. Reply STOP to opt out.
```

### Template L4 — STOP Confirmation (auto-sent)

```
You have been opted out of White Knight Roadside SMS. We will not text this number
again. Reply START to opt back in.
```

### Template L5 — HELP Response (auto-sent)

```
White Knight Roadside customer care: Call (XXX) XXX-XXXX or email
service@whiteknightroadside.com. Reply STOP to opt out.
```

The `{LINK}` variable is a unique short URL on `whiteknightroadside.com` — for example, `whiteknightroadside.com/loc/A7B4F2`. The link contains a one-time tokenized job ID. **It cannot be reused after the location is captured or after 4 hours, whichever comes first.** Public URL shorteners (bit.ly, tinyurl, t.co) are **forbidden** — major carriers filter messages containing them.

## 17.5 Sending Procedure (Dispatcher)

1. Confirm the Service Request has a complete `CustomerID` with `PhonePrimary` in E.164 format (e.g., `+15035551234`).
2. Confirm the consent flag on the Service Request is set to **Granted** with a timestamp and method (`Verbal` or `Web`).
3. **If consent is not on file, STOP.** Either obtain consent per §17.3 or capture location verbally. **Do not proceed to send.**
4. Click "Send Location SMS" in the Service Request screen. The system selects Template L1, populates `{JOBID}` and `{LINK}`, and sends via Telnyx.
5. Observe the send-status indicator: **Pending → Delivered** (or **Failed**). If Failed, see §17.7.
6. The system starts a 5-minute timer. If no location is received within 5 minutes, the system auto-sends Template L2 once. **No further auto-resends after that.**
7. When the location is received, the Service Request map updates with the captured GPS coordinates and accuracy radius. Verify the location matches the customer's verbal description (or note any discrepancy in the Service Log).

## 17.6 Webpage Procedure (Customer-Facing)

The link in the SMS opens a page on `whiteknightroadside.com/loc/{TOKEN}`. The page is hosted on company-owned infrastructure, served over HTTPS, and presents the customer with a deliberately minimal interface.

### Page Content

- Brand header with White Knight Roadside logo and color scheme
- Plain-English description: *"We need your exact location to dispatch your technician. Tap below to share."*
- A single primary button: **Share My Location**
- Privacy line: *"We use your location only for this service request. We do not sell or share your location."*
- A help link to the privacy policy and terms

### Customer Tap Flow

1. Customer taps the primary button.
2. The browser's native HTML5 Geolocation API prompts the customer for permission. The prompt is browser-native; the company's webpage cannot bypass or pre-grant this.
3. **If granted:** latitude, longitude, and accuracy are captured and POSTed to the Service Request via authenticated API call. The page shows a confirmation screen with the captured location displayed on a small map.
4. **If denied:** the page shows a fallback prompt asking the customer to manually pin their location on a tappable map, or to call dispatch back at a posted phone number.
5. **If the customer's browser does not support geolocation:** same manual fallback as denied.

The token in the URL expires upon successful capture or after 4 hours, whichever comes first. Reusing an expired token shows a friendly error and prompts the customer to call dispatch.

## 17.7 Failure Modes

| Failure | Indicator | Action |
|---|---|---|
| Customer phone missing or invalid | Send button disabled | Capture phone during intake; re-attempt |
| Consent not granted | System refuses send | Obtain consent per §17.3 or use verbal capture |
| Send to a landline | Telnyx returns landline error | System auto-flags; fall back to verbal |
| Carrier filtered / blocked | Status: Filtered or Blocked | Verbal capture; notify Owner — may indicate content flag |
| Delivery delayed > 60 sec | Status remains Pending | Wait up to 5 min; system auto-sends L2; if still no delivery, verbal |
| Customer denies geolocation permission | Webpage shows fallback | Customer pins manually OR dispatch falls back to verbal |
| Customer doesn't tap link | No response after L2 reminder | Verbal capture; tag request "SMS unresponsive" |
| Customer texts STOP / opt-out keyword | Telnyx auto-routes | System auto-sends L4 confirmation; blocks all future SMS to number |

## 17.8 STOP / HELP Keyword Handling

The Telnyx number is configured to handle the following keywords automatically without human intervention. These behaviors are required by 10DLC rules and **cannot be turned off**.

### Opt-Out Keywords (case-insensitive)

`STOP`, `STOPALL`, `UNSUBSCRIBE`, `CANCEL`, `END`, `QUIT`

- **Action:** Add the number to the campaign opt-out list. Auto-send Template L4 (one confirmation message). Block all future SMS to this number from any campaign.
- **Cross-campaign effect:** Opting out of location-capture also opts the number out of any other operational SMS through the same number. Re-consent is required to resume.

### Re-Opt-In Keywords

`START`, `UNSTOP`, `YES`

- **Action:** If the number was previously opted out via this system, remove from opt-out list and send confirmation. Customer is re-eligible for SMS for new service requests.

### Help Keyword

`HELP`, `INFO`

- **Action:** Auto-send Template L5 with customer care contact information. Does not affect opt-in status.

## 17.9 Audit Trail and Retention

Every SMS sent and received through the system is logged with the fields below. **Logs are immutable once written** and are retained per the schedule that follows.

| Field | Description |
|---|---|
| Timestamp | UTC, to the second |
| Direction | Outbound or Inbound |
| Sender / Recipient | E.164 phone numbers |
| Message Body | Full content as sent (variables resolved) |
| Delivery Status | Sent, Delivered, Failed, Filtered (with reason if not Delivered) |
| Campaign ID | TCR campaign reference |
| Service Request ID | Linked SR for traceability |
| Consent Record Reference | Pointer to the consent grant authorizing this send |

### Retention Schedule

- **SMS logs (sent and received):** 4 years from the date of the message.
- **Consent records** (dispatcher attestation with timestamp, user ID, and CallerID; or web form submission record): 4 years from the date of the last SMS sent to the number, or until opt-out, whichever is longer.
- **Opt-out records:** retained **indefinitely** (cannot be purged — required to honor the opt-out in perpetuity).
- **Campaign content templates and approvals:** retained for the life of the campaign plus 4 years.

## 17.10 Forbidden Practices

Any of the following constitute a serious compliance violation. They expose the company to carrier suspension, TCPA litigation (statutory damages of $500–$1,500 per violation), and FCC enforcement action. **Each is grounds for disciplinary action.**

- Sending any SMS to a number without documented prior express consent.
- Sending promotional or marketing content through the operational campaign number.
- Sending SMS to a number on the opt-out list, ever, without re-consent.
- Including content that could appear deceptive, urgent in a manipulative way, or impersonating another brand.
- Using public URL shorteners (bit.ly, tinyurl, t.co) — always use `whiteknightroadside.com` short links.
- Sending SMS outside the customer's local time window of 8 AM – 9 PM, except for active service requests where the customer initiated contact within the last 30 minutes.
- Manually editing message content beyond approved templates and variable substitution.
- Bypassing the consent-verification step in the dispatcher tool.
- Sharing or reusing customer phone numbers across campaigns without separate consent for each.
- Importing customer lists from third parties and messaging them — every consent must be original and traceable to the individual customer.

> **⚖ Legal Exposure.** 10DLC and TCPA violations are not warnings — they are enforceable. TCPA statutory damages are $500 per unsolicited message, trebled to $1,500 per message for willful violations. A single dispatcher who sends 50 messages without proper consent exposes the company to **$25,000–$75,000** in liability. Carrier suspension is faster: T-Mobile, AT&T, and Verizon can suspend the company's number — and the brand registration — within days of a complaint, taking the entire SMS capability offline. **Treat the consent verification step the same way you treat the bead-seating pressure limit: it is not negotiable, and shortcuts are not permitted.**

## 17.11 Cross-References

- Consent capture during phone intake: §4.3
- Customer phone field on the Service Request: §2.4
- Service Request form (intake): §5
- Detailed 10DLC registration documentation: 10DLC Compliance Workbook (separate document)

\newpage

# 18. Customer-Facing Document Templates

This section provides fillable templates for the three customer-facing documents not already templated elsewhere in this workbook: the **Estimate** (doc 2 of the six-document workflow chain), the **Change Order** (doc 4), and the **Diagnostic Report** used during Mobile Mechanic diagnostic-then-quote flows. The Service Request, Waiver, and Receipt templates appear in §5, §6, and §11 respectively.

## 18.1 Estimate Template

Generated when a Service Request is approved for dispatch. Customer-facing; **no signature required** (per company policy, the customer signs only the Waiver and the Receipt). Acceptance can be verbal, app-based, or by text.

```
ESTIMATE
Estimate #: ____________________   Job ID: ____________________
Date: ____________________         Time: ____________________
Tech: ____________________         Valid Until: ____________________
```

### Customer

```
Name: __________________________________________________________________
Phone: ____________________   Email: ____________________
Service Address: _______________________________________________________
```

### Vehicle

```
Year: ____________________   Make: ____________________
Model: __________________________________________________________________
Plate: ____________________   State: ____________________
```

### Service Type and ETA

```
Service Type: __________________________________________________________
Quoted ETA: ____________________
```

### Charges

| Part No | Description | Qty/Hrs | Unit | Price |
|---|---|---:|---:|---:|
| | | | | |
| | | | | |
| | | | | |
| **Subtotal** | | | | |
| **Tax** | | | | |
| **QUOTED TOTAL** | | | | |

### Notes

```
________________________________________________________________________
________________________________________________________________________
```

### What Happens Next

1. Customer accepts this quote (by phone, app, or text).
2. A technician is dispatched to the service location.
3. On-scene, the customer reviews and signs a Service Authorization, Charges & Liability Waiver. That document authorizes work to begin and locks in the agreed charges.
4. If hidden conditions push the price up by more than the lesser of 10% of the agreed total or $200, the technician STOPS and produces a Change Order for customer signature before continuing.
5. When work is complete, the customer signs a Receipt. That document anchors the warranty and closes the job.

**Validity.** Quote valid through the time shown. After that, prices for parts (especially used tires and batteries) may change with supplier availability and a re-quote may be required.

**No customer signature is required on this Estimate.** Per company policy, the customer signs only two documents per job: the Waiver (before work) and the Receipt (at completion).

## 18.2 Change Order Template

Used when on-scene scope or price needs to change after the Waiver is signed. Customer signature is required when the increase exceeds **the lesser of 10% of the original Total Agreed or $200**, per Oregon consumer-protection law. Smaller changes are logged and verbally communicated. The technician STOPS work the moment a triggering increase is identified and does not resume until the customer signs.

```
CHANGE ORDER
CO #: ____________________   Job ID: ____________________
Date: ____________________   Time: ____________________
Tech: ____________________
References Waiver signed: ____________________
```

### Customer and Vehicle (abbreviated — full record on referenced Waiver)

```
Customer Name: _________________________________________________________
Customer Phone: ____________________
Vehicle: _______________________________________________________________
VIN: ____________________
```

### Reason for Change

```
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
```

### Itemized Changes

| Description | Amount |
|---|---:|
| | $ |
| | $ |
| | $ |
| **NET CHANGE** | **$** |

### Total Roll-Up

```
Original Total Agreed (per Waiver): ____________________
Net change (this CO): ____________________
NEW TOTAL AGREED: ____________________
```

### Authorization to Proceed

By signing this Change Order, the customer authorizes White Knight Roadside, LLC to perform the additional work itemized above and agrees to pay the New Total Agreed amount on completion. The terms of the original Service Authorization, Charges & Liability Waiver remain in full force; only the Total Agreed amount is amended.

```
Customer Signature: __________________________________________________
Customer Printed Name: _______________________________________________
Date: ____________________   Time: ____________________

Technician Signature: ________________________________________________
```

> **Oregon Change Order Threshold.** Oregon consumer-protection law requires the customer's signature on any change to the agreed price that exceeds the lesser of 10% of the agreed total or $200. White Knight applies this rule across all services. Performing work that pushes the total above the threshold without a signed Change Order is grounds for the customer to refuse payment of the unauthorized amount and may constitute an Oregon Unlawful Trade Practices Act violation.

## 18.3 Diagnostic Report Template

Used in the Mobile Mechanic diagnostic-then-quote flow (§3.6.3) when a service request scope cannot be fully determined from the intake call alone. The diagnostic visit is separately billable. **If the customer authorizes the recommended repair within 7 calendar days, the diagnostic fee is credited toward the repair total**; otherwise it stands as its own paid visit.

```
DIAGNOSTIC REPORT
Diag #: ____________________   Job ID: ____________________
Date: ____________________     Time: ____________________
Tech: ____________________
```

### Customer and Vehicle

```
Name: __________________________________________________________________
Phone: ____________________   Email: ____________________
Vehicle: _______________________________________________________________
VIN: ____________________
Plate: ____________________   State: ____________________
Mileage: ____________________
```

### Reported Symptom

```
________________________________________________________________________
________________________________________________________________________
```

### Tests Performed

| Test | Reading / Result |
|---|---|
| | |
| | |
| | |
| | |
| | |

### Findings

```
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
```

### Recommendation

```
________________________________________________________________________
________________________________________________________________________
________________________________________________________________________
```

### Estimate to Repair

| Description | Amount |
|---|---:|
| | $ |
| | $ |
| | $ |
| **ESTIMATED TOTAL** | **$** |

**Diagnostic fee.** A Receipt for the diagnostic visit is issued at the conclusion of testing. If the customer authorizes the recommended repair within 7 calendar days, the diagnostic fee is credited against the repair total.

**Disclaimer.** This Diagnostic Report reflects the technician's observations and tests at the time of the visit. Conditions on a vehicle can change; if symptoms differ at the time of repair, scope and price may be adjusted via Change Order.

```
Technician Signature: ________________________________________________
Date: ____________________   Time: ____________________
```

\newpage

# Document Control

| Field | Value |
|---|---|
| Document Title | White Knight Roadside Operations Document Book |
| Version | 2.0 |
| Effective Date | ____________________ |
| Reviewed By | ____________________ |
| Next Review Date | ____________________ |
| Distribution | Owner, Dispatchers, Technicians, Counsel (legal review of §6) |
| Confidentiality | Internal use only — not for distribution outside the company |

### Change Log

| Version | Date | Editor | Change Summary |
|---|---|---|---|
| 1.0 | (initial) | Owner | Original draft of operations book |
| 2.0 | 2026-05-09 | Editorial pass | Restored §1.7, §1.8, §3.5, §3.6, §17 from authoritative source. Tightened prose throughout. Converted in-line matrices to real tables. Standardized voice across SOPs (imperative second person), policy sections (declarative third person), and signed forms (first-person customer voice). Added §18 customer-facing form templates (Estimate, Change Order, Diagnostic Report). Reformatted §6 Release of Liability and §11 Receipt per editorial direction. Removed "fluid level" reference from §3.1 photo requirements. Added Google Review link block to §11 Receipt with explicit no-other-channel notice. |

---

*End of Document — White Knight Roadside Operations Document Book v2.0*
