# HelpDesk Account and Category Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the missing User Management and Categories destinations with complete, admin-only, database-backed account and category lifecycle tools.

**Architecture:** Admin routes live behind the existing authenticated, active-account, and `role:admin` middleware. Focused form requests own validation; an `AccountManager` service performs role/status changes under a database lock so concurrent requests cannot remove the last active administrator. Controllers render the existing HelpDesk workspace shell and use Eloquent counts/history rather than placeholder records.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, Tailwind CSS 4, MySQL, Laravel password broker/notifications, PHPUnit feature tests

**Spec:** `docs/superpowers/specs/2026-10-06-helpdesk-live-system-redesign.md`

## Global Constraints

- Keep the product name **HelpDesk** and the existing admin workspace visual language.
- Every displayed count, account, ticket, category, and state must come from the database.
- Only active administrators may access these routes; hidden navigation is not an authorization boundary.
- Customers and staff cannot manage roles, account state, categories, or password reset actions.
- No account is hard-deleted; ticket history remains auditable.
- A role or state change must never leave the system without an active administrator.
- Category names remain unique; category deletion is not exposed. Referenced categories are deactivated, and only active categories are available for new tickets.
- Preserve all existing authentication, portal, workspace, ticket, attachment, session, and activity behavior.
- Repository commits remain deferred by user request; record evidence checkpoints without committing.

## Review Focus

- Two concurrent admin updates must not both pass if together they would leave zero active administrators.
- Crafted requests from staff/customers must receive 403 even when they know the route and target identifier.
- Invalid role, duplicate email/category name, and missing targets must fail without partially changing records.
- Deactivating an account with existing tickets must preserve the account and all ticket relationships.
- Deactivating a category must remove it from ticket creation while preserving existing tickets and historical labels.

---

### Task 1: Add the Admin Account Management Boundary

**Files:**
- Create: `app/Services/AccountManager.php`
- Create: `app/Http/Requests/StoreManagedUserRequest.php`
- Create: `app/Http/Requests/UpdateManagedUserRequest.php`
- Create: `app/Http/Controllers/Admin/UserManagementController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/UserManagementTest.php`

**Interfaces:**
- Consumes: `UserRole`, `User`, database transactions, existing `role:admin` middleware.
- Produces: named routes `admin.users.index`, `admin.users.show`, `admin.users.store`, and `admin.users.update`; `AccountManager::create(array): User`; `AccountManager::update(User, array): User`.

- [ ] **Step 1: Write failing route, authorization, validation, and lifecycle tests**

Cover guest redirect, staff/customer 403, admin list/search/role/status filtering, customer ticket counts, customer history detail, creation limited to staff/admin roles, unique email validation, account state/role updates, no hard-delete route, and an inactive target retaining all tickets.

- [ ] **Step 2: Write failing last-active-admin tests**

Prove a lone active administrator cannot deactivate or demote themselves, crafted updates cannot leave zero active admins, and the same changes succeed when another active administrator exists.

- [ ] **Step 3: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php`

Expected: FAIL because the routes, requests, service, and controller do not exist.

- [ ] **Step 4: Implement the account requests and `AccountManager`**

Validate normalized name/email, `UserRole`, and boolean active state. Create only staff/admin accounts with a cryptographically random unusable password. In `update`, lock active admin rows inside a transaction and reject a role/state change that would leave fewer than one active admin.

- [ ] **Step 5: Implement index, detail, create, and update controller actions**

Index supports `search`, `role`, and `status`, paginates 15 accounts, and loads real ticket/open-ticket counts plus latest ticket activity. Detail shows the selected account and paginated created-ticket history. Successful writes redirect with explicit confirmation.

- [ ] **Step 6: Run the focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php`

Expected: PASS.

- [ ] **Step 7: Record the no-commit checkpoint**

Capture changed files and test output in the Plan 3 ledger; do not commit.

### Task 2: Add Team Invitations and Password Reset Delivery

**Files:**
- Modify: `app/Services/AccountManager.php`
- Create: `app/Http/Controllers/Admin/UserPasswordResetController.php`
- Modify: `app/Http/Controllers/Admin/UserManagementController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Admin/UserManagementTest.php`

**Interfaces:**
- Consumes: Laravel `Password` broker and the managed account routes from Task 1.
- Produces: named route `admin.users.password-reset`; a newly created team account receives a password-reset notification; admins can resend it explicitly.

- [ ] **Step 1: Write failing notification tests**

Use notification fakes to prove team creation sends a reset link to the created account, resending works, unknown/inactive authorization boundaries hold, and no password/token appears in the response.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php`

Expected: FAIL because reset delivery is not wired to managed accounts.

- [ ] **Step 3: Implement password-reset delivery**

After a successful staff/admin creation, call the password broker for that exact account email. Add an admin-only resend action that reports success or a safe delivery failure without exposing tokens.

- [ ] **Step 4: Run the focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php`

Expected: PASS.

- [ ] **Step 5: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 3: Implement the Category Lifecycle

**Files:**
- Create: `app/Http/Requests/StoreCategoryRequest.php`
- Create: `app/Http/Requests/UpdateCategoryRequest.php`
- Create: `app/Http/Controllers/Admin/CategoryController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/CategoryManagementTest.php`

**Interfaces:**
- Consumes: existing `Category`, `Category::tickets()`, and admin middleware.
- Produces: named routes `admin.categories.index`, `admin.categories.store`, and `admin.categories.update`.

- [ ] **Step 1: Write failing authorization and lifecycle tests**

Cover guest redirect, non-admin 403, real ticket counts, create, rename, description, activate/deactivate, case-insensitive unique name handling, validation rollback, no delete route, referenced-category preservation, and active-only ticket creation choices.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/Admin/CategoryManagementTest.php`

Expected: FAIL because category management routes do not exist.

- [ ] **Step 3: Implement category requests and controller**

Normalize names/descriptions, validate length/uniqueness/boolean state, paginate categories with real ticket counts, and update records without detaching or deleting historical tickets.

- [ ] **Step 4: Run the focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Admin/CategoryManagementTest.php tests/Feature/Tickets/TicketCrudTest.php`

Expected: PASS.

- [ ] **Step 5: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 4: Build the Workspace Management Screens and Navigation

**Files:**
- Create: `resources/views/admin/users/index.blade.php`
- Create: `resources/views/admin/users/show.blade.php`
- Create: `resources/views/admin/categories/index.blade.php`
- Modify: `resources/views/layouts/workspace.blade.php`
- Modify: `resources/css/app.css`
- Modify: `tests/Feature/Views/LayoutSmokeTest.php`
- Modify: `tests/Feature/Admin/UserManagementTest.php`
- Modify: `tests/Feature/Admin/CategoryManagementTest.php`

**Interfaces:**
- Consumes: Task 1–3 route names, form data, validation errors, pagination, and flash status.
- Produces: working admin-only sidebar destinations labelled `User Management` and `Categories`; responsive account/category tables and management forms.

- [ ] **Step 1: Write failing view and navigation assertions**

Assert admin navigation contains both working destinations and their active states, staff navigation omits them, every displayed value is database-derived, forms preserve old input/errors, and empty states are truthful.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php tests/Feature/Admin/UserManagementTest.php tests/Feature/Admin/CategoryManagementTest.php`

Expected: FAIL because the pages and links do not exist.

- [ ] **Step 3: Build responsive management views**

Reuse the dark navy workspace shell, indigo actions, cards, status badges, filters, and mobile-safe tables. User Management includes create-team form, account update controls, reset-link action, counts, and account detail/history. Categories includes create and inline edit/state controls with ticket counts.

- [ ] **Step 4: Add admin-only navigation and page titles**

Render links only for `UserRole::Admin`; use Phosphor icons and named-route active states. Update the compact top-bar title for both modules.

- [ ] **Step 5: Run focused tests and verify GREEN**

Run: `php artisan test tests/Feature/Views/LayoutSmokeTest.php tests/Feature/Admin/UserManagementTest.php tests/Feature/Admin/CategoryManagementTest.php`

Expected: PASS.

- [ ] **Step 6: Record the no-commit checkpoint**

Capture changed files and test output; do not commit.

### Task 5: Regression, Build, and Browser Release Gate

**Files:**
- Modify: `design-qa.md`
- Modify: `README.md`
- Modify: Plan 3 tests only if verification exposes an incorrect expectation

**Interfaces:**
- Consumes: every Plan 3 route, service, request, controller, view, and earlier system behavior.
- Produces: a verified management stage with no 404 navigation and no development-data loss.

- [ ] **Step 1: Run task-scoped Pint**

Run Pint in test mode over all Plan 3 PHP files; format only those files if needed and rerun.

- [ ] **Step 2: Run the complete test suite and production build**

Run: `php artisan test` and `npm run build`

Expected: all tests pass and Vite emits a production manifest.

- [ ] **Step 3: Verify routes, migrations, and preserved development data**

Run route and migration listings plus read-only counts. Confirm User Management and Categories routes exist, all prior migrations remain Ran, and the current users, six categories, and `TKT-2026-00006` remain.

- [ ] **Step 4: Run desktop and mobile browser QA**

At 1440×900 and 430×900, verify admin list/filter/detail/create/update/reset flows, last-admin validation, category create/update/state flows, navigation/active states, dark/light theme, responsive tables, logout/session behavior, and zero console errors. Use disposable records only in the test database; do not create example production records during visual QA.

- [ ] **Step 5: Update QA and operations documentation**

Record exact browser/test/build evidence and clearly state that account/category management is live while later knowledge, feedback, reporting, settings, and release stages remain pending.

- [ ] **Step 6: Record the plan completion checkpoint**

Report exact evidence and remaining Plan 4 boundary; do not commit.
