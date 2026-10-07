# HelpDesk Authentication and Customer Portal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the public homepage and separate customer ticket list with a protected, database-backed green customer portal while strengthening login, logout, password, and database-session behavior.

**Architecture:** A focused `CustomerDashboardController` owns the customer portal query and renders the approved green Blade experience. A role-aware destination service centralizes post-authentication routing, and a database-session service owns safe session listing and revocation. Existing ticket actions, policies, private attachments, and staff/admin queue behavior remain intact for the next workspace plan.

**Tech Stack:** Laravel 12.69.3, PHP 8.2, MySQL, Blade, Tailwind CSS 4, Vite, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-06-helpdesk-live-system-redesign.md`

## Global Constraints

- The product name is **HelpDesk**; do not render Deskly branding.
- `/`, dashboards, tickets, articles, reports, and settings require an authenticated active account; only authentication and password-recovery routes are public.
- Customers use `/`; staff/admin use `/dashboard`; customers visiting `/tickets` are redirected to `/`.
- Successful customer registration and login redirect to `/`; staff/admin login redirects to `/dashboard` unless a safe authorized local intended URL exists.
- Sessions use the database driver, expire after 120 minutes of inactivity, and use a 30-day remember duration.
- Successful login regenerates the session ID; logout invalidates the session and CSRF token and redirects to `/login` with confirmation.
- Every customer statistic and ticket row is derived from that signed-in customer's records.
- Preserve `TKT-2026-00006`, all genuine users, and all existing migrations; schema changes in this plan are additive only.
- Do not add a JavaScript framework or new runtime dependency.
- Do not perform Git commits until the user explicitly re-enables repository operations.

## Review Focus

- A crafted external or protocol-relative intended URL must never become a post-login redirect; test this in Task 2.
- A customer with tickets created by another user must never count or render those records; test this in Task 4.
- A deactivated user with an existing session or remember cookie must be logged out before protected content renders; test this in Task 2.
- A session-revocation request with a wrong password or another user's session identifier must not remove sessions; test this in Task 5.
- Empty, malformed, or unsupported customer filter values must return a usable portal without broadening the authorized query; test this in Task 4.

---

## File Structure

### Authentication and routing

- `app/Services/LoginDestination.php`: returns the safe role-aware destination after login or registration.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`: coordinates login/logout and session messages.
- `app/Http/Controllers/Auth/RegisteredUserController.php`: creates a customer and sends them to the portal.
- `app/Http/Requests/Auth/LoginRequest.php`: validates credentials, applies rate limiting, active-account filtering, and remember duration.
- `bootstrap/app.php`: configures role-aware guest/auth redirects and existing middleware aliases.
- `config/auth.php`: exposes the exact 30-day remember duration.
- `routes/web.php` and `routes/auth.php`: define the protected portal and public authentication boundaries.

### Customer portal

- `app/Http/Controllers/CustomerDashboardController.php`: scopes statistics, filters, recent tickets, and paginated history to the current customer.
- `resources/views/customer/dashboard.blade.php`: authenticated green dashboard adapted from the current landing page.
- `resources/views/components/customer-ticket-row.blade.php`: one reusable accessible ticket summary.
- `resources/views/welcome.blade.php`: removed only after the protected customer dashboard passes.
- `resources/views/layouts/portal.blade.php`: customer navigation, alerts, theme control, responsive menu, and logout.
- `app/Http/Controllers/TicketController.php`: redirects customers away from the staff queue while preserving create/show behavior.

### Account security

- `app/Services/UserSessionManager.php`: lists current-user database sessions, describes devices safely, and revokes only authorized sessions.
- `app/Http/Controllers/ProfilePasswordController.php`: changes a password, rotates the current session, and revokes other sessions.
- `app/Http/Controllers/ProfileSessionController.php`: revokes other sessions after current-password validation.
- `app/Http/Requests/UpdatePasswordRequest.php`: validates current password and confirmed replacement password.
- `app/Http/Requests/DestroyOtherSessionsRequest.php`: validates current password for session revocation.
- `resources/views/profile/edit.blade.php`: profile, password, active-session, and logout interface.
- `resources/views/auth/login.blade.php`: improved validation, password visibility, remember copy, and status feedback.
- `resources/js/app.js`: progressive password-visibility and existing navigation/theme behaviors.

### Tests and configuration

- `tests/Feature/Auth/AuthenticationTest.php`: redirects, safe intended URLs, session rotation, remember login, logout, and inactive sessions.
- `tests/Feature/Auth/RegistrationTest.php`: protected-root registration destination.
- `tests/Feature/CustomerDashboardTest.php`: portal data, isolation, filters, history, and queue redirect.
- `tests/Feature/ProfileSecurityTest.php`: password and database-session lifecycle.
- `tests/Feature/Views/LayoutSmokeTest.php`: authenticated portal navigation and logout control.
- `tests/Feature/WelcomePageTest.php`: replaced by protected-root expectations or removed after equivalent coverage exists.
- `.env.example`: documents database sessions and safe local defaults without production secrets.

---

### Task 1: Protect the Root Route and Centralize Role Destinations

**Files:**
- Create: `app/Services/LoginDestination.php`
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/RegistrationTest.php`
- Create: `tests/Feature/CustomerDashboardTest.php`

**Interfaces:**
- Consumes: `App\Enums\UserRole`, Laravel `Request`, and named routes `portal`, `dashboard`, `login`.
- Produces: `LoginDestination::for(User $user, ?string $intended = null): string`; named authenticated route `portal` at `/`; staff/admin route `dashboard` at `/dashboard`.

- [ ] **Step 1: Write failing routing tests**

Add tests named:

- `test_guest_root_redirects_to_login`
- `test_customer_root_is_the_portal`
- `test_staff_and_admin_root_redirect_to_workspace_dashboard`
- `test_authenticated_users_are_redirected_from_login_by_role`

Assert exact redirect paths and that inactive users cannot render either destination.

- [ ] **Step 2: Run routing tests and verify RED**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/CustomerDashboardTest.php`

Expected: FAIL because `/` is public and `LoginDestination`/`portal` do not exist.

- [ ] **Step 3: Implement the destination service and protected routes**

Create `LoginDestination::for(User $user, ?string $intended = null): string`. Default customers to `route('portal', absolute: false)` and staff/admin to `route('dashboard', absolute: false)`. Treat only a single-leading-slash local path as potentially intended; reject absolute, protocol-relative, login, registration, and role-forbidden queue paths.

Define `/` inside `auth` and `active` middleware as `portal`. Keep `/dashboard` protected and temporarily redirect staff/admin to `tickets.index` until the workspace dashboard plan replaces it. Configure authenticated-user redirects for guest routes through the same service.

- [ ] **Step 4: Run routing tests and verify GREEN**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/CustomerDashboardTest.php`

Expected: PASS for the new routing tests; pre-existing redirect assertions may remain red until Task 2 updates controllers.

- [ ] **Step 5: Record the no-commit checkpoint**

Capture changed files and test output in the task log; do not commit.

### Task 2: Harden Login, Remembered Login, Logout, and Role Redirects

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `app/Http/Requests/Auth/LoginRequest.php`
- Modify: `config/auth.php`
- Modify: `routes/auth.php`
- Modify: `resources/views/auth/login.blade.php`
- Modify: `resources/js/app.js`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/RegistrationTest.php`

**Interfaces:**
- Consumes: `LoginDestination::for(User, ?string): string` from Task 1.
- Produces: `LoginRequest::authenticate(): void` using `config('auth.remember_duration', 43200)` minutes; logout flash status `You have been signed out safely.`

- [ ] **Step 1: Write failing authentication lifecycle tests**

Add assertions for:

- Customers redirect to `/` after login and registration.
- Staff/admin redirect to `/dashboard`.
- A safe local intended customer ticket URL is honored.
- `https://evil.example`, `//evil.example`, and a customer intended `/tickets` value are rejected in favor of the role destination.
- The session ID changes after successful login.
- Checking `remember` creates a remember token/cookie and uses the exact 43,200-minute duration.
- Logout changes the session ID, clears an existing sentinel value, makes the user a guest, redirects to `/login`, and flashes the exact confirmation message.
- Inactive accounts cannot establish or retain normal/remembered authentication.

- [ ] **Step 2: Run the authentication tests and verify RED**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php`

Expected: FAIL on the old `/dashboard` and `/` redirects, missing safe-intended handling, missing logout confirmation, or remember-duration behavior.

- [ ] **Step 3: Implement role-aware authentication behavior**

Inject `LoginDestination` into login and registration stores. Set the session guard remember duration before `Auth::attempt`. Pull and forget `url.intended`, pass it through the destination service, regenerate the session after login/registration, and redirect to the returned path. Preserve five-attempt rate limiting and `is_active=true` credentials.

On logout, call `Auth::guard('web')->logout()`, invalidate the session, regenerate the CSRF token, and redirect to `login` with the exact status message.

- [ ] **Step 4: Improve the login interaction without weakening server validation**

Keep email/password/remember field names stable. Add accessible per-field errors, an `aria-live` status area, a password visibility button implemented with existing vanilla JavaScript, clear session-duration copy, and links to password recovery and registration. Do not display whether an email exists.

- [ ] **Step 5: Run authentication tests and verify GREEN**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/RegistrationTest.php`

Expected: PASS.

- [ ] **Step 6: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 3: Build the Real Customer Dashboard Query

**Files:**
- Create: `app/Http/Controllers/CustomerDashboardController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/CustomerDashboardTest.php`

**Interfaces:**
- Consumes: `User::createdTickets()`, `TicketStatus`, `TicketPriority`, active `Category` records, and existing ticket relationships.
- Produces: `CustomerDashboardController::__invoke(Request $request): View|RedirectResponse`; view data `stats`, `recentTickets`, `tickets`, `categories`, and `filters`.

- [ ] **Step 1: Write failing customer dashboard data tests**

Create tests named:

- `test_customer_dashboard_counts_only_their_tickets_by_status`
- `test_customer_dashboard_recent_tickets_are_owned_ordered_and_limited`
- `test_customer_history_search_and_filters_remain_owner_scoped`
- `test_malformed_filters_do_not_broaden_the_customer_query`
- `test_staff_and_admin_portal_requests_redirect_to_dashboard`

Use two customers with overlapping subjects/statuses and assert the other customer's public number and subject never render or affect counts. Assert total/open/in-progress/resolved keys and exact values. Assert recent tickets are newest-first and limited to five. Assert history paginates ten records and retains query parameters.

- [ ] **Step 2: Run the dashboard tests and verify RED**

Run: `php artisan test tests/Feature/CustomerDashboardTest.php`

Expected: FAIL because the controller and data contract do not exist.

- [ ] **Step 3: Implement the controller query contract**

Implement `__invoke(Request $request): View|RedirectResponse`. Redirect non-customer roles to `dashboard`. Start every customer query from `$request->user()->createdTickets()` before applying search, `TicketStatus::tryFrom`, `TicketPriority::tryFrom`, and validated integer category filtering. Eager-load category and assignee. Compute stats with database aggregate queries, recent tickets with a five-row query, and history with ten-row pagination plus `withQueryString()`.

- [ ] **Step 4: Run the dashboard tests and verify GREEN**

Run: `php artisan test tests/Feature/CustomerDashboardTest.php`

Expected: PASS.

- [ ] **Step 5: Record the no-commit checkpoint**

Capture the changed-file list and passing test output; do not commit.

### Task 4: Convert the Green Landing Design into the Authenticated Portal

**Files:**
- Create: `resources/views/layouts/portal.blade.php`
- Create: `resources/views/customer/dashboard.blade.php`
- Create: `resources/views/components/customer-ticket-row.blade.php`
- Modify: `resources/views/tickets/create.blade.php`
- Modify: `resources/views/tickets/show.blade.php`
- Modify: `app/Http/Controllers/TicketController.php`
- Remove after verification: `resources/views/welcome.blade.php`
- Modify: `tests/Feature/CustomerDashboardTest.php`
- Modify: `tests/Feature/Views/LayoutSmokeTest.php`
- Remove or replace: `tests/Feature/WelcomePageTest.php`

**Interfaces:**
- Consumes: Task 3 view data; named routes `portal`, `tickets.create`, `tickets.show`, `knowledge-base.index`, `profile.edit`, and `logout` where available.
- Produces: authenticated portal anchors `#overview` and `#my-tickets`; reusable `<x-customer-ticket-row :ticket="$ticket" />`.

- [ ] **Step 1: Write failing portal rendering tests**

Assert:

- Guest `/` redirects and contains no portal HTML.
- An authenticated customer sees their name, exact statistic values, owned recent tickets, full-history filters, create-ticket link, profile link, knowledge-base link or approved staged disabled state, and a POST logout form.
- The portal contains no Sign in/Create account controls, guest marketing claims, Deskly text, fake ticket number, or “Available after sign in” copy.
- Empty customers see the truthful empty state and create-ticket action.
- Customer `/tickets` redirects to `/`, while `/tickets/create` and an owned `/tickets/{ticket}` remain accessible.
- Staff/admin `/tickets` behavior remains unchanged.

- [ ] **Step 2: Run portal rendering tests and verify RED**

Run: `php artisan test tests/Feature/CustomerDashboardTest.php tests/Feature/Views/LayoutSmokeTest.php tests/Feature/WelcomePageTest.php`

Expected: FAIL because the public welcome view and customer queue still exist.

- [ ] **Step 3: Build the authenticated portal layout and dashboard view**

Port the approved green variables, responsive header, theme toggle, summary cards, and ticket container into the new files. Replace guest actions and promotional sections with authenticated navigation and real Task 3 data. Render ticket content through the reusable row component, include accessible filter labels, and use the existing pagination renderer. Preserve escaped output and visible focus states.

- [ ] **Step 4: Redirect customers away from the staff queue**

At the start of `TicketController::index(Request $request)`, return `redirect(route('portal', absolute: false).'#my-tickets')` for `UserRole::User`; retain the existing authorized queue query for staff/admin. Do not change create/store/show policy behavior.

- [ ] **Step 5: Replace obsolete public-home coverage and remove the unused view**

Move all still-valid HelpDesk branding, theme, menu, and link assertions into customer dashboard/layout tests. Remove `WelcomePageTest` only after equivalent protected-route assertions pass, then remove `welcome.blade.php` after `rg "view\('welcome'\)|welcome\.blade"` returns no application reference.

- [ ] **Step 6: Run portal tests and verify GREEN**

Run: `php artisan test tests/Feature/CustomerDashboardTest.php tests/Feature/Views/LayoutSmokeTest.php tests/Feature/Tickets/TicketCrudTest.php tests/Feature/Tickets/TicketFilterTest.php`

Expected: PASS.

- [ ] **Step 7: Record the no-commit checkpoint**

Capture files, test output, and the `/`, `/tickets`, `/tickets/create`, and ticket-detail route behavior; do not commit.

### Task 5: Add Password Changes and Authorized Session Management

**Files:**
- Create: `app/Services/UserSessionManager.php`
- Create: `app/Http/Controllers/ProfilePasswordController.php`
- Create: `app/Http/Controllers/ProfileSessionController.php`
- Create: `app/Http/Requests/UpdatePasswordRequest.php`
- Create: `app/Http/Requests/DestroyOtherSessionsRequest.php`
- Modify: `app/Http/Controllers/ProfileController.php`
- Modify: `resources/views/profile/edit.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/ProfileSecurityTest.php`
- Modify: `tests/Feature/ProfileTest.php`

**Interfaces:**
- Consumes: configured session connection/table, current Laravel session ID, `Hash`, and authenticated `User`.
- Produces: `UserSessionManager::for(User $user, string $currentSessionId): Collection`; `UserSessionManager::revokeOthers(User $user, string $currentSessionId): int`; `UserSessionManager::deviceLabel(?string $userAgent): string`; named routes `profile.password.update` and `profile.sessions.destroy-others`.

- [ ] **Step 1: Write failing profile-security tests**

Add tests named:

- `test_profile_lists_only_the_current_users_database_sessions`
- `test_current_session_is_identified_without_exposing_payload`
- `test_correct_password_revokes_only_other_sessions`
- `test_wrong_password_does_not_revoke_sessions`
- `test_another_users_sessions_cannot_be_revoked`
- `test_password_change_requires_current_password_and_confirmation`
- `test_password_change_rotates_current_session_and_revokes_other_sessions`

Seed database session rows for two users with distinct IDs, IPs, agents, and activity times. Assert session payload and remember token never render.

- [ ] **Step 2: Run profile-security tests and verify RED**

Run: `php artisan test tests/Feature/ProfileSecurityTest.php tests/Feature/ProfileTest.php`

Expected: FAIL because the service, requests, routes, and UI do not exist.

- [ ] **Step 3: Implement `UserSessionManager`**

Query only `user_id=$user->id` on the configured session connection/table. Return presentation objects ordered by `last_activity` descending with ID, safe device label, IP, last activity, and `is_current`; never return payload. `revokeOthers` must delete only matching user rows whose ID differs from the current ID. Implement deterministic labels for common Chrome/Edge/Firefox/Safari and Windows/macOS/Linux/mobile agents, falling back to `Unknown device` without reflecting raw markup.

- [ ] **Step 4: Implement password and session controllers/requests**

Validate `current_password` through Laravel's rule. Password replacement uses `required`, `confirmed`, and `Rules\Password::defaults()`. Within the update flow, hash/save the new password, revoke other database sessions, regenerate the current session, regenerate the CSRF token, and flash a success message. The revoke-others endpoint requires the current password and returns the number revoked only as a user-facing summary.

- [ ] **Step 5: Render profile security sections**

Keep basic profile editing, add the confirmed password form, render safe session cards with current-device indication, and add a revoke-other-sessions form. Every destructive form uses CSRF and the correct HTTP method. Keep a visible POST logout action.

- [ ] **Step 6: Run profile-security tests and verify GREEN**

Run: `php artisan test tests/Feature/ProfileSecurityTest.php tests/Feature/ProfileTest.php`

Expected: PASS.

- [ ] **Step 7: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 6: Configuration, Full Regression, and Visual Gate

**Files:**
- Modify: `.env.example`
- Modify: `README.md` only for the newly operational authentication/session/customer behavior
- Modify: tests from Tasks 1–5 if verification exposes incorrect expectations
- Create during Product Design QA: `design-qa.md`

**Interfaces:**
- Consumes: all prior tasks in this plan.
- Produces: verified Plan 1 release gate and documented local/production session values.

- [ ] **Step 1: Pin safe example configuration**

Set/document `SESSION_DRIVER=database`, `SESSION_LIFETIME=120`, `SESSION_EXPIRE_ON_CLOSE=false`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, and `SESSION_SAME_SITE=lax`. Keep `SESSION_SECURE_COOKIE=false` for local HTTP in `.env.example` and explicitly document that production HTTPS must set it to `true`. Do not copy credentials or the current `.env` into documentation.

- [ ] **Step 2: Run task-scoped formatting**

Run Pint in test mode on every PHP file changed by this plan.

Expected: PASS. If it fails, format only plan-owned files and rerun.

- [ ] **Step 3: Run the complete automated suite**

Run: `php artisan test`

Expected: all tests pass with zero errors or failures.

- [ ] **Step 4: Build production assets**

Run: `npm run build`

Expected: Vite completes successfully and writes a production manifest/assets.

- [ ] **Step 5: Verify route and migration state**

Run: `php artisan route:list --except-vendor` and `php artisan migrate:status`.

Expected: `/` is protected, auth routes remain public, profile security routes are protected, all migrations show Ran, and there are no duplicate route names.

- [ ] **Step 6: Run visual and interaction QA**

Use the in-app browser on `http://127.0.0.1:8000` and verify:

- Guest `/` reaches login.
- Registration creates a customer and opens the green portal.
- Customer statistics/history reflect database records.
- Customer `/tickets` redirects without breaking create/details.
- Login validation, show/hide password, remember login, profile security, mobile navigation, theme, and logout work.
- Light/dark layouts at desktop and narrow mobile widths have no critical/major mismatch, overlap, horizontal overflow, inaccessible control, or console error.

Record comparisons and findings in `design-qa.md`; fix P0/P1/P2 findings and repeat until it says `final result: passed`.

- [ ] **Step 7: Confirm production-data preservation**

Query the development database and confirm the genuine ticket `TKT-2026-00006`, current accounts, and six categories remain. Do not run `migrate:fresh` against the development database.

- [ ] **Step 8: Record the plan completion checkpoint**

Report behavior delivered, exact verification evidence, and any blocker for Plan 2. Do not commit.
