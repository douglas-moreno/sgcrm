# Project Phases — sgCrm

Implementation roadmap for **sgCrm**, derived from `docs/project_description.md`, `docs/user_stories.md`, and `docs/database_schema.md`.

Conventions:
- **Phase X.Y** numbering — agents may reference any task by its number (e.g. "implement Phase 7.3").
- Each task lists the **automated feature tests** to be generated as its acceptance criteria. Use Pest 4. All tests live under `tests/Feature/` unless noted.
- `[x]` = completed; `[ ]` = pending. Completion checked against the current codebase.
- Drag-and-drop on the Kanban **must** use Livewire 4's `wire:sort`. No external JS DnD libraries.
- All commands run inside Sail: `vendor/bin/sail …`.

Legend for test placement:
- `tests/Feature/Auth/` · authentication flows
- `tests/Feature/Companies/` · tenancy, invites, user mgmt
- `tests/Feature/Kanban/` · board, drag-drop, filters
- `tests/Feature/Leads/` · lead CRUD + uniqueness
- `tests/Feature/Deals/` · deal CRUD, notes, activity
- `tests/Feature/Whatsapp/` · Evolution integration
- `tests/Feature/Reports/` · dashboards
- `tests/Feature/Authorization/` · RBAC + tenant isolation
- `tests/Browser/` · Pest 4 browser tests for critical UI flows
- `tests/Unit/` · pure unit tests (services, calculators, policies in isolation)

---

## Phase 1 — Foundation & Tooling

### 1.1 Base Laravel 13 install
- [x] Laravel 13 skeleton present
- [x] Composer dependencies declared (`laravel/framework`, `livewire/livewire ^4`, `wireui/wireui ^2.6`, Pest 4, Pint, Pail, Sail)

### 1.2 Sail / Docker environment
- [x] `compose.yaml` configured
- [ ] Verify Sail boots cleanly with MySQL 8 and Redis (if used). Document in README.
- **Tests:** none (infra) — replace with a `tests/Feature/SmokeTest.php` asserting `GET /` returns 200.

### 1.3 Pest 4 setup
- [x] Pest installed
- [ ] Configure `tests/Pest.php` for `RefreshDatabase`, factory faker locale, base test case bindings.
- **Tests:** `tests/Unit/PestSetupTest.php` — confirms `RefreshDatabase` trait wired and `fake()` works.

### 1.4 Pint formatter
- [x] Pint installed
- [ ] Add `pint.json` if project deviates from defaults; add a CI/local script reminder.

### 1.5 Environment variables
- [ ] `.env.example` extended with: `EVOLUTION_API_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_WEBHOOK_SECRET`, `MAIL_*`, `APP_URL`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`.
- **Tests:** `tests/Feature/ConfigTest.php` — asserts required config keys resolve when env present.

### 1.6 Application service providers
- [ ] Register a `DomainServiceProvider` for binding domain services (Evolution client, activity recorder).
- **Tests:** `tests/Unit/ServiceProviderBindingTest.php` — resolves each binding from container.

---

## Phase 2 — Frontend Foundation & Design System

Reference assets: `docs/design/padrao_de_cores.png`, `docs/design/botoes.png`, `docs/design/checkbox.png`, `docs/design/formularios.png`, `docs/design/dashboard.png`, `docs/design/kanban.png`, `docs/design/whatsapp.png`, `docs/design/layout_base_login.png`.

### 2.1 Tailwind v4 + Vite pipeline
- [x] Vite + Tailwind installed
- [ ] Configure Tailwind v4 with project palette extracted from `padrao_de_cores.png`.
- [ ] Define CSS custom properties for primary/secondary/success/warning/danger/neutral.
- **Tests:** `tests/Browser/StyleSmokeTest.php` — load `/login`, assert primary color tokens applied.

### 2.2 Color tokens
- [ ] Implement design palette as Tailwind theme tokens (`primary`, `accent`, `surface`, `border`, `text-muted`, `text-strong`).
- [ ] Document tokens inline in `resources/css/app.css`.

### 2.3 Typography
- [ ] Pick + load font matching the design (Inter or similar). Define heading/body scales.
- [ ] Set base line-height, weights.

### 2.4 Base components (Wireui-backed where available)
For each: build Blade component under `resources/views/components/ui/`, configure Wireui where applicable.
- [ ] **2.4.1 Button** (variants: primary, secondary, ghost, danger; sizes sm/md/lg; loading state)
- [ ] **2.4.2 Input** (text, email, password, number; with label, hint, error)
- [ ] **2.4.3 Textarea**
- [ ] **2.4.4 Select** (Wireui native select)
- [ ] **2.4.5 Checkbox**
- [ ] **2.4.6 Radio**
- [ ] **2.4.7 Modal** (Wireui modal, with header/body/footer slots)
- [ ] **2.4.8 Toast / Notification** (Wireui notifications)
- [ ] **2.4.9 Card** (used for Kanban deal cards and detail panels)
- [ ] **2.4.10 Badge / Pill** (status chips)
- [ ] **2.4.11 Avatar** (initials fallback + image)
- **Tests:** `tests/Browser/Components/UiComponentsTest.php` — render each component with sample props, assert visible, no JS errors.

### 2.5 Icon set
- [ ] Pick icon library compatible with Wireui (Heroicons). Wire it up.

### 2.6 Component preview page (dev-only)
- [ ] Route `/dev/ui` (gated by `APP_ENV=local`) showing all components for visual QA.
- **Tests:** `tests/Feature/Dev/UiPreviewTest.php` — route returns 200 in `local`, 404 in `production`.

---

## Phase 3 — Layout Bases

### 3.1 Guest layout (non-logged)
Reference: `docs/design/layout_base_login.png`.
- [ ] `resources/views/layouts/guest.blade.php` — centered card, brand logo, footer.
- [ ] Mobile-first responsive rules.
- **Tests:** `tests/Browser/Layouts/GuestLayoutTest.php` — visit `/login`, assert layout structure + brand visible.

### 3.2 App layout (logged-in)
Reference: `docs/design/dashboard.png`.
- [ ] `resources/views/layouts/app.blade.php` — sidebar nav, top bar, user menu, mobile drawer.
- [ ] Sidebar items conditional on role: Kanban, Leads, Reports (Owner only), Settings.
- [ ] Mobile bottom nav or hamburger.
- **Tests:** `tests/Browser/Layouts/AppLayoutTest.php` — login as Owner / Salesperson, assert nav items differ; toggle mobile menu.

### 3.3 Layout primitives
- [ ] Page header component (title + actions slot).
- [ ] Empty-state component.
- [ ] Loading skeleton component.

---

## Phase 4 — Database Migrations, Models, Factories, Seeders

Implements `docs/database_schema.md`.

### 4.1 Lookup tables migrations
- [ ] **4.1.1** `roles`
- [ ] **4.1.2** `pipeline_stages`
- [ ] **4.1.3** `invite_statuses`
- [ ] **4.1.4** `whatsapp_connection_statuses`
- [ ] **4.1.5** `message_directions`
- [ ] **4.1.6** `message_statuses`
- [ ] **4.1.7** `message_types`
- [ ] **4.1.8** `activity_types`

### 4.2 Core domain migrations
- [ ] **4.2.1** `companies`
- [ ] **4.2.2** Modify `users` — add `company_id`, `role_id`, `avatar_path`, `is_active`, `must_change_password`, `last_login_at`, `deleted_at`
- [ ] **4.2.3** `invites`
- [ ] **4.2.4** `leads` (with composite unique `(company_id, email)`)
- [ ] **4.2.5** `deals`
- [ ] **4.2.6** `deal_notes`
- [ ] **4.2.7** `activities`
- [ ] **4.2.8** `whatsapp_connections`
- [ ] **4.2.9** `messages`
- **Tests:** `tests/Feature/Database/SchemaTest.php` — assert each table exists with required columns and indexes (use `Schema::hasColumns`, `Schema::hasIndex`).

### 4.3 Models
For each: Eloquent model with relationships, casts, `$fillable`, soft-deletes where applicable, global `CompanyScope` on tenant tables.
- [ ] Company, Role, User, Invite, InviteStatus
- [ ] PipelineStage, Lead, Deal, DealNote
- [ ] ActivityType, Activity
- [ ] WhatsappConnection, WhatsappConnectionStatus
- [ ] MessageDirection, MessageStatus, MessageType, Message
- **Tests:** `tests/Unit/Models/RelationshipsTest.php` — assert each relationship method returns correct relation type and target.

### 4.4 Factories
- [ ] Factories for every domain model. States: `Lead::factory()->forCompany($c)`, `Deal::factory()->inStage('lost')->withLossReason()`, `User::factory()->businessOwner()`, `User::factory()->salesperson()`, etc.
- **Tests:** `tests/Unit/Factories/FactoriesTest.php` — each factory builds a persistable model.

### 4.5 Seeders
- [ ] **4.5.1** `RoleSeeder` — business_owner, salesperson
- [ ] **4.5.2** `PipelineStageSeeder` — new_lead, contacted, proposal_sent, negotiation, won, lost (with flags + positions)
- [ ] **4.5.3** `InviteStatusSeeder`
- [ ] **4.5.4** `WhatsappConnectionStatusSeeder`
- [ ] **4.5.5** `MessageDirectionSeeder`, `MessageStatusSeeder`, `MessageTypeSeeder`
- [ ] **4.5.6** `ActivityTypeSeeder`
- [ ] **4.5.7** `DatabaseSeeder` orchestration + a `DemoDataSeeder` for local dev only
- **Tests:** `tests/Feature/Database/LookupSeedTest.php` — after `db:seed`, assert each lookup contains expected slugs.

### 4.6 CompanyScope global scope
- [ ] Trait `BelongsToCompany` applying global scope to filter by `auth()->user()->company_id`.
- [ ] Auto-fill `company_id` on save via observer.
- **Tests:** `tests/Feature/Authorization/TenantScopeTest.php` — user from Company A cannot read records of Company B even via direct query.

---

## Phase 5 — Authentication & Registration

### 5.1 Auth scaffolding
- [ ] Build manual auth (no Breeze/Jetstream) using Livewire components: `Auth\Login`, `Auth\Register`, `Auth\ForgotPassword`, `Auth\ResetPassword`.
- [ ] Routes in `routes/web.php` under guest middleware group.
- **Tests:** `tests/Feature/Auth/LoginTest.php`, `RegisterTest.php`, `LogoutTest.php`, `PasswordResetTest.php`.

### 5.2 Business Owner registration (US-1.1)
- [ ] Form: name, email, password, password_confirmation, company_name.
- [ ] In transaction: create Company → create User with role=business_owner → log in → redirect to Kanban.
- [ ] Send welcome email (queued).
- **Tests:** `tests/Feature/Auth/BusinessOwnerRegistrationTest.php`
  - registers successfully and creates Company + User
  - rejects duplicate email globally
  - validates password rules (min 8)
  - logs in and redirects to `/kanban`
  - dispatches `WelcomeMail`

### 5.3 Login (US-1.2)
- [ ] Livewire login form with rate-limiting via `RateLimiter`.
- [ ] Generic error on bad credentials.
- [ ] Remember-me toggle.
- **Tests:** `tests/Feature/Auth/LoginTest.php`
  - valid credentials → authenticated + redirect
  - invalid credentials → generic error, no enumeration
  - rate limit triggers after N attempts
  - remember-me sets persistent cookie

### 5.4 Logout (US-1.4)
- [ ] POST `/logout` route, invalidate session, regenerate token.
- **Tests:** `tests/Feature/Auth/LogoutTest.php`
  - logout terminates session
  - CSRF token rotated

### 5.5 Password reset (US-1.3)
- [ ] Forgot-password page → email link (60 min TTL).
- [ ] Reset page validating token.
- [ ] On reset: invalidate other sessions, send confirmation email.
- **Tests:** `tests/Feature/Auth/PasswordResetTest.php`
  - request with unknown email returns generic success (no enumeration)
  - valid token allows reset
  - expired token rejected
  - all sessions invalidated post-reset
  - confirmation email dispatched

### 5.6 Email verification
- [ ] Standard Laravel email verification with branded notification.
- **Tests:** `tests/Feature/Auth/EmailVerificationTest.php` — unverified user blocked from app routes; verifies via signed link.

---

## Phase 6 — Company & User Management

### 6.1 Roles & policies foundation
- [ ] `App\Policies\` for User, Lead, Deal, DealNote, Invite, WhatsappConnection, Message, Activity.
- [ ] Gate definitions or `before` callback granting Business Owner full access within company.
- **Tests:** `tests/Feature/Authorization/PolicyTest.php` — for each policy method, assert allow/deny per role.

### 6.2 Invite via email link (US-2.1)
- [ ] Owner UI to enter name + email, dispatches `SendInviteJob`.
- [ ] Signed token, 7-day expiry.
- [ ] Mail with invite URL.
- [ ] Public route `/invites/{token}` — render acceptance form (set password) + creates Salesperson user.
- [ ] Owner list shows pending/accepted/expired with resend + revoke.
- **Tests:** `tests/Feature/Companies/EmailInviteTest.php`
  - Owner creates invite → mail queued, token persisted
  - Salesperson opens link → form rendered, can set password, account created
  - expired token rejected
  - revoked token rejected
  - resend regenerates token + sends email
  - non-Owner cannot create invite (403)

### 6.3 Direct account creation (US-2.2)
- [ ] Owner form: name, email, temporary password.
- [ ] Creates Salesperson with `must_change_password=true`.
- [ ] Email with login info.
- [ ] On first login, redirected to forced password change before any other action.
- **Tests:** `tests/Feature/Companies/DirectAccountCreationTest.php`
  - account created with role salesperson
  - email dispatched
  - first login forces password change
  - password change clears flag

### 6.4 List & manage Salespeople (US-2.3)
- [ ] Livewire table: name, email, status, date added, actions (deactivate / reactivate).
- [ ] Search by name/email.
- [ ] Owner cannot deactivate self.
- **Tests:** `tests/Feature/Companies/UserManagementTest.php`
  - list shows only company users
  - search filters
  - deactivate blocks login
  - reactivate restores login
  - cannot deactivate self
  - non-Owner forbidden (403)

### 6.5 Reassign lead ownership (US-2.4)
- [ ] Action on lead/deal detail to pick a new Salesperson.
- [ ] Cascades to all deals of the lead.
- [ ] Activity entries written for lead + each deal.
- **Tests:** `tests/Feature/Leads/ReassignLeadTest.php`
  - lead + all deals reassigned
  - activities created with `lead_reassigned` and `ownership_changed`
  - previous owner cannot access lead afterwards
  - only Owner can reassign

---

## Phase 7 — Kanban Pipeline (wire:sort)

### 7.1 Kanban route + Livewire component
- [ ] Route `/kanban` (auth + verified middleware).
- [ ] Livewire component `App\Livewire\Kanban\Board` rendering columns from `pipeline_stages`.
- [ ] Scoped queries via Policy + global scope (Salesperson sees own; Owner sees all).
- **Tests:** `tests/Feature/Kanban/BoardRenderTest.php`
  - Salesperson sees only own deals
  - Owner sees all deals in own company
  - cross-company deals never visible
  - column counts and totals correct

### 7.2 Kanban view (Tailwind + responsive)
- [ ] Reference `docs/design/kanban.png`.
- [ ] Columns horizontally scrollable on mobile, grid on desktop.
- [ ] Card displays: title, value (formatted), lead name, last activity, owner badge (Owner view only).
- **Tests:** `tests/Browser/Kanban/BoardLayoutTest.php` — desktop + mobile viewports render columns and cards.

### 7.3 Drag-and-drop with `wire:sort`
- [ ] **MUST use Livewire 4 `wire:sort` directive.** No external DnD libraries.
- [ ] Backend method `updateStage(int $dealId, string $toStageSlug)` validates ownership + stage transition.
- [ ] If destination = `lost` → return a flag instructing the UI to open the loss-reason modal before persisting; on cancel, revert.
- [ ] If destination = `won` → mark `won_at`, lock further edits except notes.
- [ ] Activity entry `stage_changed` written.
- **Tests:**
  - `tests/Feature/Kanban/MoveDealTest.php`
    - moves deal to allowed stage
    - rejects move on a deal not owned by current Salesperson (403)
    - move to `lost` without reason fails validation
    - move to `lost` with reason persists `loss_reason` + `lost_at`
    - move to `won` sets `won_at`, locks future edits
    - records `stage_changed` activity with from/to stage ids
  - `tests/Browser/Kanban/DragDropTest.php` — drag a card across columns, assert new stage shown after refresh; uses `wire:sort` interactions.

### 7.4 Kanban filter (US-3.3, Owner only)
- [ ] Filter dropdown listing active Salespeople + "All".
- [ ] Filter state in URL query string (`?owner=`).
- **Tests:** `tests/Feature/Kanban/FilterTest.php`
  - filter scopes board to selected Salesperson
  - "All" returns all company deals
  - state persisted in URL
  - non-Owner does not see filter (403 if posted directly)

---

## Phase 8 — Lead Management

### 8.1 Inline lead creation from Kanban (US-4.1)
- [ ] "New Lead" button on board opens Wireui modal.
- [ ] Form: name, email, phone, optional notes.
- [ ] On email blur or full match → real-time lookup against `leads` (scoped per company).
- [ ] If no match → create Lead + auto-create Deal in `new_lead` (Phase 9.1).
- [ ] If match → trigger reuse flow (8.2).
- **Tests:** `tests/Feature/Leads/CreateLeadTest.php`
  - creates lead + deal in single submission
  - rejects duplicate email per company (validation + DB constraint)
  - allows same email in different company
  - lead owner = current user (or selected Salesperson if Owner)
  - `lead_created` activity recorded

### 8.2 Reuse existing lead on duplicate email (US-4.2)
- [ ] Real-time Livewire validation hits `LeadLookupService`.
- [ ] If match found and visible to current user → show match card with reuse / cancel.
- [ ] If match owned by another Salesperson and current user is Salesperson → show "lead exists, contact your manager" notice.
- [ ] On reuse → skip lead creation, jump to deal creation step.
- **Tests:** `tests/Feature/Leads/ReuseLeadTest.php`
  - duplicate email returns existing lead reference
  - Salesperson cannot reuse a lead owned by another Salesperson
  - Owner can reuse any lead in company
  - reuse path creates deal but no second lead

### 8.3 Edit lead (US-4.3)
- [ ] Livewire component for lead detail / edit.
- [ ] Salesperson cannot edit email (Owner-only).
- [ ] Email change re-runs uniqueness validation.
- [ ] `lead_updated` activity entries with field-level before/after.
- **Tests:** `tests/Feature/Leads/EditLeadTest.php`
  - update name / phone / notes succeeds
  - Salesperson cannot edit email
  - duplicate email rejected on update
  - activity entries created per changed field

---

## Phase 9 — Deal Management

### 9.1 Auto-create deal on lead creation (US-5.1)
- [ ] Service `CreateLeadWithDeal` wraps both creations in a transaction.
- [ ] Deal defaults: title = lead name, value = 0, stage = `new_lead`.
- **Tests:** covered in `tests/Feature/Leads/CreateLeadTest.php`; add `tests/Feature/Deals/AutoCreateDealTest.php` asserting service rolls back on failure.

### 9.2 Add additional deal to existing lead (US-5.2)
- [ ] UI: from lead detail or reuse flow, "New Deal" form (title, value).
- [ ] Owner inherited from lead.
- **Tests:** `tests/Feature/Deals/AddDealTest.php`
  - additional deal created for existing lead
  - owner inherited
  - default stage = `new_lead`
  - validation: title required, value ≥ 0

### 9.3 View & edit deal detail (US-5.3)
- [ ] Route `/deals/{deal}`. Livewire `Deals\Show` component.
- [ ] Sections: header (title, value, stage), lead info, owner, notes, activity timeline, chat shortcut.
- [ ] Editable title, value, notes (notes via 9.5).
- [ ] Won deals locked except notes.
- **Tests:** `tests/Feature/Deals/DealDetailTest.php`
  - shows all sections
  - updates title and value (records `value_changed` activity)
  - won deal returns 403 on title/value edit
  - cross-company deal returns 404
  - non-owning Salesperson returns 403

### 9.4 Mark deal as Lost with reason (US-5.4)
- [ ] Modal: required free-text loss reason.
- [ ] Server validation rejects empty.
- [ ] Persist `loss_reason`, `lost_at`, set stage to `lost`.
- [ ] Cancel reverts stage move.
- **Tests:** `tests/Feature/Deals/MarkLostTest.php`
  - empty reason rejected
  - valid reason persisted, `lost_at` set, stage updated
  - `deal_lost` activity recorded with reason in metadata
  - cancel does not change stage

### 9.5 Deal notes (US-5.5)
- [ ] Notes section on detail. Append-only from UI.
- [ ] Author + timestamp shown.
- **Tests:** `tests/Feature/Deals/NotesTest.php`
  - add note succeeds, persisted, `note_added` activity created
  - non-owner Salesperson cannot add note
  - cross-company forbidden

### 9.6 Deal activity history (US-5.6)
- [ ] Livewire timeline reading `activities` filtered by `deal_id`.
- [ ] Pagination, newest-first.
- [ ] Read-only.
- **Tests:** `tests/Feature/Deals/ActivityTimelineTest.php`
  - timeline includes stage moves, value changes, ownership changes, notes, messages, deal creation
  - sorted DESC by created_at
  - paginated
  - cannot edit/delete entries

---

## Phase 10 — WhatsApp Integration (Evolution API v2)

### 10.1 Evolution API client
- [ ] `App\Services\Evolution\EvolutionClient` wrapping HTTP calls (create instance, fetch QR, send message, disconnect).
- [ ] Config in `config/services.php`. URL/key from env.
- [ ] Retry + timeout + signed webhook secret.
- **Tests:** `tests/Unit/Services/EvolutionClientTest.php`
  - uses `Http::fake()` to assert correct endpoints, headers, payloads, retries

### 10.2 Connect WhatsApp via QR code (US-6.1)
- [ ] Settings page section. Livewire component `Whatsapp\ConnectionPanel`.
- [ ] On "Connect": create or reuse Evolution instance for the user; display QR; poll status.
- [ ] On status change: persist `whatsapp_connections` row.
- [ ] Disconnect button.
- **Tests:** `tests/Feature/Whatsapp/ConnectTest.php`
  - "Connect" provisions instance + persists `pending` status
  - QR rendered when API returns one
  - status transitions to `connected` on webhook
  - disconnect clears connection state
  - one connection per Salesperson (DB unique enforced)

### 10.3 Webhook receiver
- [ ] Public route `/webhooks/evolution/{user}` validating signed secret + idempotent on `external_id`.
- [ ] Persists inbound messages, updates statuses, fires `MessageReceived` event.
- **Tests:** `tests/Feature/Whatsapp/WebhookTest.php`
  - rejects invalid signature (401)
  - persists new inbound message
  - duplicate `external_id` ignored (idempotent)
  - associates message with correct lead by phone match
  - creates lead if phone unknown? — out of scope for MVP; webhook drops with log entry (test asserts log)

### 10.4 Open chat from a deal (US-6.2)
- [ ] Action button on deal detail.
- [ ] Disabled if WhatsApp not connected or lead has no phone.
- **Tests:** `tests/Feature/Whatsapp/OpenChatTest.php`
  - button visible only when both conditions met
  - opens chat URL with `deal` and `lead` params

### 10.5 Send & receive messages (US-6.3)
- [ ] Livewire `Whatsapp\Conversation` component bound to a lead + optional deal.
- [ ] Renders message history paginated/oldest-first; auto-scroll on new.
- [ ] `sendMessage()` posts via EvolutionClient, persists `pending` then `sent`.
- [ ] Polling every 5s (or websocket if Reverb later) for new messages.
- [ ] Unread badge on related Kanban cards.
- [ ] All messages logged in deal activity.
- **Tests:** `tests/Feature/Whatsapp/MessagingTest.php`
  - send message persists with `pending` then `sent` after API ack
  - inbound message via webhook appears in conversation
  - unread badge counted on deal card
  - Salesperson cannot view conversation of lead owned by another
  - all message events produce activities

### 10.6 Disconnected state handling (US-6.4)
- [ ] Listener for disconnect webhook updates status; UI banner shown.
- [ ] Send action disabled while disconnected.
- **Tests:** `tests/Feature/Whatsapp/DisconnectedStateTest.php`
  - status flips to `disconnected` on webhook
  - send returns validation error / 422
  - banner present in component output

---

## Phase 11 — Reports & Dashboards (Business Owner)

### 11.1 Reports route + access guard
- [ ] Routes under `/reports/*` with `role:business_owner` middleware.
- **Tests:** `tests/Feature/Reports/AccessTest.php`
  - Salesperson gets 403 on every report route

### 11.2 Pipeline overview report (US-7.1)
- [ ] Per-stage counts and value sums; date-range and Salesperson filters.
- [ ] Chart (Wireui chart or Chart.js).
- **Tests:** `tests/Feature/Reports/PipelineOverviewTest.php`
  - aggregates correct per stage
  - filters apply (date range, salesperson)
  - cross-company data never leaks

### 11.3 Salesperson performance (US-7.2)
- [ ] Per-Salesperson: won, lost, conversion %, total won value, average deal size.
- [ ] Date range, sortable columns.
- **Tests:** `tests/Feature/Reports/SalespersonPerformanceTest.php`
  - metrics computed correctly against seeded data
  - sort works on each column
  - date range filter applied

### 11.4 Loss reason analysis (US-7.3)
- [ ] Aggregates `deals.loss_reason` normalized (lowercase + trim).
- [ ] Frequency-ranked list; click expands into deal list.
- **Tests:** `tests/Feature/Reports/LossReasonReportTest.php`
  - normalizes "Price" / "  price" / "PRICE" into one bucket
  - filter by date and Salesperson
  - drill-down returns matching deals

### 11.5 Activity volume (US-7.4)
- [ ] Counts messages sent/received, notes added, stage moves, per Salesperson and over time.
- [ ] Drill-down to top deals by activity.
- **Tests:** `tests/Feature/Reports/ActivityVolumeTest.php`
  - counts per type accurate
  - per-Salesperson breakdown correct
  - time-series buckets correct

---

## Phase 12 — Authorization & Tenant Isolation (Cross-cutting)

### 12.1 Salesperson scoping (US-8.1)
- [ ] Every query for leads/deals/messages/activities filtered by `owner_user_id = auth user` for Salesperson.
- [ ] Policies enforce action-level checks.
- **Tests:** `tests/Feature/Authorization/SalespersonScopingTest.php`
  - direct URL to non-owned deal/lead/message → 403
  - reuse flow blocks reuse of lead owned by another Salesperson
  - chat denied for non-owned lead

### 12.2 Business Owner full access (US-8.2)
- [ ] Owner bypasses ownership filter within company.
- [ ] All actions logged with actor in activity history.
- **Tests:** `tests/Feature/Authorization/OwnerAccessTest.php`
  - Owner edits deal of any Salesperson
  - Owner reassigns leads
  - actions write activities tagged with Owner user_id

### 12.3 Multi-tenant isolation (US-8.3)
- [ ] `BelongsToCompany` trait + global scope on every domain model.
- [ ] Auto-fill `company_id` on save via observer.
- **Tests:** `tests/Feature/Authorization/TenantIsolationTest.php`
  - User of Company A cannot read/write any record of Company B (covers leads, deals, messages, invites, users, activities)
  - factory cross-company foreign keys rejected at DB or model layer
  - same lead email allowed across different companies

### 12.4 Rate limiting + brute-force protection
- [ ] Login + password reset endpoints rate-limited.
- [ ] Webhook endpoint per-IP throttle.
- **Tests:** `tests/Feature/Auth/RateLimitTest.php` — confirm 429 after threshold.

---

## Phase 13 — Mobile-First Experience

### 13.1 Kanban mobile (US-9.1)
- [ ] Horizontally swipeable columns; sticky column headers.
- [ ] Touch-friendly card targets (≥44px).
- [ ] `wire:sort` works on touch.
- **Tests:** `tests/Browser/Kanban/MobileKanbanTest.php`
  - viewport 360px and 414px renders correctly
  - card move via touch persists stage

### 13.2 Forms mobile
- [ ] Lead modal, deal edit, loss reason modal — full-screen on mobile.
- [ ] Auto-focus first field; correct input types/keyboards (`type=email`, `inputmode=tel`).

### 13.3 Chat mobile
- [ ] One-handed usable. Composer fixed bottom. Auto-scroll on new message.
- **Tests:** `tests/Browser/Whatsapp/MobileConversationTest.php`

### 13.4 Cross-viewport regression
- **Tests:** `tests/Browser/Smoke/ViewportSmokeTest.php` — visit `/login`, `/kanban`, `/deals/{id}`, `/settings/whatsapp` at 360, 414, 768, 1024, 1440. Assert no horizontal scroll except inside Kanban.

---

## Phase 14 — Notifications & Emails

### 14.1 Mailables
- [ ] `WelcomeMail` (Owner registration)
- [ ] `InviteMail` (US-2.1)
- [ ] `AccountCreatedMail` (US-2.2)
- [ ] `PasswordResetMail`
- [ ] `PasswordResetConfirmationMail`
- [ ] All branded against design tokens.
- **Tests:** `tests/Feature/Notifications/MailablesTest.php` — for each, assert subject, recipient, key body strings, and that triggers dispatch on the right action.

### 14.2 Queues
- [ ] All email send via queued jobs.
- [ ] Failed-job handling; retries.
- **Tests:** `tests/Feature/Notifications/QueueTest.php` — `Mail::fake()` + `Queue::fake()` confirm queued dispatch.

---

## Phase 15 — QA, Smoke & Hardening

### 15.1 Architecture tests
- [ ] `tests/Feature/ArchTest.php` (Pest arch)
  - Models extend `Illuminate\Database\Eloquent\Model`
  - Livewire components extend `Livewire\Component`
  - No `dd`/`dump`/`var_dump` in `app/`

### 15.2 Pest 4 browser smoke
- [ ] `tests/Browser/Smoke/AppSmokeTest.php` — visit each main route; assert no JS console errors.

### 15.3 N+1 audit
- [ ] Enable strict mode in non-prod; tests assert key list pages don't trigger lazy-loading.

### 15.4 Security review
- [ ] All forms CSRF-protected.
- [ ] All file paths validated; uploads stored on private disk.
- [ ] Webhook signature verified.
- [ ] Headers: HSTS, X-Frame-Options, Content-Security-Policy.
- **Tests:** `tests/Feature/Security/HeadersTest.php`.

### 15.5 Documentation
- [ ] Update README with setup, env vars, Evolution API config, seeder/demo data instructions.
- [ ] Add `docs/onboarding.md` for new devs.

### 15.6 Deployment readiness
- [ ] `php artisan optimize` clean.
- [ ] Production `.env` template.
- [ ] Migrations zero-downtime safe (no destructive alter on populated tables).

---

## Status Summary

| Phase | Topic | Done / Total |
|-------|-------|--------------|
| 1 | Foundation & Tooling | 4 / 8 |
| 2 | Frontend Foundation & Design System | 1 / 14 |
| 3 | Layout Bases | 0 / 5 |
| 4 | DB Migrations, Models, Factories, Seeders | 0 / 30+ |
| 5 | Authentication | 0 / 6 |
| 6 | Company & User Management | 0 / 5 |
| 7 | Kanban Pipeline | 0 / 4 |
| 8 | Lead Management | 0 / 3 |
| 9 | Deal Management | 0 / 6 |
| 10 | WhatsApp Integration | 0 / 6 |
| 11 | Reports | 0 / 5 |
| 12 | Authorization & Tenant Isolation | 0 / 4 |
| 13 | Mobile Experience | 0 / 4 |
| 14 | Notifications & Emails | 0 / 2 |
| 15 | QA & Hardening | 0 / 6 |

Already completed items derive from a fresh Laravel 13 + Livewire 4 + Wireui + Pest 4 install with default migrations (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`). All domain features remain to be built.
