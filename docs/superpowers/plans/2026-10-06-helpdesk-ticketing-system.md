# HelpDesk Ticketing System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete, responsive Laravel help-desk application with connected user, staff, and admin workflows suitable for a junior developer portfolio.

**Architecture:** Use Laravel 12 server-rendered Blade MVC with built-in session authentication, enum-backed roles/statuses/priorities, policies for record authorization, focused actions for transactional ticket operations, and private attachment downloads. Admin/staff use the indigo workspace shell; users use the approved green customer portal, with both interfaces backed by the same domain records.

**Tech Stack:** PHP 8.2, Laravel 12.69.3, MySQL, Blade, Tailwind CSS 4, Vite 7, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-10-06-helpdesk-ticketing-system-design.md`

## Global Constraints

- Keep Laravel at 12.x and PHP compatibility at `^8.2`; do not add Livewire, Breeze, Jetstream, an SPA framework, or Bootstrap.
- Production/development configuration targets MySQL; tests may use SQLite in memory when migrations remain MySQL-compatible.
- Public registration always creates `user`; only admins may change roles.
- Brand every interface as **HelpDesk**. Reference HTML files supply visual direction only.
- Store status, priority, and role as strings backed by PHP enums.
- Store attachments privately; allow JPG, JPEG, PNG, PDF, TXT, DOC, and DOCX up to 5 MB.
- Keep user-provided output escaped through Blade defaults and protect every mutation with validation, CSRF, and authorization.
- Closed tickets are read-only; only admins may reopen them.
- Preserve unrelated Laravel skeleton files and never commit secrets or `.env`.

## Review Focus

- Invalid or forged status values/transitions must return validation/authorization errors without partially changing ticket timestamps or history; pinned in Task 3.
- Rapid ticket creation must never create duplicate public numbers; pinned in Task 3.
- Search/filter parameters must not leak tickets outside the caller's authorized scope, including malformed enum/category/assignee values; pinned in Task 4.
- Spoofed MIME types, executable uploads, oversized files, and path-like original names must be rejected or safely renamed; pinned in Task 5.
- The last active admin must not be able to demote/deactivate themselves through crafted requests; pinned in Task 8.

---

## File Structure

- `app/Enums/`: `UserRole`, `TicketStatus`, `TicketPriority`, and `TicketActivityAction`; each owns values, labels, and display metadata.
- `app/Models/`: Eloquent records and named relationships only.
- `app/Actions/Tickets/`: create, number, status, assignment, and priority operations with transactions/activity recording.
- `app/Services/TicketActivityRecorder.php`: single activity-write interface used by all ticket mutations.
- `app/Http/Controllers/Auth/`: built-in session registration, authentication, logout, and password-reset endpoints.
- `app/Http/Controllers/`: dashboards, profile, tickets, comments, attachments, assignment, status, and priority endpoints.
- `app/Http/Controllers/Admin/`: user and category management.
- `app/Http/Requests/`: one request per write concern.
- `app/Policies/TicketPolicy.php`: ticket visibility and action permissions.
- `resources/views/layouts/`: guest, admin/staff workspace, and green user portal shells.
- `resources/views/components/`: shared alerts, form fields, badges, empty states, and responsive ticket display.
- `resources/views/auth`, `profile`, `dashboard`, `tickets`, and `admin`: screen-specific Blade views.
- `tests/Feature/`: behavior at HTTP/authorization/database boundaries; `tests/Unit/`: enum and isolated domain rules.

---

### Task 0: Record and Verify the Fresh Laravel Baseline

**Files:**
- Add unchanged scaffold: `.editorconfig`, `.env.example`, `.gitattributes`, `.gitignore`, `README.md`, `app/`, `artisan`, `bootstrap/`, `composer.json`, `composer.lock`, `config/`, `database/`, `package.json`, `phpunit.xml`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vite.config.js`

**Interfaces:**
- Produces: a reproducible Laravel 12.69.3 baseline commit before product changes.

- [ ] **Step 1: Verify the environment and ignored files**

Run: `php artisan --version`

Run: `git check-ignore .env vendor node_modules`

Expected: Laravel reports 12.69.3 and all three local/dependency paths are ignored.

- [ ] **Step 2: Run the untouched scaffold checks**

Run: `php artisan test`

Run: `npm run build`

Expected: default tests and Vite build PASS before product work begins.

- [ ] **Step 3: Commit the fresh scaffold**

```bash
git add .editorconfig .env.example .gitattributes .gitignore README.md app artisan bootstrap composer.json composer.lock config database package.json phpunit.xml public resources routes storage tests vite.config.js
git commit -m "chore: record fresh Laravel scaffold"
```

### Task 1: Domain Schema, Enums, Models, and Factories

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/TicketStatus.php`
- Create: `app/Enums/TicketPriority.php`
- Create: `app/Enums/TicketActivityAction.php`
- Create: `app/Models/Category.php`
- Create: `app/Models/Ticket.php`
- Create: `app/Models/TicketComment.php`
- Create: `app/Models/TicketAttachment.php`
- Create: `app/Models/TicketActivity.php`
- Create: `database/migrations/2026_10_06_000001_add_role_and_active_to_users_table.php`
- Create: `database/migrations/2026_10_06_000002_create_categories_table.php`
- Create: `database/migrations/2026_10_06_000003_create_tickets_table.php`
- Create: `database/migrations/2026_10_06_000004_create_ticket_comments_table.php`
- Create: `database/migrations/2026_10_06_000005_create_ticket_attachments_table.php`
- Create: `database/migrations/2026_10_06_000006_create_ticket_activities_table.php`
- Create: `database/factories/CategoryFactory.php`
- Create: `database/factories/TicketFactory.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `phpunit.xml`
- Test: `tests/Unit/EnumsTest.php`
- Test: `tests/Feature/Database/ModelRelationshipsTest.php`

**Interfaces:**
- Produces: enum methods `label(): string`, `badgeClasses(): string`; `TicketStatus::canTransitionTo(TicketStatus $next, UserRole $actorRole): bool`; Eloquent relationships named `createdTickets`, `assignedTickets`, `comments`, `attachments`, `activities`, `creator`, `assignee`, and `category`.

- [ ] **Step 1: Write failing enum and relationship tests**

Create tests asserting exact enum values, default user role/active state, casts, nullable assignment, and all named relationships. Include foreign-key behavior: referenced users/categories are not cascade-deleted; ticket children cascade with the ticket.

- [ ] **Step 2: Run the focused tests and verify failure**

Run: `php artisan test tests/Unit/EnumsTest.php tests/Feature/Database/ModelRelationshipsTest.php`

Expected: FAIL because enums, tables, models, and relationships do not exist.

- [ ] **Step 3: Implement enums and migrations**

Use string enum values `admin|staff|user`, `open|in_progress|pending|resolved|closed`, and `low|medium|high|urgent`. Activity values are `created`, `claimed`, `assigned`, `status_changed`, `priority_changed`, `resolved`, `closed`, `reopened`, `comment_added`, and `attachment_added`. Make `ticket_number` nullable during the initial insert and unique once populated; make category names unique and active by default. Add indexes for creator, assignee, category, status, priority, and timestamps used by dashboards.

- [ ] **Step 4: Implement models, casts, relationships, fillable fields, and factories**

Set enum casts on `User::$role`, `Ticket::$status`, and `Ticket::$priority`; cast active/timestamp fields explicitly. Keep passwords on Laravel's `hashed` cast.

- [ ] **Step 5: Run migrations and focused tests**

Run: `php artisan migrate:fresh --env=testing`

Run: `php artisan test tests/Unit/EnumsTest.php tests/Feature/Database/ModelRelationshipsTest.php`

Expected: migrations succeed and all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Enums app/Models database/migrations database/factories phpunit.xml tests/Unit/EnumsTest.php tests/Feature/Database/ModelRelationshipsTest.php
git commit -m "feat: add helpdesk domain model"
```

### Task 2: Authentication, Active Accounts, Roles, and Profile

**Files:**
- Create: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Create: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Create: `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- Create: `app/Http/Controllers/Auth/NewPasswordController.php`
- Create: `app/Http/Controllers/ProfileController.php`
- Create: `app/Http/Middleware/EnsureUserIsActive.php`
- Create: `app/Http/Middleware/EnsureUserHasRole.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/ProfileUpdateRequest.php`
- Create: `resources/views/layouts/guest.blade.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`
- Create: `resources/views/auth/forgot-password.blade.php`
- Create: `resources/views/auth/reset-password.blade.php`
- Create: `resources/views/profile/edit.blade.php`
- Modify: `bootstrap/app.php`
- Create: `routes/auth.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/RegistrationTest.php`
- Test: `tests/Feature/Auth/PasswordResetTest.php`
- Test: `tests/Feature/ProfileTest.php`
- Test: `tests/Feature/RoleMiddlewareTest.php`

**Interfaces:**
- Consumes: `UserRole` and `User::$is_active` from Task 1.
- Produces: route middleware aliases `active` and `role`; named routes `login`, `register`, `password.request`, `password.email`, `password.reset`, `password.update`, `logout`, `profile.edit`, and `profile.update`.

- [ ] **Step 1: Write failing authentication and middleware tests**

Assert registration ignores a forged `role=admin`, hashes passwords, logs the new user in, and assigns `UserRole::User`. Assert inactive users cannot establish or retain an authenticated session. Assert guests/admin/staff/users receive the correct route results.

- [ ] **Step 2: Run authentication tests and verify failure**

Run: `php artisan test tests/Feature/Auth tests/Feature/ProfileTest.php tests/Feature/RoleMiddlewareTest.php`

Expected: FAIL because routes/controllers/middleware/views are missing.

- [ ] **Step 3: Implement session authentication and password reset**

Use `Auth::attempt`, session regeneration/invalidation, `Password::sendResetLink`, and `Password::reset`. Rate-limit login attempts through `LoginRequest`; never accept role or active state during registration.

- [ ] **Step 4: Implement active/role middleware and profile update**

Register aliases in `bootstrap/app.php`. `EnsureUserHasRole::handle(Request $request, Closure $next, string ...$roles): Response` accepts enum string values and aborts with 403 when unauthorized.

- [ ] **Step 5: Add guest/profile Blade views and run tests**

Run: `php artisan test tests/Feature/Auth tests/Feature/ProfileTest.php tests/Feature/RoleMiddlewareTest.php`

Expected: all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Auth app/Http/Controllers/ProfileController.php app/Http/Middleware app/Http/Requests resources/views/auth resources/views/layouts/guest.blade.php resources/views/profile bootstrap/app.php routes tests/Feature/Auth tests/Feature/ProfileTest.php tests/Feature/RoleMiddlewareTest.php
git commit -m "feat: add authentication and role protection"
```

### Task 3: Ticket Domain Actions, Policies, and Status Rules

**Files:**
- Create: `app/Policies/TicketPolicy.php`
- Create: `app/Services/TicketActivityRecorder.php`
- Create: `app/Actions/Tickets/CreateTicketAction.php`
- Create: `app/Actions/Tickets/GenerateTicketNumberAction.php`
- Create: `app/Actions/Tickets/AssignTicketAction.php`
- Create: `app/Actions/Tickets/UpdateTicketStatusAction.php`
- Create: `app/Actions/Tickets/UpdateTicketPriorityAction.php`
- Test: `tests/Feature/Tickets/TicketPolicyTest.php`
- Test: `tests/Feature/Tickets/TicketActionsTest.php`
- Test: `tests/Unit/TicketStatusTransitionTest.php`

**Interfaces:**
- Consumes: Task 1 models/enums.
- Produces: `CreateTicketAction::execute(User $creator, array $attributes): Ticket`; `GenerateTicketNumberAction::execute(Ticket $ticket): string`; `AssignTicketAction::execute(Ticket $ticket, ?User $assignee, User $actor): Ticket`; `UpdateTicketStatusAction::execute(Ticket $ticket, TicketStatus $next, User $actor): Ticket`; `UpdateTicketPriorityAction::execute(Ticket $ticket, TicketPriority $next, User $actor): Ticket`; `TicketActivityRecorder::record(Ticket $ticket, ?User $actor, TicketActivityAction $action, ?string $oldValue = null, ?string $newValue = null): TicketActivity`.

- [ ] **Step 1: Write failing policy and action tests**

Assert users see only created tickets; staff see assigned/unassigned tickets but not another staff member's assigned ticket; admins see all. Assert creation writes `TKT-YYYY-{id padded to 5}`, records activity, and returns a refreshed ticket.

- [ ] **Step 2: Add failure-mode tests for transitions and numbering**

Assert forged/invalid transitions do not alter status, `resolved_at`, `closed_at`, or activity count. Create multiple tickets in rapid succession and assert every public number is unique and matches its persisted ID.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/Tickets/TicketPolicyTest.php tests/Feature/Tickets/TicketActionsTest.php tests/Unit/TicketStatusTransitionTest.php`

Expected: FAIL because domain actions and policy do not exist.

- [ ] **Step 4: Implement the policy, recorder, and transactional actions**

Use `DB::transaction`. Reject invalid transitions with `ValidationException`. Synchronize `resolved_at` and `closed_at`; clear them only on a permitted reopening. Validate assignees are active staff.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/Tickets/TicketPolicyTest.php tests/Feature/Tickets/TicketActionsTest.php tests/Unit/TicketStatusTransitionTest.php`

Expected: all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Policies app/Services app/Actions tests/Feature/Tickets/TicketPolicyTest.php tests/Feature/Tickets/TicketActionsTest.php tests/Unit/TicketStatusTransitionTest.php
git commit -m "feat: add authorized ticket workflow"
```

### Task 4: Ticket HTTP CRUD, Search, Filters, Assignment, and Pagination

**Files:**
- Create: `app/Http/Controllers/TicketController.php`
- Create: `app/Http/Controllers/TicketAssignmentController.php`
- Create: `app/Http/Controllers/TicketStatusController.php`
- Create: `app/Http/Controllers/TicketPriorityController.php`
- Create: `app/Http/Requests/StoreTicketRequest.php`
- Create: `app/Http/Requests/AssignTicketRequest.php`
- Create: `app/Http/Requests/UpdateTicketStatusRequest.php`
- Create: `app/Http/Requests/UpdateTicketPriorityRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Tickets/TicketCrudTest.php`
- Test: `tests/Feature/Tickets/TicketFilterTest.php`
- Test: `tests/Feature/Tickets/TicketManagementTest.php`

**Interfaces:**
- Consumes: Task 3 actions/policy.
- Produces: named resource-style routes `tickets.index|create|store|show`; mutation routes `tickets.assignment.update`, `tickets.status.update`, and `tickets.priority.update`; `TicketController::authorizedQuery(User $user): Builder` kept private or extracted only if reused.

- [ ] **Step 1: Write failing ticket CRUD and management tests**

Assert validated creation redirects to the public ticket route with success flash; show uses public ticket-number binding; assignment/status/priority calls enforce policy and produce activity.

- [ ] **Step 2: Write failing query-boundary tests**

Seed own, unassigned, assigned-to-self, and assigned-to-other tickets. For every role, combine search with valid and malformed status/priority/category/assignee parameters and assert response contents never include unauthorized subjects. Assert query strings persist across pagination links.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/Tickets/TicketCrudTest.php tests/Feature/Tickets/TicketFilterTest.php tests/Feature/Tickets/TicketManagementTest.php`

Expected: FAIL because HTTP endpoints are missing.

- [ ] **Step 4: Implement requests, controllers, scoped query filters, and routes**

Use enum validation rules, active-category existence rules, eager loading, `withQueryString()`, and 15-row pagination. Ignore or reject malformed filters consistently without broadening the authorized base query.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/Tickets/TicketCrudTest.php tests/Feature/Tickets/TicketFilterTest.php tests/Feature/Tickets/TicketManagementTest.php`

Expected: all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TicketController.php app/Http/Controllers/TicketAssignmentController.php app/Http/Controllers/TicketStatusController.php app/Http/Controllers/TicketPriorityController.php app/Http/Requests routes/web.php tests/Feature/Tickets/TicketCrudTest.php tests/Feature/Tickets/TicketFilterTest.php tests/Feature/Tickets/TicketManagementTest.php
git commit -m "feat: add ticket management endpoints"
```

### Task 5: Comments, Private Attachments, and Activity History

**Files:**
- Create: `app/Http/Controllers/TicketCommentController.php`
- Create: `app/Http/Controllers/TicketAttachmentController.php`
- Create: `app/Http/Requests/StoreTicketCommentRequest.php`
- Create: `app/Http/Requests/StoreTicketAttachmentRequest.php`
- Create: `app/Actions/Tickets/StoreTicketAttachmentAction.php`
- Modify: `app/Actions/Tickets/CreateTicketAction.php`
- Modify: `app/Http/Controllers/TicketController.php`
- Modify: `app/Http/Requests/StoreTicketRequest.php`
- Modify: `config/filesystems.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Tickets/TicketCommentTest.php`
- Test: `tests/Feature/Tickets/TicketAttachmentTest.php`
- Test: `tests/Feature/Tickets/TicketActivityTest.php`

**Interfaces:**
- Consumes: `TicketPolicy`, models, and `TicketActivityRecorder`.
- Produces: `StoreTicketAttachmentAction::execute(Ticket $ticket, User $uploader, UploadedFile $file): TicketAttachment`; revised `CreateTicketAction::execute(User $creator, array $attributes, ?UploadedFile $attachment = null): Ticket`; routes `tickets.comments.store`, `tickets.attachments.store`, and `tickets.attachments.download`.

- [ ] **Step 1: Write failing comment, download, and activity tests**

Assert authorized comments and attachments create activity; closed tickets reject mutations; unauthorized users receive 403 for create/download; missing stored files return 404 without exposing paths.

- [ ] **Step 2: Write upload hardening tests**

Use fake storage/files to assert JPG/JPEG/PNG/PDF/TXT/DOC/DOCX at or below 5 MB are accepted. Assert PHP/EXE, mismatched MIME, over-5-MB files, and names such as `../../report.pdf` are rejected or stored under generated safe paths while retaining only a display-safe original name.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/Tickets/TicketCommentTest.php tests/Feature/Tickets/TicketAttachmentTest.php tests/Feature/Tickets/TicketActivityTest.php`

Expected: FAIL because endpoints/storage action are missing.

- [ ] **Step 4: Implement validated comments and private storage**

Define a private `ticket_attachments` disk rooted under `storage/app/private/ticket-attachments`. Revise `CreateTicketAction` so ticket creation, optional attachment metadata/storage, and initial activity remain one transaction with stored-file cleanup on rollback. Stream authorized downloads with the original filename and recorded MIME type.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/Tickets/TicketCommentTest.php tests/Feature/Tickets/TicketAttachmentTest.php tests/Feature/Tickets/TicketActivityTest.php`

Expected: all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TicketCommentController.php app/Http/Controllers/TicketAttachmentController.php app/Http/Requests app/Actions/Tickets/StoreTicketAttachmentAction.php config/filesystems.php routes/web.php tests/Feature/Tickets/TicketCommentTest.php tests/Feature/Tickets/TicketAttachmentTest.php tests/Feature/Tickets/TicketActivityTest.php
git commit -m "feat: add ticket conversations and attachments"
```

### Task 6: Shared Blade Components and Role-Specific Layouts

**Files:**
- Create: `resources/views/layouts/workspace.blade.php`
- Create: `resources/views/layouts/portal.blade.php`
- Create: `resources/views/components/flash-message.blade.php`
- Create: `resources/views/components/form/input.blade.php`
- Create: `resources/views/components/form/select.blade.php`
- Create: `resources/views/components/form/textarea.blade.php`
- Create: `resources/views/components/status-badge.blade.php`
- Create: `resources/views/components/priority-badge.blade.php`
- Create: `resources/views/components/empty-state.blade.php`
- Create: `resources/views/components/ticket-card.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Test: `tests/Feature/Views/LayoutSmokeTest.php`

**Interfaces:**
- Consumes: enum label/badge metadata and named routes.
- Produces: Blade layouts consumed through `@extends('layouts.workspace')` for admin/staff and `@extends('layouts.portal')` for users; shared components accept typed values or documented scalar props.

- [ ] **Step 1: Write failing layout smoke tests**

Assert role navigation contains only permitted links, HelpDesk branding appears, flash/validation content renders escaped, and both layouts render at authenticated routes without missing components.

- [ ] **Step 2: Run the smoke test and verify failure**

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php`

Expected: FAIL because layouts/components do not exist.

- [ ] **Step 3: Implement workspace and nature-portal design tokens**

Workspace uses the approved indigo sidebar. Portal CSS variables use `#2F7D4A`, `#25663C`, `#F2F7F3`, `#E2F1E6`, `#173023`, `#65786C`, `#0D1F16`, `#142B1E`, and `#68C985`. Add class-based dark theme persistence and reduced-motion handling.

- [ ] **Step 4: Implement accessible components and responsive shells**

Use semantic navigation, visible focus states, labeled controls, escaped slots/props, a collapsible workspace sidebar, and portal navigation that fits at 320px without horizontal overflow.

- [ ] **Step 5: Build assets and run tests**

Run: `npm run build`

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php`

Expected: Vite build succeeds and smoke tests PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/css resources/js resources/views/layouts resources/views/components tests/Feature/Views/LayoutSmokeTest.php
git commit -m "feat: add responsive helpdesk layouts"
```

### Task 7: Dashboards and Connected Ticket Screens

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Create: `resources/views/dashboard/admin.blade.php`
- Create: `resources/views/dashboard/staff.blade.php`
- Create: `resources/views/dashboard/user.blade.php`
- Create: `resources/views/tickets/index.blade.php`
- Create: `resources/views/tickets/create.blade.php`
- Create: `resources/views/tickets/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DashboardTest.php`
- Test: `tests/Feature/Views/TicketViewTest.php`

**Interfaces:**
- Consumes: scoped ticket query, mutations, comments/attachments, layouts/components.
- Produces: named route `dashboard`; dashboard view data keys `statistics`, `recentTickets`, `statusCounts`, and role-specific `workload` where applicable.

- [ ] **Step 1: Write failing role-dashboard tests**

Assert every role receives its intended dashboard and counts include only authorized records. Assert admin totals include users/staff; staff workload separates assigned/unassigned; user totals include only created tickets.

- [ ] **Step 2: Write failing connected-screen tests**

Assert user create/list/detail screens use the green portal, admin/staff screens use the workspace, and the same ticket/comment/status changes appear in both authorized presentations. Assert mobile ticket-card markup and desktop table wrappers are present.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/DashboardTest.php tests/Feature/Views/TicketViewTest.php`

Expected: FAIL because dashboards and ticket views are missing.

- [ ] **Step 4: Implement dashboard queries and Blade screens**

Render a simple server-side status bar chart without a new chart dependency. Keep the user portal's welcome area focused on ticket search, summaries, recent tickets, and create-ticket actions; do not add knowledge-base claims.

- [ ] **Step 5: Implement full ticket detail and authorized action panels**

Show metadata, description, private downloads, conversation entries with author name/role/time, activity entries with actor/time, assignment/status/priority controls by policy, and a user close action only when resolved.

- [ ] **Step 6: Build assets and run focused tests**

Run: `npm run build`

Run: `php artisan test tests/Feature/DashboardTest.php tests/Feature/Views/TicketViewTest.php`

Expected: build succeeds and all focused tests PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/DashboardController.php resources/views/dashboard resources/views/tickets routes/web.php tests/Feature/DashboardTest.php tests/Feature/Views/TicketViewTest.php
git commit -m "feat: add connected role dashboards and ticket views"
```

### Task 8: Admin User and Category Management

**Files:**
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `app/Http/Controllers/Admin/CategoryController.php`
- Create: `app/Http/Requests/Admin/StoreUserRequest.php`
- Create: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Create: `app/Http/Requests/Admin/StoreCategoryRequest.php`
- Create: `app/Http/Requests/Admin/UpdateCategoryRequest.php`
- Create: `resources/views/admin/users/index.blade.php`
- Create: `resources/views/admin/users/create.blade.php`
- Create: `resources/views/admin/users/edit.blade.php`
- Create: `resources/views/admin/categories/index.blade.php`
- Create: `resources/views/admin/categories/create.blade.php`
- Create: `resources/views/admin/categories/edit.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/UserManagementTest.php`
- Test: `tests/Feature/Admin/CategoryManagementTest.php`

**Interfaces:**
- Consumes: `role:admin` middleware, models/enums, workspace layout.
- Produces: `admin.users.index|create|store|edit|update` and `admin.categories.index|create|store|edit|update|toggle` routes.

- [ ] **Step 1: Write failing user/category management tests**

Assert non-admins receive 403; admins can list/create/update users and create/update/toggle categories; new users receive validated unique emails and hashed passwords; referenced categories cannot be destructively removed and inactive categories disappear from new-ticket choices.

- [ ] **Step 2: Add last-admin safeguard tests**

With one active admin, submit crafted self-demotion and self-deactivation requests and assert validation failure plus unchanged role/active state. Add a second active admin and assert a permitted change succeeds.

- [ ] **Step 3: Run focused tests and verify failure**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Admin/CategoryManagementTest.php`

Expected: FAIL because admin management is missing.

- [ ] **Step 4: Implement requests, controllers, routes, and views**

Calculate the active-admin safeguard inside the validated update transaction, not only in the UI. Provide enable/disable actions and professional empty/flash states.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Admin/CategoryManagementTest.php`

Expected: all focused tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin app/Http/Requests/Admin resources/views/admin routes/web.php tests/Feature/Admin
git commit -m "feat: add admin user and category management"
```

### Task 9: Demo Seeders and Development Configuration

**Files:**
- Create: `database/seeders/CategorySeeder.php`
- Create: `database/seeders/DemoUserSeeder.php`
- Create: `database/seeders/DemoTicketSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `.env.example`
- Modify: `.gitignore`
- Test: `tests/Feature/Database/DemoSeederTest.php`

**Interfaces:**
- Consumes: all domain models/enums/factories.
- Produces: deterministic demo accounts `admin@example.com`, `staff@example.com`, and `user@example.com`, each with development password `password`; six named categories and realistic ticket/comment/activity coverage.

- [ ] **Step 1: Write the failing seeder test**

Assert exact account emails/roles, hashed passwords, categories, every status/priority represented, assigned/unassigned tickets, and comments/activities attached to authorized actors.

- [ ] **Step 2: Run the seeder test and verify failure**

Run: `php artisan test tests/Feature/Database/DemoSeederTest.php`

Expected: FAIL because seeders are missing.

- [ ] **Step 3: Implement deterministic seeders and MySQL example configuration**

Set `.env.example` to generic MySQL placeholders, keep mail on `log`, exclude runtime private uploads and `.superpowers/`, and never write real credentials.

- [ ] **Step 4: Run fresh seed and focused test**

Run: `php artisan migrate:fresh --seed --env=testing`

Run: `php artisan test tests/Feature/Database/DemoSeederTest.php`

Expected: seed succeeds and test PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders .env.example .gitignore tests/Feature/Database/DemoSeederTest.php
git commit -m "feat: add realistic helpdesk demo data"
```

### Task 10: README, Full Verification, and Portfolio Readiness

**Files:**
- Modify: `README.md`
- Modify: `tests/Feature/ExampleTest.php` or remove it if fully superseded
- Modify: `tests/Unit/ExampleTest.php` or remove it if fully superseded
- Create: `docs/testing/manual-test-checklist.md`

**Interfaces:**
- Consumes: completed application and commands from Tasks 1-9.
- Produces: documented installation/testing/demo workflow and final verification evidence.

- [ ] **Step 1: Write the README and manual test checklist**

Document description, features, a screenshots section ready for final images, stack, requirements, clone/install commands, MySQL setup, key generation, migrations/seeding, Vite, serving, demo accounts, structure, security, tests, future improvements, and an author section without inventing identity or private credentials.

- [ ] **Step 2: Run formatting and asset build**

Run: `vendor/bin/pint --test`

Run: `npm run build`

Expected: formatting check and Vite build succeed.

- [ ] **Step 3: Run the complete automated suite**

Run: `php artisan config:clear`

Run: `php artisan route:list`

Run: `php artisan test`

Expected: no duplicate/broken routes and all tests PASS.

- [ ] **Step 4: Verify the MySQL migration/seed path**

Run after the local `.env` contains the user's MySQL connection: `php artisan migrate:fresh --seed`

Expected: all migrations and deterministic seeders complete successfully. Never overwrite a non-development database; inspect `APP_ENV` and database name first.

- [ ] **Step 5: Perform manual role and responsive checks**

Follow `docs/testing/manual-test-checklist.md` for registration/login, user ownership, staff claiming/handling, admin assignment/management, comments, files, filters, password reset log, empty states, dark theme, keyboard focus, and widths 320, 375, 425, 768, 1024, and 1440 pixels.

- [ ] **Step 6: Review repository hygiene**

Run: `git status --short`

Run: `git ls-files .env storage/app/private vendor node_modules public/build`

Expected: `.env`, private uploads, dependencies, and generated assets are not tracked unless intentionally documented.

- [ ] **Step 7: Commit**

```bash
git add README.md docs/testing tests
git commit -m "docs: finalize helpdesk portfolio project"
```

