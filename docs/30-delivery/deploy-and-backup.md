# Deploy and Backup Notes

## First-run setup

1. Copy `.env.example` to `.env` and set the values for your environment.
2. Ensure PHP 8.0+ is installed. PHP 8.2+ is the production target; the code is tested on 8.0.30 locally.
3. Serve `public/` as the web root (for example with `php -S 127.0.0.1:8080 -t public` for local use, or your web server's document root in production).
4. Open the app in a browser. Because no users exist yet, you will be redirected to `/setup`.
5. Enter your name, email, and a password (8 characters minimum). The account you create has the `admin` role.
6. After `/setup` completes, you are signed in and on `/dashboard`. From this point forward, every request requires a session.

## Database

- Local development uses SQLite by default (`storage/app.sqlite`). Migrations run automatically on the first request.
- For production, set `DB_DSN`, `DB_USER`, and `DB_PASS` in `.env` to a MySQL 8+ database. The code is driver-aware; the same migrations run against MySQL.
- Migrations live under `database/migrations/`. They run in filename order on every request via `App\Core\MigrationRunner` and are recorded in a `migrations` table so they only execute once.

### Verifying a MySQL deploy (not yet run end-to-end)

The application has only been exercised against SQLite. Before going live on MySQL:

1. Create the database and grant the configured user CREATE/ALTER/INSERT/UPDATE/DELETE/SELECT.
2. Point `DB_DSN` at the database and load a single page; the migration runner will create all tables.
3. Re-run the full smoke test list (see `test-plan.md`) and the seed/verify/cleanup scripts under `scripts/` to confirm no SQLite-specific behavior leaked into a query.

## File storage

The application writes uploaded and generated files under `storage/`:

- `storage/app.sqlite` -- the local SQLite database, when in use.
- `storage/uploads/YYYY/MM/` -- vendor receipts, service report photos, signatures.
- `storage/generated-pdfs/YYYY/MM/` -- generated estimate, invoice, receipt, and proof packet PDFs.

These directories must be writable by the web server user.

## Backup checklist

Run a daily backup that covers at least:

1. The database (`storage/app.sqlite` for SQLite, or `mysqldump` for MySQL).
2. The full `storage/uploads/` tree.
3. The full `storage/generated-pdfs/` tree.
4. The `.env` file (store it in a secret vault, not next to backups).

A representative daily script for the SQLite default:

```
#!/usr/bin/env bash
set -euo pipefail
STAMP=$(date +%Y%m%d)
DEST=/backups/solo-roadside/$STAMP
mkdir -p "$DEST"
cp -p storage/app.sqlite "$DEST/app.sqlite"
tar -czf "$DEST/uploads.tgz" storage/uploads
tar -czf "$DEST/generated-pdfs.tgz" storage/generated-pdfs
find /backups/solo-roadside -mindepth 1 -maxdepth 1 -type d -mtime +30 -exec rm -rf {} +
```

Test the restore at least once a quarter: rebuild the directory tree from a fresh backup, load `/dashboard`, and confirm a recent invoice, receipt, and proof packet still open.

## Operator hygiene

- The first-run admin account can sign other operators in later via a `/users` UI (not built yet -- post-MVP). For now, all activity is logged under the admin account.
- Audit log entries now capture `actor_user_id`. Logging out clears the session cookie; the audit row for the logout is recorded with the actor still attached.
- If you forget the admin password, the simplest recovery today is to drop the row from the `users` table and re-run `/setup`. A reset-by-email flow is post-MVP.
