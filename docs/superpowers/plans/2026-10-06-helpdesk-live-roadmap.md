# HelpDesk Live System Delivery Roadmap

**Spec:** `docs/superpowers/specs/2026-10-06-helpdesk-live-system-redesign.md`

The approved specification contains six independently reviewable subsystems. Each plan must leave the application working, preserve earlier tests, and pass its own release gate before the next plan starts.

1. `2026-10-06-helpdesk-auth-customer-portal.md`
   - Login-first routing, role destinations, database-session hardening, remembered login, logout behavior, session management, password changes, and the real green customer dashboard.
2. [`2026-10-06-helpdesk-workspace-tickets.md`](2026-10-06-helpdesk-workspace-tickets.md)
   - Faithful admin/staff shell, database-backed dashboard metrics, weekly chart, recent activity, staff queue, responsive filters, and ticket drawer.
3. `2026-10-06-helpdesk-account-category-management.md`
   - Customer accounts, team roles/status, last-admin protection, and category lifecycle.
4. `2026-10-06-helpdesk-knowledge-feedback.md`
   - Knowledge-base drafts/publishing/search, internal notes, first-response tracking, and customer satisfaction.
5. `2026-10-06-helpdesk-reports-settings.md`
   - Date-filtered reports, safe streaming CSV, real response/resolution/CSAT metrics, and cached application settings.
6. `2026-10-06-helpdesk-production-release.md`
   - Cross-role visual QA, full regression, production configuration, deployment, backups/restores, operations, documentation, and release review.

## Dependency Order

Plans execute in the numbered order. Plan 2 consumes role redirects and customer routing from Plan 1. Plan 4 supplies first-response and CSAT data consumed by Plan 5. Plan 6 verifies every earlier interface and must not replace missing product behavior with deployment configuration.

## Repository Constraint

The user deferred repository operations. Plans use evidence checkpoints without commits until the user explicitly re-enables Git commits or branch integration.
