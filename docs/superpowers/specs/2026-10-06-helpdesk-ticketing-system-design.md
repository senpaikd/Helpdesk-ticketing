# HelpDesk Ticketing System Design

## Purpose

HelpDesk is a portfolio project that demonstrates practical junior-level Laravel development through a realistic internal IT support application for a small company. The finished project must be professional enough for portfolio screenshots while remaining organized, readable, and easy to explain.

The application will use Laravel 12.69.3, PHP 8.2, MySQL, Blade, Tailwind CSS 4, and Vite. It will follow conventional Laravel MVC patterns without introducing a JavaScript framework or unnecessary infrastructure.

## Goals

- Demonstrate authentication, authorization, MVC, CRUD, validation, database relationships, file uploads, responsive UI, testing, and basic application security.
- Support three clearly separated roles: admin, support staff, and user.
- Provide realistic seeded data so every dashboard and key workflow is immediately visible.
- Keep controllers and views focused, use Laravel conventions, and avoid overengineering.
- Preserve the HelpDesk product name. The supplied Deskly HTML is a visual reference only and does not define branding, data, or feature scope.

## Roles and Permissions

### User

A publicly registered account always receives the `user` role. A user can:

- View the user dashboard.
- Create tickets and view only tickets they created.
- Add comments and authorized attachments to their tickets.
- Review status, assignment, attachments, conversation, and activity history.
- Close their own resolved tickets.
- Update basic profile information.

A user cannot assign tickets, change priority or operational status, access another user's ticket, or access staff/admin routes.

### Support Staff

Support staff can:

- View their assigned tickets and all unassigned tickets.
- Claim an unassigned ticket.
- Add comments and attachments.
- Change operational status and resolve a ticket.
- Search and filter the tickets available to them.

Once a ticket is assigned, other staff members cannot access it. Admins retain access to every ticket.

### Admin

Admins can:

- View all tickets and system statistics.
- Assign or reassign tickets to active support staff.
- Change ticket status and priority.
- Comment, resolve, close, and reopen tickets.
- Manage users, roles, active state, and categories.
- Search and filter across all tickets.
- Review complete ticket activity history.

An admin cannot remove their own admin role or deactivate themselves when they are the only active administrator.

## Architecture

The application will use server-rendered Blade views and Laravel's built-in session authentication facilities. Login, registration, logout, password-reset requests, and password resets will use Laravel authentication and password-broker services without Laravel Breeze, Livewire, or an SPA framework.

Core units:

- Models: `User`, `Ticket`, `Category`, `TicketComment`, `TicketAttachment`, and `TicketActivity`.
- Enums: `UserRole`, `TicketStatus`, and `TicketPriority`.
- Controllers: focused authentication, dashboard, ticket, comment, attachment, profile, and admin controllers.
- Form Requests: validation and authorization for each write operation.
- Policies: record-level rules for ticket viewing and mutation.
- Middleware: authentication, active-account checks, and role-restricted route groups.
- Services/actions: ticket-number generation, activity recording, attachment storage, and status transition handling where this keeps controllers small.
- Blade components: shared layout, navigation, form controls, alerts, badges, empty states, pagination, and responsive ticket presentation.

Controllers will coordinate requests, models will describe data and relationships, policies will own authorization decisions, and focused actions will own multi-record business operations. Important writes will run inside database transactions.

## Data Model

### Users

The existing users table will gain:

- `role`: `admin`, `staff`, or `user`.
- `is_active`: controls whether an account may sign in or perform authenticated actions.

Relationships include created tickets, assigned tickets, comments, attachments, and activities.

### Categories

Categories contain a unique name, optional description, and active state. A referenced category is disabled instead of deleted. Only active categories appear when creating a ticket.

### Tickets

A ticket contains:

- A unique public ticket number such as `TKT-2026-00001`.
- Creator, optional assignee, and category relationships.
- Subject and description.
- Enum-backed priority and status string values.
- Resolution and closure timestamps.
- Standard timestamps.

The public reference is generated after insertion from the creation year and the database ID padded to at least five digits, for example `TKT-2026-00001`. This avoids race-prone "latest ticket plus one" queries while keeping the reference unique and readable. The reference is used throughout the interface and for route-model binding. Internal database IDs are not presented as the ticket reference.

### Comments, Attachments, and Activities

Comments record the ticket, author, message, and timestamps.

Attachments record the ticket, uploader, original filename, generated storage path, validated MIME type, size, and timestamps. Allowed file types are JPG, JPEG, PNG, PDF, TXT, DOC, and DOCX with a five-megabyte limit per file. Executable and unrecognized uploads are rejected.

Activities record the ticket, optional actor, action type, old value, new value, and timestamp. Display text is derived consistently from the action data. Activities include creation, claiming, assignment, status and priority changes, resolution, closure, reopening, comments, and attachments where useful.

## Ticket Lifecycle

The standard workflow is:

`Open -> In Progress or Pending -> Resolved -> Closed`

- New tickets begin as open.
- Staff or admins may move available tickets among open, in-progress, and pending states and may resolve them.
- The ticket owner or an admin may close a resolved ticket.
- Closed tickets are read-only.
- Only an admin may reopen a closed ticket, returning it to open.
- Invalid transitions are rejected in both the user interface and server-side authorization/business rules.

Ticket creation validates the request, creates the ticket, generates the public number, stores an optional attachment, records activity, and commits as one transaction. A failure rolls back the complete operation.

## Routes and Access Control

Guest routes provide login, registration, forgot-password, and reset-password flows.

Authenticated routes provide role-aware dashboard routing, profile management, ticket listing, creation, details, comments, attachments, and permitted ticket actions.

Admin routes use an `/admin` prefix and role middleware for user and category management. Staff operations use authenticated routes plus ticket policies rather than relying on hidden navigation.

Policies enforce creator, assignee, unassigned-ticket, and admin rules for every ticket action. Blade conditionals improve the interface but are never the security boundary.

## Interface Design

The supplied HTML files define visual direction only. They do not define the product name, business data, or feature scope. Both role experiences remain branded **HelpDesk** and operate on the same Laravel records and authorization rules.

### Admin and Staff Experience

The admin reference informs a workspace-oriented interface with:

- A dark indigo responsive sidebar and a light content canvas.
- Indigo primary actions, restrained shadows, rounded cards, compact tables, and clear badges.
- Inter typography and consistent spacing.
- A collapsible mobile navigation pattern.
- Light and dark themes with accessible contrast.

Role-aware navigation includes Dashboard, Tickets, Create Ticket, Users for admins, Categories for admins, Profile, and Logout.

Admin dashboards show ticket totals by status, total users, support staff, a simple status chart, and recent activity. Staff dashboards use the same visual system but focus on assigned and unassigned workload. The supplied reference's Customers, Knowledge Base, Reports, Settings, CSAT, and response-time content will not be added unless supported by the approved requirements and real application data.

### User Experience

The replacement customer-support portal reference informs a welcoming, responsive web experience with:

- A sticky top navigation, wide welcome area, clear action cards, and a balanced desktop content width.
- Personal ticket summaries, recent requests, readable progress, and conversation-oriented ticket details.
- A focused ticket-creation form that captures category, subject, description, priority, and an optional attachment while preserving server-side validation.
- A two-column ticket-detail layout on larger screens that places the conversation beside ticket metadata and collapses cleanly on mobile.
- The same light/dark theme behavior and accessibility standards as the staff/admin interface.

The user portal uses a calm nature palette instead of the reference's indigo/pink branding:

- Primary forest green: `#2F7D4A`.
- Deep hover green: `#25663C`.
- Soft sage page background: `#F2F7F3`.
- Pale green interactive highlight: `#E2F1E6`.
- Dark green text: `#173023`.
- Muted green-gray text: `#65786C`.
- Dark-mode page background: `#0D1F16`.
- Dark-mode card surface: `#142B1E`.
- Dark-mode accent green: `#68C985`.

The reference's knowledge-base search and article sections are replaced with approved HelpDesk functionality: ticket search, status summaries, recent tickets, and prominent ticket-creation actions. Billing, feature-request, response-time, support-hours, and knowledge-base claims are not added without supporting requirements and real application data.

### Shared Role Data

The role interfaces are different presentations of one system:

- User requests and admin/staff tickets are the same `tickets` records.
- User and staff messages are the same authorized `ticket_comments` records.
- Category choices come from admin-managed active categories.
- Progress indicators and admin status controls use the same `TicketStatus` enum.
- Assigned-agent information comes from the ticket's `assigned_to` relationship.
- Attachments and activities use the same secured records and policies.
- Admin/staff changes appear in the user's ticket view, and user replies appear in the staff/admin workflow.

The ticket-detail experience is a full page rather than only an admin slide-over drawer. It provides enough room for metadata, description, secure attachment downloads, conversation, activity history, and role-authorized actions. Desktop tables use responsive wrappers; narrow screens use readable ticket cards so the page does not create horizontal viewport overflow.

All screens include useful empty states, validation feedback, and flash messages. Controls remain available without hover and use appropriate labels and focus behavior.

## Search, Filters, and Pagination

Ticket lists support search by public ticket number or subject and filters for status, priority, and category. Admins can also filter by assignee. Filters are represented in query parameters, remain applied while paginating, and operate only within the current user's authorized ticket scope.

## Attachment Security

Attachments are stored with generated filenames on a private local disk path rather than a directly public URL. Downloads pass through an authenticated controller that authorizes access to the parent ticket before returning the file.

Validation checks extension, MIME type, and size. Blade uses escaped output for user-provided text. Filenames are shown for usability but never trusted as storage paths.

## Validation and Error Handling

Form Requests provide field validation and friendly messages for authentication, tickets, comments, attachments, profiles, users, and categories. Multi-record operations use transactions. Expected authorization and validation failures return normal Laravel responses and do not leak internal details.

Inactive users cannot continue using protected functionality. Missing files produce a controlled not-found response. Failed storage operations leave no committed attachment record.

## Seed Data

Development seeders provide:

- `admin@example.com`, `staff@example.com`, and `user@example.com`.
- The development-only password `password`, clearly documented as unsuitable for production.
- Hardware, Software, Network, Account Access, Email, and Other categories.
- Realistic tickets across all statuses and priorities.
- Assigned and unassigned tickets, comments, and activity records.

The seeded application must look populated immediately after `php artisan migrate:fresh --seed`.

## Testing Strategy

Feature tests cover:

- Registration always creating a normal user.
- Login, logout, password-reset requests, and inactive-account handling.
- Role middleware and admin-only routes.
- Ticket ownership, assignment, claiming, and staff isolation.
- Ticket creation and unique public numbers.
- Allowed and rejected status transitions.
- Comment and attachment authorization.
- Accepted and rejected upload types and sizes.
- Search, filtering, pagination, and authorized query scoping.
- Admin user and category management safeguards.
- Dashboard statistics.

Unit tests are added only where isolated enum, transition, or ticket-number behavior benefits from them. Tests may use SQLite in memory for speed while migrations remain MySQL-compatible. Before completion, the full migration and seed flow must also be verified against the configured MySQL development database.

## Documentation and Repository Hygiene

The README will contain project purpose, features, screenshots, stack, requirements, installation, MySQL setup, asset compilation, storage behavior, demo accounts, structure overview, security notes, testing instructions, future improvements, and author details.

`.env` remains ignored, `.env.example` stays generic, and secrets are never committed. Runtime uploads, dependencies, compiled caches, and local-only brainstorming artifacts are excluded where appropriate.

## Implementation Sequence

Implementation follows the requested phases:

1. Project setup and verified MySQL configuration.
2. Migrations, enums, models, relationships, factories, and seeders.
3. Authentication, active-account enforcement, roles, middleware, policies, and route protection.
4. Ticket creation, listing, details, assignment, filters, pagination, and status workflow.
5. Comments, private attachments, and activity history.
6. Role dashboards, admin user management, and category management.
7. Responsive Tailwind interface, navigation, validation, messages, badges, empty states, and themes.
8. Automated and manual workflow verification.
9. README and final repository hygiene review.

Each major phase will be reported with what changed, relevant files, commands to run, and manual verification steps.

## Completion Criteria

The project is complete only when migrations and seeders succeed, the application and compiled assets load, authentication works, authorization boundaries are verified, all three roles can perform their intended workflows, uploads are protected, filters and pagination work, dashboards show correct data, routes and Blade views are intact, responsive layouts remain usable from 320px through desktop sizes, critical tests pass, and no secrets or development credentials are committed as production configuration.
