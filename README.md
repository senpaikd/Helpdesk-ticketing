# HelpDesk

HelpDesk is a Laravel ticketing system with a private customer portal and a role-aware support workspace. Customers create and track requests; support staff and administrators manage the shared ticket workflow through authorization policies, private attachments, conversations, and activity history.

The project is being completed in evidence-gated stages. Authentication, session security, the ticket domain, customer portal, and the role-scoped admin/staff workspace are operational. The management, knowledge-base, reporting, settings, and production-release stages are documented under `docs/superpowers/`.

## Current customer flow

- Guests visiting `/` are redirected to `/login`.
- Self-registration creates an active customer account and opens the private portal.
- Customers see database-backed ticket totals, five recent requests, and a searchable/filterable paginated history at `/`.
- `/tickets/create` submits a request and `/tickets/{ticket}` contains the authorized conversation and details.
- The staff/admin queue remains at `/tickets`; customers are redirected back to their portal history.

## Admin and staff workspace

- `/dashboard` shows authorized workload metrics, the real seven-day ticket volume, and recent activity.
- `/tickets` provides requester search and composable status, priority, category, and assignee filters.
- Selecting a ticket opens a server-addressable drawer while preserving the active queue filters.
- Administrators see all tickets. Staff see only unassigned tickets and tickets assigned to them.
- Ticket status, priority, assignment, replies, attachments, and activity remain protected by Laravel policies and audited actions.

## Authentication and sessions

- Active-account validation and five-attempt login throttling.
- Session-ID regeneration on login and registration.
- Database-backed sessions with a 120-minute idle lifetime.
- Optional remembered login lasting 43,200 minutes (30 days).
- Encrypted session payloads.
- HTTP-only, SameSite `lax` cookies.
- Logout invalidates the current session and CSRF token.
- Profile security supports password changes, safe active-session summaries, and password-confirmed revocation of other sessions.

Production must run behind HTTPS with `SESSION_SECURE_COOKIE=true`. Never copy local credentials or a local `APP_KEY` to production.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL 8
- Blade and Tailwind CSS 4
- Vite 7
- PHPUnit 11

## Local setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Configure the MySQL connection in `.env` before running migrations. Default seeding creates only the reusable HelpDesk categories and does not create shared-password accounts or example tickets.

## Verification

```bash
php artisan test
npm run build
vendor/bin/pint --test
```

The test environment uses the dedicated `helpdesk_ticketing_test` MySQL database configured in `phpunit.xml`. Do not point automated tests at a database containing real data.

## Session configuration

Local HTTP development uses:

```dotenv
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
AUTH_REMEMBER_DURATION=43200
```

Set `SESSION_SECURE_COOKIE=true` in every HTTPS production environment. The session table is included in the base migration.

## Design and delivery documents

- Live-system specification: `docs/superpowers/specs/2026-10-06-helpdesk-live-system-redesign.md`
- Delivery roadmap: `docs/superpowers/plans/2026-10-06-helpdesk-live-roadmap.md`
- Completed workspace plan: `docs/superpowers/plans/2026-10-06-helpdesk-workspace-tickets.md`

## Production status

The application is not yet approved for production deployment. The remaining management, knowledge-base, reporting/settings, and production-hardening plans must be completed, followed by the final release review described in the live-system specification.
