# Decisions

## 2026-05-23 - Greenfield Start

Decision: Treat this workspace as a new application.

Reason: The project root is empty and is not a Git repository. There are no existing routes, database connection files, CSS assets, authentication logic, modules, or database migrations to preserve.

Impact: Phase 0 audit documents the absence of existing code. Sprint 1 should begin by scaffolding the app structure, configuration pattern, routing, database access, and migration runner.

## 2026-05-23 - Product Boundary

Decision: Optimize for a solo roadside assistance / mobile mechanic operator.

Reason: The system is intended to support fast job capture, mobile field execution, proof, payment, and clean records. It is not a towing fleet system, impound system, police rotation system, auction platform, full ERP, or multi-dispatcher enterprise tool.

Impact: Features should stay practical, mobile-first, and low-friction. Avoid enterprise-only abstractions unless they directly support the owner/operator workflow.

## 2026-05-23 - Workflow First

Decision: Build around the job lifecycle from intake through accounting and proof packet.

Reason: Every major record should inherit data from the previous stage to avoid duplicate entry.

Impact: Database design, UI pages, validations, and tests should follow this sequence:

Intake -> Service Request -> Estimate -> Customer Approval -> Work Order -> Field Work -> Service Completion Report -> Invoice -> Payment -> Receipt -> Accounting Posting -> Proof Packet.

## 2026-05-23 - Boring Reliable Stack

Decision: Use a simple PHP 8.2+, MySQL 8+, PDO, HTML, CSS, and vanilla JavaScript architecture unless project needs later justify more.

Reason: The brief calls for clear, boring, reliable code and no heavy framework unless already present.

Impact: Sprint 1 should use small classes, simple routing, explicit controllers, server-side validation, and easy-to-read views.

## 2026-05-23 - Local Runtime Compatibility

Decision: Keep the first implementation compatible with PHP 8.0.30 on this machine while preserving PHP 8.2+ as the deployment target.

Reason: The local CLI runtime is PHP 8.0.30. Blocking development on a runtime upgrade would slow the first foundation pass.

Impact: Avoid PHP features newer than 8.0 until the local runtime is upgraded.

## 2026-05-23 - Local SQLite Default

Decision: Use SQLite as the zero-configuration local development default when DB_DSN is not set, while supporting MySQL through .env.

Reason: No MySQL credentials were present in the empty workspace. The app still needs to be runnable and testable immediately.

Impact: Migrations are written to support both SQLite and MySQL where practical. Deployment should set a MySQL DB_DSN.


