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
- [x] `tests/Feature/SmokeTest.php` asserts `GET /` returns 200.
- **Tests:** `tests/Feature/SmokeTest.php`.

### 1.3 Pest 4 setup
- [x] Pest installed
- [x] `tests/Pest.php` wires `RefreshDatabase` to all Feature tests.
- [x] `tests/Unit/PestSetupTest.php` verifies Faker helper + Pest configuration.
- **Tests:** `tests/Unit/PestSetupTest.php`.

### 1.4 Pint formatter
- [x] Pint installed
- [x] Default Laravel preset in use (no `pint.json` override needed). Run via `vendor/bin/sail bin pint --dirty --format agent`.

### 1.5 Environment variables
- [x] `.env.example` extended with: `EVOLUTION_API_URL`, `EVOLUTION_API_KEY`, `EVOLUTION_WEBHOOK_SECRET`, `EVOLUTION_HTTP_TIMEOUT`, `EVOLUTION_HTTP_RETRY`. Mail, DB and queue defaults aligned with Sail (mailpit, mysql, database driver).
- [x] `config/services.php` exposes `services.evolution` block.
- [x] `tests/Feature/ConfigTest.php` asserts keys resolve and core config defaults present.
- **Tests:** `tests/Feature/ConfigTest.php`.

### 1.6 Application service providers
- [x] `App\Providers\DomainServiceProvider` registered in `bootstrap/providers.php`.
- [x] Binds `EvolutionClient` (singleton, configured from `services.evolution`) and `ActivityRecorder` (singleton stub for Phase 9.6).
- [x] `tests/Unit/ServiceProviderBindingTest.php` resolves both as singletons.
- **Tests:** `tests/Unit/ServiceProviderBindingTest.php`.

---

## Phase 2 — Frontend Foundation & Design System

Reference assets: `docs/design/padrao_de_cores.png`, `docs/design/botoes.png`, `docs/design/checkbox.png`, `docs/design/formularios.png`, `docs/design/dashboard.png`, `docs/design/kanban.png`, `docs/design/whatsapp.png`, `docs/design/layout_base_login.png`.

### 2.1 Tailwind v4 + Vite pipeline
- [x] Vite + Tailwind installed
- [x] Tailwind v4 `@theme` block populated with project palette extracted from `padrao_de_cores.png`.
- [x] CSS custom properties for primary/accent/success/warning/danger/neutral defined in `resources/css/app.css`.
- **Tests:** covered by `tests/Feature/Dev/UiPreviewTest.php` (asserts vite stylesheet loaded on `/dev/ui`).

### 2.2 Color tokens
- [x] Palette implemented as Tailwind theme tokens (`primary-*`, `accent-*`, `surface*`, `outline`, `ink`, `ink-muted`, `success`, `warning`, `danger`).
- [x] Inline documentation block in `resources/css/app.css` listing every token + design source.

### 2.3 Typography
- [x] Inter loaded as primary `--font-sans` (Instrument Sans kept as fallback). Base line-height + smoothing applied to `html` / `body`.

### 2.4 Base components (under `resources/views/components/ui/`)
- [x] **2.4.1 Button** — variants primary/secondary/outline/ghost/danger/success; sizes sm/md/lg; loading + disabled states.
- [x] **2.4.2 Input** — label, hint, error, optional leading icon, error-state styling.
- [x] **2.4.3 Textarea** — label, hint, error.
- [x] **2.4.4 Select** — placeholder, options array, value selection, error state.
- [x] **2.4.5 Checkbox** — checked/disabled/error/success states.
- [x] **2.4.6 Radio** — same state matrix as checkbox.
- [x] **2.4.7 Modal** — header/body/footer slots, sizes sm/md/lg/xl, hidden by default.
- [x] **2.4.8 Toast** — info/success/warning/danger.
- [x] **2.4.9 Card** — title/subtitle/header/footer/actions slots.
- [x] **2.4.10 Badge** — neutral/primary/success/warning/danger/accent.
- [x] **2.4.11 Avatar** — initials fallback + image variant.
- **Tests:** `tests/Feature/Ui/UiComponentsTest.php` — Blade-rendering assertions per component.

### 2.5 Icon set
- [x] `<x-ui.icon name="…">` thin wrapper over Wireui's `<x-icon>` (Heroicons outline/solid).

### 2.6 Component preview page (dev-only)
- [x] Route `GET /dev/ui` gated by `App::environment('production')` returning 404 in production.
- [x] View at `resources/views/dev/ui-preview.blade.php` showing every component for visual QA.
- **Tests:** `tests/Feature/Dev/UiPreviewTest.php` — preview returns 200 in non-prod, 404 in production.

---

## Phase 3 — Layout Bases

### 3.1 Guest layout (non-logged)
Reference: `docs/design/layout_base_login.png`.
- [x] `resources/views/components/layouts/guest.blade.php` — split-screen layout (form left, brand block right), brand mark, mobile-first.
- [x] `tests/Feature/Layouts/GuestLayoutTest.php` — Blade-renders layout with brand + slot + heading/subheading.

### 3.2 App layout (logged-in)
Reference: `docs/design/dashboard.png`.
- [x] `resources/views/components/layouts/app.blade.php` — fixed icon sidebar, sticky topbar, Alpine-powered mobile drawer, `actions` slot.
- [x] Sidebar items role-aware via `App\Support\Navigation`: Kanban + Leads + Settings for everyone; Reports + Team only for Business Owner; empty for guests.
- [x] `app/Models/User::roleSlug()` defensive accessor (returns null until Phase 4 wires the relation).
- [x] `tests/Unit/Support/NavigationTest.php` — role matrix.
- [x] `tests/Feature/Layouts/AppLayoutTest.php` — sidebar/topbar/drawer markers + role-scoped nav items.

### 3.3 Layout primitives
- [x] `<x-ui.page-header>` — title, subtitle, actions slot.
- [x] `<x-ui.empty-state>` — title, description, icon, actions slot.
- [x] `<x-ui.skeleton>` — text (configurable lines), avatar, card shapes.
- [x] `tests/Feature/Layouts/PrimitivesTest.php` — render assertions for each.

---

## Phase 4 — Database Migrations, Models, Factories, Seeders

Implements `docs/database_schema.md`.

### 4.1 Lookup tables migrations
- [x] **4.1.1** `roles`
- [x] **4.1.2** `pipeline_stages`
- [x] **4.1.3** `invite_statuses`
- [x] **4.1.4** `whatsapp_connection_statuses`
- [x] **4.1.5** `message_directions`
- [x] **4.1.6** `message_statuses`
- [x] **4.1.7** `message_types`
- [x] **4.1.8** `activity_types`

### 4.2 Core domain migrations
- [x] **4.2.1** `companies`
- [x] **4.2.2** `modify_users_table` adds `company_id`, `role_id`, `avatar_path`, `is_active`, `must_change_password`, `last_login_at`, `deleted_at`
- [x] **4.2.3** `invites` (token unique, composite `company_id+email` index)
- [x] **4.2.4** `leads` with composite unique `(company_id, email)`
- [x] **4.2.5** `deals` with `loss_reason`, `won_at`, `lost_at`, soft deletes
- [x] **4.2.6** `deal_notes`
- [x] **4.2.7** `activities` with `metadata` JSON, `(deal_id, created_at)` index
- [x] **4.2.8** `whatsapp_connections` (one-per-user unique, instance_name unique)
- [x] **4.2.9** `messages` (lead_id+created_at, deal_id+created_at indexes)
- **Tests:** `tests/Feature/Database/SchemaTest.php` — table + column presence; lead-email composite uniqueness; cross-company email allowed; whatsapp one-per-user enforced.

### 4.3 Models
- [x] `Company`, `Role`, `User` (modified), `Invite`, `InviteStatus`.
- [x] `PipelineStage`, `Lead`, `Deal`, `DealNote`.
- [x] `ActivityType`, `Activity`.
- [x] `WhatsappConnection`, `WhatsappConnectionStatus`.
- [x] `MessageDirection`, `MessageStatus`, `MessageType`, `Message`.
- [x] All tenant models use `App\Models\Concerns\BelongsToCompany`. User exposes `roleSlug()`, `isBusinessOwner()`, `isSalesperson()`. Domain constants live as model class consts.
- **Tests:** `tests/Unit/Models/RelationshipsTest.php` — verifies HasMany / BelongsTo / HasOne wiring per model.

### 4.4 Factories
- [x] Factory for every domain model.
- [x] States: `User::factory()->businessOwner()`, `salesperson()`, `inactive()`, `forCompany()`; `Lead::factory()->forCompany()`, `ownedBy()`; `Deal::factory()->forLead()`, `inStage(slug)`, `withLossReason()`, `won()`; `Invite::factory()->accepted()`; `WhatsappConnection::factory()->connected()`; `Message::factory()->inbound()`; `Role`/`PipelineStage` factories with named states.
- **Tests:** `tests/Feature/Database/FactoriesTest.php` — every factory persists; `Deal::withLossReason()` + `Deal::won()` set the right stage flags.

### 4.5 Seeders
- [x] **4.5.1** `RoleSeeder`, **4.5.2** `PipelineStageSeeder`, **4.5.3** `InviteStatusSeeder`, **4.5.4** `WhatsappConnectionStatusSeeder`.
- [x] **4.5.5** `MessageDirectionSeeder`, `MessageStatusSeeder`, `MessageTypeSeeder`.
- [x] **4.5.6** `ActivityTypeSeeder`.
- [x] **4.5.7** `DatabaseSeeder` orchestrates all eight lookup seeders. (`DemoDataSeeder` deferred until Phase 15 polishing.)
- **Tests:** `tests/Feature/Database/LookupSeedTest.php` — every canonical slug seeded; Won/Lost stages flagged correctly.

### 4.6 CompanyScope global scope
- [x] `App\Models\Scopes\CompanyScope` filters by authenticated user's `company_id` (no-op for guests).
- [x] `App\Models\Concerns\BelongsToCompany` boots the global scope and auto-fills `company_id` on `creating` when the user is authenticated and value is missing.
- **Tests:** `tests/Feature/Authorization/TenantScopeTest.php` — cross-tenant reads filtered for Lead + Deal; `company_id` auto-fill; explicit `company_id` honored.

---

## Phase 5 — Authentication & Registration

### 5.1 Auth scaffolding
- [x] Livewire components in `app/Livewire/Auth/`: `Register`, `Login`, `ForgotPassword`, `ResetPassword`, `VerifyEmail`.
- [x] Dedicated `routes/auth.php` mounted via `bootstrap/app.php` `then` hook; guest + auth groups.
- [x] Default redirect targets configured: guests → `login`, users → `/kanban`.

### 5.2 Business Owner registration (US-1.1)
- [x] Livewire `Register` form: name, email, password, password_confirmation, company_name.
- [x] DB transaction creates Company → User with `business_owner` role.
- [x] Queued `WelcomeMail` + email verification notification dispatched.
- [x] Auto-login + redirect to `/kanban`.
- **Tests:** `tests/Feature/Auth/BusinessOwnerRegistrationTest.php` — happy path, duplicate email, weak password, mail/notification dispatch.

### 5.3 Login (US-1.2)
- [x] Livewire `Login` with `RateLimiter` (5 attempts per email+IP / 60s).
- [x] Generic error on bad credentials.
- [x] `remember` checkbox + `Auth::attempt(..., $this->remember)`.
- [x] Inactive users blocked at the credential layer (`is_active = true` constraint).
- [x] `last_login_at` stamped on success.
- **Tests:** `tests/Feature/Auth/LoginTest.php` — happy path, bad credentials, throttle, inactive user blocked.

### 5.4 Logout (US-1.4)
- [x] `POST /logout` route (auth-only) invalidates session and regenerates CSRF token.
- **Tests:** `tests/Feature/Auth/LogoutTest.php` — logout for authed user; guest redirected to `login`.

### 5.5 Password reset (US-1.3)
- [x] `ForgotPassword` Livewire — calls `Password::sendResetLink`; same generic message regardless of email match (no enumeration).
- [x] `ResetPassword` Livewire — uses Laravel `Password::reset`; on success: deletes the user's session rows + dispatches `PasswordResetConfirmationMail`.
- [x] Default broker `expire => 60` (minutes) kept.
- **Tests:** `tests/Feature/Auth/PasswordResetTest.php` — known + unknown email path, valid token resets + queues confirmation mail, invalid token rejected.

### 5.6 Email verification
- [x] `User` implements `MustVerifyEmail`. Routes guarded by `verified` middleware.
- [x] `VerifyEmail` Livewire notice page with resend + sign-out actions.
- [x] Signed verification URL + 6/min throttle.
- **Tests:** `tests/Feature/Auth/EmailVerificationTest.php` — unverified blocked, signed verify URL accepted, verified can reach `/kanban`.

---

## Phase 6 — Company & User Management

### 6.1 Roles & policies foundation
- [x] `App\Policies\` for User, Lead, Deal, DealNote, Invite, WhatsappConnection, Message, Activity.
- [x] Owner gets in-company access expressed inside each policy method (no global `before` so self-deactivate stays blocked).
- **Tests:** `tests/Feature/Authorization/PolicyTest.php` — allow/deny matrix per policy method per role.

### 6.2 Invite via email link (US-2.1)
- [x] Owner UI (`App\Livewire\Team\InviteList`) at `/team/invites` to enter name + email; dispatches `SendInviteJob`.
- [x] `Str::random(48)` token, 7-day expiry, persisted on `invites`.
- [x] `App\Mail\InviteMail` with invite URL.
- [x] Public route `/invites/{token}` (`App\Livewire\Invites\AcceptInvite`) — set-password form creates Salesperson + auto-login.
- [x] Owner list shows pending/accepted/expired/revoked with resend + revoke. Pending past `expires_at` auto-marked expired on render.
- **Tests:** `tests/Feature/Companies/EmailInviteTest.php`
  - owner creates invite → job dispatched, token persisted
  - non-owner cannot create invite (403)
  - duplicate user email + existing pending invite rejected
  - acceptance form creates Salesperson + auto-logs in
  - expired token marked expired and rejected
  - revoked token rejected
  - resend regenerates token + dispatches mail
  - revoke flips status to revoked
  - queued job sends `InviteMail`

### 6.3 Direct account creation (US-2.2)
- [x] Owner form (`App\Livewire\Team\CreateUser`): name, email, temporary password.
- [x] Creates Salesperson with `must_change_password=true`, email_verified.
- [x] `App\Mail\AccountCreatedMail` with login + temporary password.
- [x] `App\Http\Middleware\EnsurePasswordChanged` (web group) forces redirect to `/password/change` (`App\Livewire\Auth\ChangePassword`) until flag cleared.
- **Tests:** `tests/Feature/Companies/DirectAccountCreationTest.php`
  - account created with role salesperson + must_change_password
  - mail queued with temp password
  - duplicate email rejected
  - first login forces password change redirect
  - password change clears flag
  - non-owner forbidden

### 6.4 List & manage Salespeople (US-2.3)
- [x] `App\Livewire\Team\UserList` at `/team` — table with name, email, role, status, joined date, deactivate/reactivate.
- [x] Live search (`?q=`) filters by name or email.
- [x] Owner cannot deactivate self (policy + view hides action).
- **Tests:** `tests/Feature/Companies/UserManagementTest.php`
  - list shows only same-company users
  - search filters by name and email
  - deactivate blocks login
  - reactivate restores login
  - cannot deactivate self (403)
  - non-Owner forbidden (403)
  - cross-company target forbidden

### 6.5 Reassign lead ownership (US-2.4)
- [x] `App\Services\Leads\ReassignLeadService` reassigns lead + cascades to all deals in a transaction.
- [x] `App\Services\Activity\ActivityRecorder` upgraded to persist real `activities` rows. Writes `lead_reassigned` (lead) + `ownership_changed` (per deal).
- [x] `App\Livewire\Leads\ReassignLead` embeddable Salesperson-picker form, owner-only.
- **Tests:** `tests/Feature/Leads/ReassignLeadTest.php`
  - lead + all deals reassigned
  - `lead_reassigned` + `ownership_changed` activities recorded with before/after + actor
  - previous owner loses `view` ability (policy)
  - only Owner can mount the Livewire component (403 for original owner)
  - new owner from another company rejected with `InvalidArgumentException`

---

## Phase 7 — Kanban Pipeline (wire:sort)

### 7.1 Kanban route + Livewire component
- [x] `/kanban` (auth + verified) routes to `App\Livewire\Kanban\Board` (`#[Layout('components.layouts.app')]`).
- [x] Board renders columns from active `pipeline_stages` ordered by position.
- [x] Salesperson scoping via `owner_user_id = auth->id`; Owner sees all + optional `ownerFilter`. `CompanyScope` keeps cross-tenant invisible.
- **Tests:** `tests/Feature/Kanban/BoardRenderTest.php`
  - Salesperson sees only own deals
  - Owner sees all company deals
  - cross-company deals never visible
  - column counts + totals correct
  - all six pipeline stages rendered

### 7.2 Kanban view (Tailwind + responsive)
- [x] Horizontally scrollable column row on mobile (`flex overflow-x-auto`), grid on desktop (`lg:overflow-visible`, `flex-1` columns).
- [x] Card shows title, formatted value, lead name, last-activity timestamp, owner badge (Owner-only).
- [x] Loss-reason modal lives in same component, gated by `pendingLostDealId`.
- **Tests:** `tests/Browser/Kanban/BoardLayoutTest.php` — deferred (Pest 4 browser smoke covered in Phase 15).

### 7.3 Drag-and-drop with `wire:sort`
- [x] `wire:sort="updateStage($item, '<slug>')"` per column; cards expose `wire:sort.item="<deal-id>"`. No external DnD libs.
- [x] `Board::updateStage(int $dealId, string $toStageSlug)` authorizes via `move` policy, no-ops on same stage.
- [x] Destination `lost` → returns `['status' => 'requires_loss_reason']` and arms `pendingLostDealId`; modal calls `confirmLoss` (validates non-empty `lossReason`) or `cancelLoss` to revert.
- [x] Destination `won` → sets `won_at`; `DealPolicy::update` locks further edits while keeping notes editable.
- [x] `ActivityRecorder` writes `stage_changed` (with `from_stage_id`/`to_stage_id` in metadata) plus `deal_won` / `deal_lost` on terminal moves.
- **Tests:** `tests/Feature/Kanban/MoveDealTest.php`
  - moves deal to allowed stage + writes stage_changed activity
  - non-owning Salesperson gets 403
  - move to lost without reason fails validation
  - move to lost with reason persists loss_reason + lost_at + deal_lost activity
  - cancelLoss reverts pending state
  - move to won sets won_at, locks update via policy, writes deal_won activity
  - returns requires_loss_reason flag when targeting lost
- Browser regression test deferred to Phase 15.

### 7.4 Kanban filter (US-3.3, Owner only)
- [x] Owner-only `<select>` (`data-testid="owner-filter"`) lists active company Salespeople; "All" submits null.
- [x] State bound via `#[Url(as: 'owner')]` so it survives reloads.
- [x] `mount()` strips bogus `?owner=` for non-owners; `setOwnerFilter` throws `ValidationException` if non-owner posts directly.
- **Tests:** `tests/Feature/Kanban/FilterTest.php`
  - filter scopes board to selected Salesperson
  - null filter returns all company deals
  - non-Owner does not see filter, cannot set it
  - non-Owner mount strips `?owner=`
  - Owner `?owner=` query param hydrates filter

---

## Phase 8 — Lead Management

### 8.1 Inline lead creation from Kanban (US-4.1)
- [x] `App\Livewire\Leads\CreateLead` modal mounted in board topbar (`<livewire:leads.create-lead />`).
- [x] Form: name, email, phone, notes; Owner-only `ownerUserId` select.
- [x] `email` debounced live update hits `LeadLookupService::findInCompany` (case-insensitive, trim) to populate `matchedLeadId` and `matchVisible`.
- [x] No match → `CreateLeadWithDeal::create` opens transaction, creates Lead + Deal in `new_lead` stage, records `lead_created` + `deal_created` activities.
- [x] Match → `reuseExisting` calls `addDealForLead` instead of creating second lead.
- **Tests:** `tests/Feature/Leads/CreateLeadTest.php`
  - creates lead + deal + lead_created activity in one submission
  - rejects duplicate email per company
  - allows same email in different company
  - sets owner to current user for Salesperson
  - Owner can assign lead to a Salesperson
  - service throws + rolls back when owner is from another company

### 8.2 Reuse existing lead on duplicate email (US-4.2)
- [x] `App\Services\Leads\LeadLookupService::findInCompany` + `isVisibleTo` enforce visibility (Salesperson sees only own; Owner sees all).
- [x] CreateLead modal renders match card with "Add deal to this lead" when visible.
- [x] Hidden match shows "Lead exists; contact your manager." `reuseExisting` rejects with email error if not visible.
- [x] Reuse calls `CreateLeadWithDeal::addDealForLead` — no new lead, only a new deal in `new_lead` stage.
- **Tests:** `tests/Feature/Leads/ReuseLeadTest.php`
  - duplicate email surfaces existing lead reference
  - Salesperson blocked from reusing another Salesperson's lead (no deal created)
  - Owner can reuse any lead in company
  - reuse path adds deal but lead count unchanged
  - `LeadLookupService` matches case-insensitive + trimmed input

### 8.3 Edit lead (US-4.3)
- [x] `App\Livewire\Leads\EditLead` (`#[Layout]`, `/leads/{lead}`) shows form + reassign panel for Owner.
- [x] Email field hidden behind `LeadPolicy::updateEmail` (Owner-only); Salesperson form silently keeps original email.
- [x] Email change re-runs uniqueness validation (case-insensitive, trimmed, scoped to company, excludes self).
- [x] `ActivityRecorder` writes one `lead_updated` per changed field with `field` in metadata + before/after strings.
- **Tests:** `tests/Feature/Leads/EditLeadTest.php`
  - updates name/phone/notes
  - Salesperson cannot change email (silent revert, no error)
  - Owner can change email
  - duplicate email rejected on update
  - one `lead_updated` activity per changed field with metadata
  - no activity written when nothing changes
  - non-owning Salesperson gets 403 on mount

---

## Phase 9 — Deal Management

### 9.1 Auto-create deal on lead creation (US-5.1)
- [x] `App\Services\Leads\CreateLeadWithDeal::create` (built in 8.1) wraps Lead + Deal creations in `DB::transaction`.
- [x] Deal defaults: `title = lead.name`, `value = 0`, `stage = new_lead`. `lead_created` + `deal_created` activities written.
- **Tests:** `tests/Feature/Deals/AutoCreateDealTest.php`
  - happy path returns lead + deal in `new_lead` stage with $0
  - missing `new_lead` stage rolls back lead and deal
  - cross-company owner throws `InvalidArgumentException`

### 9.2 Add additional deal to existing lead (US-5.2)
- [x] `App\Livewire\Deals\AddDeal` form (title + value) calls `CreateLeadWithDeal::addDealForLead`. Owner inherited from lead, stage defaults `new_lead`.
- **Tests:** `tests/Feature/Deals/AddDealTest.php`
  - additional deal created with inherited owner + new_lead stage
  - title required
  - value must be ≥ 0
  - non-owning Salesperson forbidden

### 9.3 View & edit deal detail (US-5.3)
- [x] `/deals/{deal}` routes to `App\Livewire\Deals\Show` (`#[Layout]`).
- [x] Sections (data-testid): `deal-header-section`, `lead-info-section`, `owner-section`, `notes-section`, `activity-section`, `chat-shortcut-section`.
- [x] Title/value form gated by `DealPolicy::update`; won deals show read-only `dl` + `deal-locked-notice`. Notes always editable for visible users.
- [x] Title change → `deal_updated` activity (with `field: title`); value change → `value_changed` activity.
- **Tests:** `tests/Feature/Deals/DealDetailTest.php`
  - all six sections rendered
  - title + value update writes `deal_updated` and `value_changed`
  - won deal returns 403 on save
  - cross-company deal returns 404 via route binding
  - non-owning Salesperson returns 403 on mount
  - Owner can edit any non-won deal in company
  - negative value rejected

### 9.4 Mark deal as Lost with reason (US-5.4)
- [x] `Show::openLostModal` / `confirmLost` / `cancelLost` drive in-detail loss flow (modal `data-testid="lost-modal"`).
- [x] Server validates non-empty `lossReason`, persists `loss_reason` + `lost_at`, switches stage to `lost`. Cancel resets state without DB write.
- [x] `stage_changed` + `deal_lost` activities written (deal_lost metadata carries `loss_reason`).
- **Tests:** `tests/Feature/Deals/MarkLostTest.php`
  - empty reason rejected, stage unchanged
  - valid reason persists loss_reason + lost_at + stage
  - `deal_lost` activity contains reason in metadata
  - cancel reverts modal + lossReason without changing stage

### 9.5 Deal notes (US-5.5)
- [x] `App\Livewire\Deals\NotesPanel` embedded inside Show. Append-only form, list shows author + `diffForHumans()` timestamp.
- [x] `note_added` activity written (metadata.preview = first 80 chars).
- [x] `DealNotePolicy::create` blocks non-owning Salesperson (Gate authorized at `viewAny` on mount + `create` on add).
- **Tests:** `tests/Feature/Deals/NotesTest.php`
  - owner-of-deal Salesperson adds note + activity
  - non-owning Salesperson forbidden on mount
  - cross-company forbidden
  - Owner can add note to any deal in company
  - empty body rejected

### 9.6 Deal activity history (US-5.6)
- [x] `App\Livewire\Deals\Timeline` (uses `WithPagination`) reads `activities` for `deal_id`, sorted `created_at DESC`, page size 15.
- [x] Read-only — `ActivityPolicy::create/update/delete` all return false.
- **Tests:** `tests/Feature/Deals/ActivityTimelineTest.php`
  - includes mixed activity types (deal_created, stage_changed, value_changed, ownership_changed, note_added, message_sent) sorted DESC
  - paginates (perPage=15, total=25, returns 15 on first page)
  - policy denies create/update/delete on activities
  - non-owning Salesperson forbidden on mount

---

## Phase 10 — WhatsApp Integration (Evolution API v2)

### 10.1 Evolution API client
- [x] `App\Services\Evolution\EvolutionClient` exposes `createInstance`, `fetchQr`, `fetchStatus`, `sendTextMessage`, `disconnect` over `Http` macro with `apikey` header, configured timeout + retry.
- [x] `createInstance` payload includes webhook URL + `X-Webhook-Secret` header for signed webhooks.
- **Tests:** `tests/Unit/Services/EvolutionClientTest.php` (via `uses(TestCase::class)`)
  - createInstance posts payload with apikey + webhook headers
  - fetchQr GET, fetchStatus GET, sendTextMessage POST, disconnect DELETE
  - exposes baseUrl

### 10.2 Connect WhatsApp via QR code (US-6.1)
- [x] `App\Services\Whatsapp\ConnectionService` provisions/refreshes Evolution instance, persists `whatsapp_connections` row with deterministic `instance_name` (sha1 prefix), random `webhook_secret`.
- [x] `App\Livewire\Whatsapp\ConnectionPanel` lives on `/settings`. Connect → pending + QR; refreshQr; disconnect.
- **Tests:** `tests/Feature/Whatsapp/ConnectTest.php`
  - Connect provisions instance + persists pending status
  - QR rendered when API returns one
  - CONNECTION_UPDATE webhook flips to connected with phone
  - disconnect clears state + sets disconnected_at
  - reusing connect for same user idempotent + DB unique enforced

### 10.3 Webhook receiver
- [x] `POST /webhooks/evolution/{user}` (`EvolutionWebhookController`) — CSRF-exempt via `validateCsrfTokens(except)`, throttled 60/min.
- [x] Validates `X-Webhook-Secret` header against stored connection secret using `hash_equals`.
- [x] Handles `CONNECTION_UPDATE`, `MESSAGES_UPSERT` (idempotent on `external_id`, lead matched by digits-only phone), `MESSAGES_UPDATE` (status transitions: delivered/read/failed).
- [x] Unknown phones logged + dropped (no auto-lead creation in MVP).
- **Tests:** `tests/Feature/Whatsapp/WebhookTest.php`
  - invalid signature → 401
  - persists inbound message + matches lead by phone
  - idempotent on duplicate external_id
  - unknown phone logged + dropped
  - CONNECTION_UPDATE flips status
  - missing connection → 404

### 10.4 Open chat from a deal (US-6.2)
- [x] Deal detail chat shortcut (`chat-shortcut-section`) renders link only when WhatsApp connected AND lead has phone.
- [x] Disconnected → `chat-blocked-disconnected` notice; no phone → `chat-blocked-no-phone`.
- [x] Link points to `route('whatsapp.conversation', ['lead' => ..., 'deal' => ...])`.
- **Tests:** `tests/Feature/Whatsapp/OpenChatTest.php`
  - link visible when connected + phone
  - hidden when disconnected
  - hidden when no phone
  - URL contains lead + deal params

### 10.5 Send & receive messages (US-6.3)
- [x] `App\Livewire\Whatsapp\Conversation` bound to `lead` (route param) + optional `?deal=`. Renders message history oldest-first via `messageList` computed.
- [x] `send()` persists `pending`, calls `EvolutionClient::sendTextMessage`, flips to `sent` (with external_id + sent_at) on success or `failed` on error.
- [x] When `dealId` set, writes `message_sent` activity.
- [x] Inbound webhook surfaces messages in conversation.
- **Tests:** `tests/Feature/Whatsapp/MessagingTest.php`
  - send → message persists with sent + external_id + sent_at
  - inbound webhook message appears in conversation
  - cross-Salesperson conversation forbidden
  - message_sent activity recorded with deal context
  - MESSAGES_UPDATE webhook updates outbound status to read
  - messages sorted oldest-first
- Polling/unread badge deferred (UI-only; covered by Phase 13/15 polish).

### 10.6 Disconnected state handling (US-6.4)
- [x] Webhook `state: close` flips connection to `disconnected` via `ConnectionService::markStatus`.
- [x] `Conversation::send` short-circuits with body validation error when status not `connected`; banner `conversation-disconnected-banner` rendered.
- [x] `ConnectionPanel` shows `whatsapp-disconnected-banner` + `whatsapp-reconnect` button when disconnected.
- **Tests:** `tests/Feature/Whatsapp/DisconnectedStateTest.php`
  - webhook flips to disconnected
  - send blocked with body error, no message persisted
  - conversation renders disconnected banner
  - connection panel renders banner + reconnect button

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
| 1 | Foundation & Tooling | 8 / 8 |
| 2 | Frontend Foundation & Design System | 14 / 14 |
| 3 | Layout Bases | 5 / 5 |
| 4 | DB Migrations, Models, Factories, Seeders | 30 / 30 |
| 5 | Authentication | 6 / 6 |
| 6 | Company & User Management | 5 / 5 |
| 7 | Kanban Pipeline | 4 / 4 |
| 8 | Lead Management | 3 / 3 |
| 9 | Deal Management | 6 / 6 |
| 10 | WhatsApp Integration | 6 / 6 |
| 11 | Reports | 0 / 5 |
| 12 | Authorization & Tenant Isolation | 0 / 4 |
| 13 | Mobile Experience | 0 / 4 |
| 14 | Notifications & Emails | 0 / 2 |
| 15 | QA & Hardening | 0 / 6 |

Already completed items derive from a fresh Laravel 13 + Livewire 4 + Wireui + Pest 4 install with default migrations (`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `failed_jobs`). All domain features remain to be built.
