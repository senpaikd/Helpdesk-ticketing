# HelpDesk Admin and Staff Workspace Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the temporary staff/admin ticket screen with a faithful, responsive HelpDesk workspace backed entirely by authorized ticket, activity, and dashboard data.

**Architecture:** A shared authorized-ticket query service will enforce the same admin/staff scope for dashboard metrics, activity, queue filters, and drawer selection. `/dashboard` becomes a real controller-rendered workspace dashboard, while `/tickets` remains the filterable queue and optionally loads a server-addressable `?ticket=TKT-...` drawer without losing the queue query string. The existing Laravel actions, policies, and mutation endpoints remain the write boundary.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, Tailwind CSS 4, Alpine-free vanilla JavaScript, Chart.js, Phosphor Icons, MySQL, PHPUnit/Pest-style Laravel feature tests

**Spec:** `docs/superpowers/specs/2026-10-06-helpdesk-live-system-redesign.md`

## Global Constraints

- Keep the product name **HelpDesk**; do not copy the Deskly name, example records, emoji, or invented metrics.
- Match `C:\Users\Kent\Downloads\Deskly — Help Desk Ticketing.html` with a dark navy responsive sidebar, light/dark canvas, compact top bar, indigo actions, rounded cards, dashboard chart, queue table, and right-side drawer.
- Use real database records and truthful empty/unavailable states only.
- Admin data covers the complete system; staff data is limited to unassigned tickets and tickets assigned to that staff member.
- Preserve existing customer routing, ticket policies, transition rules, attachment privacy, and activity auditing.
- Use Phosphor Icons for UI icons and Chart.js for the weekly chart; do not use emoji, handwritten SVG, text glyph icons, or CSS-drawn chart art.
- Navigation items for later roadmap modules are omitted until their routes exist; no visible link may lead to a placeholder.
- This plan does not add customers, categories, knowledge base, internal notes, first-response tracking, satisfaction, reports, or settings behavior owned by Plans 3–5.
- Repository commits remain deferred by user request; record evidence checkpoints without committing.

## Review Focus

- A staff member must never see metrics, activity, queue rows, or a selected drawer ticket assigned to another staff member.
- A forged `assignee` filter from staff must not broaden or change their authorized scope.
- A selected ticket number that is missing returns 404; an existing but unauthorized selection returns 403 without leaking its contents.
- Search must match ticket number, subject, and requester while category/status/priority/assignee filters compose without dropping pagination parameters.
- Empty or unavailable first-response/CSAT inputs must render truthful text, never fabricated values or percentages.

---

### Task 1: Centralize Authorized Workspace Queries

**Files:**
- Create: `app/Services/AuthorizedTicketQuery.php`
- Modify: `app/Http/Controllers/TicketController.php`
- Modify: `tests/Feature/Tickets/TicketFilterTest.php`
- Test: `tests/Feature/WorkspaceAuthorizationTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `App\Enums\UserRole`, and existing `TicketPolicy` rules.
- Produces: `AuthorizedTicketQuery::for(User $user): Builder<Ticket>` used by dashboard and queue controllers.

- [ ] **Step 1: Write failing authorization tests**

Add tests proving admin receives all tickets, staff receives only unassigned plus self-assigned tickets, customer access is rejected by workspace controllers, and forged staff assignee input does not broaden scope.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/WorkspaceAuthorizationTest.php tests/Feature/Tickets/TicketFilterTest.php`

Expected: FAIL because the shared service does not exist and `TicketController` still owns a private duplicate query.

- [ ] **Step 3: Implement `AuthorizedTicketQuery::for(User $user): Builder`**

Return the admin, staff, and customer query scopes already enforced by the application. Make workspace callers explicitly reject customers rather than treating the customer scope as workspace access.

- [ ] **Step 4: Inject the service into `TicketController::index`**

Replace the private `authorizedQuery` method without changing customer `/tickets` redirection or existing filters.

- [ ] **Step 5: Run the focused tests and verify GREEN**

Run: `php artisan test tests/Feature/WorkspaceAuthorizationTest.php tests/Feature/Tickets/TicketFilterTest.php`

Expected: PASS.

- [ ] **Step 6: Record the no-commit checkpoint**

Capture changed files and test output in the Plan 2 SDD ledger; do not commit.

### Task 2: Build the Real Workspace Dashboard

**Files:**
- Create: `app/Http/Controllers/WorkspaceDashboardController.php`
- Create: `app/Services/WorkspaceDashboardData.php`
- Create: `resources/views/workspace/dashboard.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/js/app.js`
- Test: `tests/Feature/WorkspaceDashboardTest.php`

**Interfaces:**
- Consumes: `AuthorizedTicketQuery::for(User): Builder`, `TicketActivity`, configured application timezone, and authenticated active staff/admin.
- Produces: named route `dashboard`; view data `metrics`, `weeklyTickets`, and `recentActivities`; `[data-workspace-chart]` JSON consumed by the JavaScript chart initializer.

- [ ] **Step 1: Write failing dashboard tests**

Add tests named for admin system-wide metrics, staff-scoped metrics/activity, seven calendar-day creation counts, resolved-today timezone behavior, truthful unavailable response/CSAT cards, and customer rejection.

- [ ] **Step 2: Run dashboard tests and verify RED**

Run: `php artisan test tests/Feature/WorkspaceDashboardTest.php`

Expected: FAIL because `/dashboard` is still a redirect and the dashboard service/view do not exist.

- [ ] **Step 3: Implement `WorkspaceDashboardData::for(User $user): array`**

Return active-workload count for open/pending/in-progress statuses, resolved-today count, a seven-item date/count series, and the ten newest authorized activities with `ticket`, `user`, and timestamps eager-loaded. Return explicit unavailable states for first response and CSAT until their roadmap-owned tables/columns exist.

- [ ] **Step 4: Implement `WorkspaceDashboardController::__invoke(Request $request): View|RedirectResponse`**

Redirect customers to `portal`; render `workspace.dashboard` for staff/admin using the dashboard service.

- [ ] **Step 5: Render real dashboard cards, chart canvas, and activity list**

Use the supplied layout hierarchy and truthful empty states. Serialize only date labels and integer counts to the chart data attribute.

- [ ] **Step 6: Initialize the Chart.js weekly chart**

Render a responsive indigo bar chart, destroy an existing instance before reinitialization, respect reduced motion, and derive grid/text colors from workspace CSS variables.

- [ ] **Step 7: Run dashboard tests and verify GREEN**

Run: `php artisan test tests/Feature/WorkspaceDashboardTest.php`

Expected: PASS.

- [ ] **Step 8: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 3: Rebuild the Responsive Workspace Shell

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: `resources/views/layouts/workspace.blade.php`
- Modify: `tests/Feature/Views/LayoutSmokeTest.php`

**Interfaces:**
- Consumes: named `dashboard`, `tickets.index`, and `profile.edit` routes plus the authenticated user's role/name.
- Produces: responsive workspace shell, `[data-workspace-sidebar]`, `[data-workspace-menu-toggle]`, theme control, active navigation states, and real icon-library markup.

- [ ] **Step 1: Add failing workspace-shell assertions**

Assert HelpDesk branding, Dashboard/Tickets/Profile destinations, role label, logout form, active-route state, and absence of future placeholder navigation and Deskly/emoji text at desktop and mobile markup levels.

- [ ] **Step 2: Run layout tests and verify RED**

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php`

Expected: FAIL against the temporary shell.

- [ ] **Step 3: Install `@phosphor-icons/web` and `chart.js`**

Run: `npm install @phosphor-icons/web chart.js`

Import the icon stylesheet and Chart.js from application-owned assets; do not use a CDN.

- [ ] **Step 4: Rebuild `layouts/workspace.blade.php`**

Implement the navy fixed/collapsible sidebar, compact top bar, current-route styling, user card, theme control, mobile backdrop, accessible menu labels/focus behavior, and only the working Dashboard, Tickets, Profile, and Logout destinations.

- [ ] **Step 5: Update workspace tokens and responsive behavior**

Use solid navy/indigo colors, Inter/system type, restrained borders, matching radii, focus-visible rings, dark theme variables, overflow-safe content, and full-width mobile sidebar behavior.

- [ ] **Step 6: Implement sidebar interactions in `resources/js/app.js`**

Open/close from the menu and backdrop, close on Escape and navigation, restore focus to the trigger, and keep `aria-expanded` synchronized.

- [ ] **Step 7: Run layout tests and verify GREEN**

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php`

Expected: PASS.

- [ ] **Step 8: Record the no-commit checkpoint**

Capture dependency and layout changes plus test output; do not commit.

### Task 4: Deliver the Real Ticket Queue and Server-Addressable Drawer

**Files:**
- Modify: `app/Http/Controllers/TicketController.php`
- Create: `resources/views/components/workspace-ticket-row.blade.php`
- Create: `resources/views/components/workspace-ticket-drawer.blade.php`
- Modify: `resources/views/tickets/index.blade.php`
- Modify: `resources/views/tickets/show.blade.php`
- Modify: `resources/js/app.js`
- Modify: `tests/Feature/Tickets/TicketFilterTest.php`
- Modify: `tests/Feature/Tickets/TicketActivityTest.php`
- Test: `tests/Feature/WorkspaceTicketDrawerTest.php`

**Interfaces:**
- Consumes: existing ticket policies, actions, mutation routes, attachment downloads, comment creation, enum labels/transitions, and `AuthorizedTicketQuery`.
- Produces: queue parameter `ticket=<public-ticket-number>`; controller view data `selectedTicket`, `filters`, `categories`, and `staff`; desktop right drawer/mobile full-width panel.

- [ ] **Step 1: Write failing queue and drawer tests**

Cover requester search, composed filter query retention, admin assignee filter, ignored staff assignee filter, selected authorized ticket loading, 404 missing selection, 403 unauthorized selection, staff `/tickets/{ticket}` canonical redirect to `tickets.index?ticket=...`, and mutation validation returning to the selected queue URL.

- [ ] **Step 2: Run focused tests and verify RED**

Run: `php artisan test tests/Feature/Tickets/TicketFilterTest.php tests/Feature/Tickets/TicketActivityTest.php tests/Feature/WorkspaceTicketDrawerTest.php`

Expected: FAIL because requester search and the selected drawer state do not exist.

- [ ] **Step 3: Extend `TicketController::index`**

Search through `creator.name` in addition to number/subject. Validate enum and integer filters as today. Resolve `ticket` by public number, return 404 when missing, authorize it separately, eager-load drawer relationships, and pass safe normalized filter values to the view.

- [ ] **Step 4: Canonicalize staff/admin `show` requests**

Keep the existing customer ticket detail view. Redirect staff/admin `/tickets/{ticket}` to `tickets.index` with the selected `ticket` query so direct links consistently use the drawer state.

- [ ] **Step 5: Rebuild the queue view**

Implement status pills, priority/category/assignee filters, requester-aware search, accessible table rows, truthful empty state, pagination query retention, and links that add `ticket` without discarding current filters.

- [ ] **Step 6: Render the drawer component**

Show real public number, subject, requester, description, category/times, authorized status/priority/assignment controls, public conversation, attachments, activity, and reply/upload forms. Omit the internal-note toggle until Plan 4 creates its persistence and authorization boundary.

- [ ] **Step 7: Add drawer interaction polish**

The close control removes only `ticket` from the query, Escape closes via navigation, desktop uses the right panel/backdrop, mobile uses a full-width panel, and focus moves to the drawer heading after navigation.

- [ ] **Step 8: Run focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Tickets/TicketFilterTest.php tests/Feature/Tickets/TicketActivityTest.php tests/Feature/WorkspaceTicketDrawerTest.php`

Expected: PASS.

- [ ] **Step 9: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 5: Regression, Production Build, and Visual Fidelity Gate

**Files:**
- Modify: `design-qa.md`
- Modify: `README.md` only where the operational workspace behavior needs documentation
- Modify: Plan 2 tests only if verification exposes an incorrect expectation

**Interfaces:**
- Consumes: all Plan 2 deliverables and the supplied admin HTML visual source.
- Produces: verified Plan 2 release gate while preserving Plan 1 behavior and development data.

- [ ] **Step 1: Run task-scoped formatting**

Run Pint in test mode over every PHP file changed by Plan 2. If it fails, format only Plan 2 files and rerun.

- [ ] **Step 2: Run the complete PHP suite**

Run: `php artisan test`

Expected: all tests pass with zero errors or failures.

- [ ] **Step 3: Build production assets**

Run: `npm run build`

Expected: Vite compiles the local Chart.js and Phosphor assets and emits a production manifest.

- [ ] **Step 4: Verify routes, migrations, and preserved data**

Run: `php artisan route:list --except-vendor`, `php artisan migrate:status`, and read-only database counts. Confirm current accounts, six categories, and `TKT-2026-00006` remain; never run `migrate:fresh` against development.

- [ ] **Step 5: Capture the exact reference and implementation at matching viewports**

Use the in-app browser where possible; use the existing local-file browser surface only for the provided reference if the in-app browser blocks `file://`. Compare equivalent dashboard, queue, and open-drawer states at 1440 × 900 and a narrow mobile viewport.

- [ ] **Step 6: Run interaction QA**

Verify admin and staff login destinations, role-scoped metrics/activities/queue, search and every filter, row-to-drawer navigation, close/back/refresh behavior, status/priority/assignment/reply validation, attachments, theme persistence, sidebar keyboard behavior, mobile layout, logout, and browser console output.

- [ ] **Step 7: Update `design-qa.md` and fix all P0/P1/P2 findings**

Record the paired reference/implementation comparisons and repeat until the workspace section says exactly `final result: passed`. Preserve the existing customer QA evidence.

- [ ] **Step 8: Record the plan completion checkpoint**

Report exact test/build/browser evidence, delivered behavior, and the remaining Plan 3 boundary. Do not commit.
