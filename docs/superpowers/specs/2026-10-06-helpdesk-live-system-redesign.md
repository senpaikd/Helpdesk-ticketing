# HelpDesk Live System Redesign

## Status and Relationship to the Existing Design

This specification records the approved direction for turning the current HelpDesk portfolio build into a deployable help-desk application. It supersedes the earlier interface, routing, seeding, dashboard, and production-readiness decisions in `2026-10-06-helpdesk-ticketing-system-design.md`. Existing ticket authorization, status-transition, attachment-security, and activity-audit rules remain in force unless this document explicitly changes them.

The system remains branded **HelpDesk**. The supplied `Deskly — Help Desk Ticketing.html` file is the visual source of truth for the admin and staff workspace, but its Deskly name, emoji icons, placeholder screens, example records, and invented metrics are not application data.

## Product Outcome

HelpDesk will provide two authenticated experiences backed by the same Laravel records:

- A green customer portal using the existing landing-page visual direction.
- A dark-sidebar admin/staff workspace faithfully based on the supplied HTML design.

There is no public marketing homepage. A guest visiting `/` is redirected to `/login`. Login, registration, forgotten-password, and password-reset routes remain public. Every dashboard, ticket, article, report, and setting is protected by authentication, active-account checks, and role or record authorization.

All displayed operational values come from the database. The application must not use example tickets, fake trends, invented response times, placeholder customers, or decorative CSAT values.

## Roles and Permissions

### Customer (`user`)

Customers may self-register and are always assigned the `user` role. They may:

- Use the customer dashboard at `/`.
- See statistics and recent tickets derived only from tickets they created.
- Create tickets and view their complete ticket history.
- View ticket details, assignment, status, conversation, attachments, and permitted activity.
- Reply and upload approved attachments while the ticket is writable.
- Close their own resolved tickets.
- Rate resolved or closed tickets once using a one-to-five score and optional feedback.
- Search and read published knowledge-base articles.
- Edit their basic profile, change their password, review active sessions, and revoke other sessions.

Customers cannot access the staff queue, internal notes, unpublished articles, administrative reports, other users' data, or application settings.

### Support Staff (`staff`)

Staff use the workspace interface. They may:

- See real dashboard metrics limited to unassigned tickets and tickets assigned to them.
- View unassigned tickets, claim one, and work tickets assigned to them.
- Update permitted status values, reply, add internal notes, and upload attachments.
- See ticket activity relevant to their authorized queue.
- Draft and edit knowledge-base articles, but not publish or delete them.
- View customer information only when needed for tickets they are authorized to handle.

Staff cannot access other staff members' assigned tickets, manage roles, change global settings, publish articles, export global reports, or deactivate accounts.

### Administrator (`admin`)

Admins may access every ticket and all workspace modules. They may:

- Assign and reassign tickets, update priority and status, reopen closed tickets, and review complete activity.
- Manage customer accounts, team accounts, roles, and active state.
- Manage categories.
- Create, edit, publish, unpublish, and delete knowledge-base articles.
- View and export reports.
- Manage approved application settings.

The last active administrator cannot demote or deactivate themselves. Administrative operations remain protected server-side even if controls are hidden in the UI.

## Routing and Role Destinations

- `GET /` is authenticated and active-account protected.
- A customer receives the green customer dashboard from `/`.
- Staff and admins visiting `/` are redirected to the workspace dashboard at `/dashboard`.
- Successful customer login or registration redirects to `/`.
- Successful staff/admin login redirects to `/dashboard` unless a safe intended URL exists.
- `/tickets` is the staff/admin queue. A customer visiting it is redirected to `/`.
- `/tickets/create` and `/tickets/{ticket}` remain available to customers subject to ticket policies.
- `/knowledge-base` is available to all authenticated users and shows only published content to customers.
- Administrative modules use named routes and role middleware; navigation visibility is not an authorization boundary.
- Logout is a `POST` request and redirects to `/login` with a confirmation message.

## Customer Portal

The existing nature-themed landing page becomes the authenticated customer dashboard. Guest-only marketing content and authentication calls to action are removed.

The dashboard contains:

- A customer greeting and clear create-ticket action.
- Four real statistics: total, open, in progress, and resolved tickets belonging to the signed-in customer.
- A recent-ticket list showing the public ticket number, subject, category, priority, status, assignee when present, and most recent update time.
- A useful empty state when the customer has no tickets.
- An expandable or paginated “My tickets” section on `/` for the customer's complete history, plus links to ticket creation, ticket details, knowledge base, profile, sessions, and logout.

Customer ticket details preserve the readable conversation-focused layout, attachment security, activity visibility, validation feedback, and mobile behavior already approved. Data queries are always scoped by the ticket policy and signed-in user rather than only filtered in the view.

## Admin and Staff Workspace

The workspace must visually follow the supplied admin HTML:

- Dark navy fixed/collapsible sidebar.
- Light content canvas with a supported dark theme.
- Inter typography, compact top bar, rounded cards, restrained borders, and indigo primary actions.
- Responsive sidebar behavior below desktop widths.
- Dashboard statistic cards, weekly chart, recent activity, ticket table, filter pills, and right-side ticket drawer.
- Real icon-library icons rather than emoji, handwritten SVG, or text glyph substitutes.
- Product branding changed from Deskly to HelpDesk.

The working navigation is:

- Dashboard
- Tickets
- Customers
- Knowledge Base
- Reports
- Categories
- Settings
- Profile
- Logout

Items that a role cannot access are omitted. No visible navigation item leads to a placeholder screen.

### Workspace Dashboard

Admin metrics cover the complete system; staff metrics use only their authorized workload. Cards show:

- Active workload: open, pending, and in-progress tickets.
- Average first-response time for tickets with a recorded first staff response.
- Tickets resolved today in the configured application timezone.
- Average CSAT and response count for rated tickets.

The weekly chart shows real ticket creation counts for the last seven calendar days. Recent activity shows real authorized `ticket_activities` records with actor, action, ticket reference, and time. Comparisons are shown only when both current and prior periods contain enough real data; otherwise the UI presents the value without a fabricated percentage.

### Ticket Queue and Drawer

The ticket queue supports search by ticket number, subject, or requester and filters for status, priority, category, and assignee where authorized. Pagination retains query parameters.

Selecting a row opens the supplied right-side drawer pattern. The drawer shows:

- Public ticket number, subject, requester, category, timestamps, and description.
- Authorized status, priority, and assignee controls.
- Customer replies, staff replies, and clearly distinguished internal notes.
- Private attachments and downloads.
- Activity history.
- Reply composer with attachment support.

The drawer has a direct URL or equivalent server-addressable state so refresh, authorization failure, and browser navigation remain predictable. Mobile displays the same content as a full-width panel. All mutations submit to existing or focused Laravel endpoints and return validation errors without losing the current queue context.

## Customers and Team Management

The Customers module lists customer accounts with search, active state, joined date, ticket count, open-ticket count, and latest activity. Admins can view a customer's ticket history and activate or deactivate the account. Destructive deletion is not part of the live baseline because ticket history must remain auditable.

Team account management lives under Settings and is admin-only. Admins can create staff/admin accounts, change roles, activate or deactivate accounts, and trigger a password-reset email. Last-active-admin safeguards apply to normal and crafted requests.

## Categories

Admins can create, rename, describe, activate, and deactivate categories. Referenced categories are not hard-deleted. Only active categories are available for new tickets and configurable ticket defaults.

## Knowledge Base

Knowledge-base articles contain a title, unique slug, summary, body, optional category, author, status (`draft` or `published`), and publication timestamp.

- Authenticated users can search and read published articles.
- Staff can create drafts and edit only drafts they authored.
- Admins can edit any article, publish or unpublish it, and soft-delete or restore it so editorial history is retained.
- Customer-facing article output is sanitized and never renders untrusted executable markup.
- Empty, draft, and unpublished states are explicit.

## First Response, Internal Notes, and Satisfaction

Tickets gain a nullable `first_responded_at` timestamp. The first public reply by staff or an admin records it atomically; internal notes do not count as a first response. Subsequent replies never overwrite it.

Ticket comments gain an `is_internal` flag. Internal notes are visible only to authorized staff/admin users and never appear in customer responses, customer activity summaries, exports intended for customers, or notifications.

Ticket satisfaction is stored separately with ticket, customer, one-to-five rating, optional feedback, and timestamps. A unique ticket constraint permits one current rating per ticket. Only the ticket creator may rate their resolved or closed ticket. Admin reporting aggregates actual ratings and clearly shows “No ratings yet” when appropriate.

## Reports and Export

Reports are admin-only and support a validated date range. They include:

- Created, resolved, and closed ticket counts.
- Current status, priority, and category breakdowns.
- Average first-response time from recorded timestamps.
- Average resolution time for resolved/closed tickets.
- CSAT average, rating count, and rating distribution.
- Staff workload and resolution counts without ranking claims unsupported by context.

CSV export uses the same validated filters and authorization as the visible report. Large exports are streamed to avoid loading the complete result set into memory. Exported cells that begin with spreadsheet formula characters are neutralized to prevent CSV injection.

## Settings

Admins can manage a deliberately limited settings set:

- Application display name, fixed by default to HelpDesk.
- Support email address.
- Application timezone.
- Whether self-registration is enabled; it is enabled by default for the approved launch flow.
- Default ticket category and priority.
- Customer-visible support message.

Settings are validated, cached, and invalidated after updates. Security-sensitive deployment values such as database credentials, `APP_KEY`, mail passwords, cookie security, and debug mode remain environment configuration and are never editable in the browser.

## Authentication and Session Security

Laravel's session authentication remains the foundation. Required behavior:

- Login is rate-limited and accepts only active accounts.
- A successful login regenerates the session ID to prevent fixation.
- Normal authenticated sessions expire after 120 minutes of inactivity.
- “Keep me signed in” uses a 30-day remember duration.
- Authentication redirects are role-aware and honor only safe intended local URLs.
- Logout removes authentication, invalidates the current session, regenerates the CSRF token, and confirms completion on the login page.
- Inactive accounts are invalidated on the next protected request.
- Password changes regenerate the current session and invalidate other authenticated sessions.
- The profile security screen lists the current user's database-backed sessions using safe device/browser descriptions, IP address, and last activity, identifies the current session, and allows revoking other sessions after password confirmation.
- Production uses HTTPS-only, HTTP-only, SameSite cookies with a production-specific cookie name and correct domain/path configuration.
- Session records use the database driver and the sessions migration is part of deployment.

Session payloads, remember tokens, password hashes, and secrets are never exposed in views, logs, reports, or exports.

## Data Model Changes

The existing schema remains and gains focused migrations for:

- `ticket_comments.is_internal` boolean with a safe default.
- `tickets.first_responded_at` nullable timestamp.
- `ticket_satisfactions` with unique `ticket_id`, `user_id`, rating, feedback, and timestamps.
- `knowledge_base_articles` with author/category relationships, slug, summary, body, status, publication timestamp, and indexes for status/slug.
- `settings` with a unique key and appropriately protected serialized value representation.

Existing tickets, users, categories, comments, attachments, activities, and the genuine `TKT-2026-00006` record are preserved. Migrations must be additive and reversible. Fresh default seeding creates only reusable categories and required safe configuration; it never creates shared-password users or example tickets.

## Error, Empty, and Lifecycle Behavior

- Authorization failures return controlled 403 responses and never leak record existence through content.
- Missing ticket or attachment records return controlled 404 responses.
- Validation messages remain beside the relevant fields and preserve safe input.
- Every list, chart, activity panel, report, and article module has a truthful empty state.
- Multi-record writes, first-response capture, status transitions, and satisfaction writes use transactions where atomicity matters.
- Settings cache is invalidated only after a successful commit.
- Closed tickets remain read-only except for approved admin reopening and customer satisfaction.

## Testing and Visual Verification

Development follows test-driven changes. Automated coverage includes:

- Guest redirects and public authentication routes.
- Role-aware login, registration, intended redirects, session rotation, remember duration, logout invalidation, inactive-account invalidation, password changes, and session revocation.
- Customer dashboard counts, recent records, ownership isolation, and customer `/tickets` redirect behavior.
- Admin/staff dashboard scoping and truthful metric calculations.
- Ticket drawer authorization, filters, mutations, internal-note privacy, and first-response capture.
- Customer and team safeguards.
- Category lifecycle.
- Knowledge-base drafting, publishing, searching, sanitization, and permissions.
- Satisfaction eligibility and uniqueness.
- Report calculations, filtering, authorization, streaming export, and CSV-injection protection.
- Settings validation, authorization, and cache invalidation.

The full PHP test suite, task-scoped Pint checks, production asset build, migrations, and dependency audits must pass. The admin/staff interface is compared against the supplied HTML at equivalent desktop and mobile viewports. Critical and major visual differences are fixed before handoff. Customer dashboard behavior is verified in guest, empty, populated, light, dark, desktop, and mobile states.

## Production and Operations

Production readiness requires evidence for:

- `APP_ENV=production`, `APP_DEBUG=false`, a production URL, and a production-specific `APP_KEY`.
- HTTPS and secure session cookies.
- Real mail transport for password resets.
- Supported PHP, database, web server, and Node build versions.
- `composer install --no-dev --optimize-autoloader`, asset build, Laravel optimization, and `php artisan migrate --force` deployment steps.
- Writable Laravel storage/cache directories without broad permissions.
- Durable private attachment storage and backup coverage.
- Database backup, restore verification, and rollback instructions.
- Scheduled session cleanup and any required queue worker supervision.
- Health checks, structured production logging, error monitoring, and log rotation.
- A committed release state without nested duplicate projects, shared credentials, debug settings, or untracked application source.
- README installation, administration, deployment, testing, backup, and security documentation.

“Production ready” is not claimed until these conditions and the full release review pass in the intended hosting environment.

## Delivery Sequence

The work is delivered in evidence-gated stages:

1. Authentication/session lifecycle and protected role destinations.
2. Real customer dashboard using the green portal.
3. Admin/staff workspace shell, dashboard metrics, charts, activity, queue, and ticket drawer matching the supplied design.
4. Customer, team, and category management.
5. Knowledge base, internal notes, first-response tracking, and satisfaction.
6. Reports, CSV export, and application settings.
7. Responsive visual QA, complete regression testing, documentation, and production hardening.

Each stage preserves passing earlier behavior and ends with targeted tests plus the complete relevant verification suite. No placeholder module is considered complete, and no stage introduces example operational data into the live database.

## Explicit Non-Goals for This Release

- Public marketing pages.
- Anonymous ticket submission or anonymous knowledge-base access.
- Billing, subscriptions, or payment processing.
- Third-party chat, telephony, or social-media support channels.
- Multi-tenant organizations.
- Native mobile applications.
- Fabricated analytics or seeded production accounts.
- WebSocket infrastructure unless separately approved; this release guarantees authoritative database updates and correct refreshed views.

## Completion Criteria

The redesign is complete only when guests see authentication first, customers use the live green dashboard, staff/admin use the supplied workspace design, every navigation destination works, all displayed values are database-derived, role and record boundaries pass automated tests, session controls behave as specified, knowledge/report/settings modules are operational, production assets and migrations succeed, visual QA passes, and the production-readiness review has no unresolved release blockers.
