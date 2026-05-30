# Solo Roadside — MVP (frozen)

A mobile-first PHP application for a solo roadside / mobile mechanic
operator. Covers the full job lifecycle:

```
Intake -> Service Request -> Estimate -> Customer Approval ->
Work Order / Dispatch -> Field Work -> Service Completion Report ->
Invoice -> Payment -> Receipt -> Accounting -> Proof Packet
```

## Status: frozen at v1.0.0-mvp

This repository is the **operator-ready MVP snapshot**. It is intentionally
**not maintained**. Open a new repository (fork from this one) for any
new work; do not push changes back here.

Production lives at https://newhope.wkrllc.com against MySQL 8.4 on
SiteGround. The same code runs locally against SQLite for development.

## Stack

- PHP 8.0+ (production runs on PHP 8.2.31)
- SQLite (local default) or MySQL 8+ (production)
- No framework; plain OOP PHP + PDO + a tiny custom router
- HTML + vanilla CSS + a small amount of vanilla JS
- Apache with the `.htaccess` shipped under `public/`

## Major modules

- Customers, vehicles, locations, intakes, service requests
- Catalog (services, parts, materials, fees, labor)
- Estimates with line items, totals, and customer approvals
- Work orders with a mobile field screen
- Service completion reports with photo and signature uploads
- Invoices, payments, receipts
- Vendor directory, vendor documents with categorized lines, vendor
  document posting to the ledger
- Accounting (chart of accounts, ledger entries, ledger lines)
- Generated PDFs for estimates, invoices, receipts, and proof packets
- Reports: revenue, payments, unpaid invoices, missing records, gross
  margin by job, lead source revenue, tax summary
- Session-based authentication (first-run `/setup`, login, logout)
- Audit log on every meaningful action with `actor_user_id` stamped

## Repository layout

```
app/            PHP application code (Controllers, Models, Services, Views, Core)
database/       Migrations
docs/           Director, product, architecture, delivery docs
public/         Apache document root (index.php + assets/ + .htaccess)
scripts/        CLI helpers (migrate, seed/verify/cleanup, deploy zip builder)
storage/        Runtime data (gitignored): SQLite db, uploads, generated PDFs
```

## Running locally

1. Copy `.env.example` to `.env`.
2. `php -S 127.0.0.1:8080 -t public`
3. Open `http://127.0.0.1:8080`. First request triggers migrations and the
   first-run `/setup` page.

## Deploying

See [`docs/30-delivery/deploy-and-backup.md`](docs/30-delivery/deploy-and-backup.md)
for the SiteGround setup, MySQL configuration, file storage layout, and
backup checklist.

## Why this is frozen

This snapshot delivers every item in the MVP completion criteria
documented in the original development plan. Further changes belong
in a forked successor project so this version stays known-good and
reproducible.
