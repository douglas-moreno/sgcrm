# Onboarding — sgCrm

Quick reference for new developers.

## Prerequisites

- Docker (Sail uses it).
- `vendor/bin/sail` for every command.

## First boot

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail npm install
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm run dev
```

Visit `http://localhost`. Hit `/register` to create your first Business Owner workspace.

## Project layout

```
app/
  Http/
    Controllers/Webhooks/   Evolution webhook entry
    Middleware/             EnsureRole, EnsurePasswordChanged, SecurityHeaders
  Jobs/                     SendInviteJob (queued)
  Livewire/                 All UI (Auth, Kanban, Leads, Deals, Whatsapp, Reports, Team, Settings, Invites)
  Mail/                     5 queued mailables (markdown)
  Models/                   Domain models + Concerns/BelongsToCompany + Scopes/CompanyScope
  Policies/                 Per-model policies
  Providers/                AppServiceProvider, DomainServiceProvider
  Services/                 Activity, Evolution, Invites, Leads, Reports, Whatsapp

database/
  factories/                Factory per domain model
  migrations/               Lookup + core domain
  seeders/                  Eight lookup seeders + DatabaseSeeder

resources/views/
  components/               UI library (`<x-ui.*>`) + layouts (guest, app)
  livewire/                 Per-component Blade
  mail/                     Branded markdown mailables

routes/
  web.php                   Authed app routes + reports + webhook
  auth.php                  Guest auth flows + password change
  console.php

tests/
  Feature/                  Auth, Companies, Kanban, Leads, Deals, Whatsapp, Reports,
                            Authorization, Mobile, Notifications, Smoke, Performance, Security
  Unit/                     Services, Models, Support
```

## Conventions

- All domain models use `BelongsToCompany` trait. `CompanyScope` filters every query to the authenticated user's company.
- Owner vs Salesperson: `User::isBusinessOwner()`, `isSalesperson()`. Most policy methods short-circuit on `company_id` mismatch first.
- Activities are an append-only audit trail. `App\Services\Activity\ActivityRecorder::record($slug, $payload)` is the only writer.
- Kanban DnD uses Livewire 4 `wire:sort` exclusively. Never add SortableJS or similar.
- Livewire components live in `app/Livewire/`; their views in `resources/views/livewire/`.
- Mailables use `Content(markdown: ...)` not `Content(view: ...)` (so `<x-mail::message>` resolves).
- Strict types declared on every PHP file. Enforced by `tests/Feature/ArchTest.php`.

## Daily workflow

```bash
# format changed PHP
vendor/bin/sail bin pint --dirty --format agent

# run all tests
vendor/bin/sail artisan test --compact

# focused run
vendor/bin/sail artisan test --compact --filter=Kanban

# tail logs
vendor/bin/sail artisan pail

# inspect routes
vendor/bin/sail artisan route:list --except-vendor

# tinker (sparingly)
vendor/bin/sail artisan tinker --execute 'App\Models\Company::count();'
```

## Adding a feature

1. Find the relevant phase in `docs/project-phases.md`. New work usually attaches to Phase 8/9/10.
2. Migration → Model → Factory → Policy → Service (if non-trivial) → Livewire component → View → Test.
3. Tests required: Feature test for the happy path + at least one tenant isolation case (Salesperson can't see another's data) + at least one validation error case.
4. Format: `vendor/bin/sail bin pint --dirty --format agent`. Verify: `vendor/bin/sail artisan test --compact`.

## Authorization debug checklist

If a Livewire component returns 403 unexpectedly:

1. Is the user's `company_id` set? (`User::factory()->forCompany($company)`)
2. Is `lead.owner_user_id === auth()->id()` for Salesperson actions?
3. Has `Gate::authorize` been called in `mount()` and again in any state-changing method?
4. Does the relevant Policy method check `company_id` AND `isBusinessOwner() || ownership`?

## Useful tooling

- **Laravel Boost** — MCP tools for AI agents. `vendor/bin/sail artisan boost:*`.
- **Pail** — log streamer. `vendor/bin/sail artisan pail`.
- **Mailpit** — local mail inbox. `http://localhost:8025`.
- **Telescope** — not installed (intentional for now).

## Where to look

| Topic | File |
|-------|------|
| Add a route | `routes/web.php`, `routes/auth.php` |
| Add a sidebar item | `app/Support/Navigation.php` |
| New mail | `app/Mail/*.php` + `resources/views/mail/*.blade.php` |
| Webhook handler | `app/Http/Controllers/Webhooks/EvolutionWebhookController.php` |
| Pipeline stages | `database/seeders/PipelineStageSeeder.php`, `App\Models\PipelineStage` consts |
| Activity types | `App\Models\ActivityType` consts + seeder |
| Tenant scope | `app/Models/Scopes/CompanyScope.php`, `Concerns/BelongsToCompany.php` |
