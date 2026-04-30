# sgCrm

Laravel 13 + Livewire 4 CRM with Kanban pipeline and WhatsApp (Evolution API v2) integration.

## Stack

- PHP 8.5, Laravel 13
- Livewire 4 (`wire:sort` Kanban — no external DnD libs)
- Wireui 2.6 (icons, modals)
- Tailwind v4 + Vite
- Pest 4 (Feature + Unit)
- Pint (code formatter)
- Laravel Sail (Docker dev environment)

## Quick start

```bash
# clone, then:
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail npm install
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm run dev
```

Browse: `vendor/bin/sail open` or `http://localhost`.

## Environment variables

Required (already in `.env.example`):

| Var | Purpose |
|-----|---------|
| `APP_NAME`, `APP_URL` | Identity and link generation. |
| `DB_*` | MySQL credentials (Sail defaults: host `mysql`, user `sail`). |
| `QUEUE_CONNECTION` | Default `database`; `sync` only for testing. |
| `MAIL_*` | Mailpit in dev (host `mailpit`, port 1025). |
| `EVOLUTION_API_URL` | Evolution v2 base URL (e.g. `http://evolution-api:8080`). |
| `EVOLUTION_API_KEY` | API key sent in `apikey` header on every Evolution call. |
| `EVOLUTION_WEBHOOK_SECRET` | Default secret (per-user random secret stored on `whatsapp_connections`). |
| `EVOLUTION_HTTP_TIMEOUT` | HTTP timeout seconds (default 10). |
| `EVOLUTION_HTTP_RETRY` | HTTP retry attempts (default 2). |

## Evolution API setup

1. Run the Evolution v2 container alongside Sail (separate compose service).
2. Set `EVOLUTION_API_URL` to its internal URL (Docker network) or host URL.
3. Generate a strong `EVOLUTION_API_KEY` and apply it server-side.
4. The CRM creates one Evolution instance per user when they hit `/settings` → "Connect WhatsApp". Each connection stores its own `webhook_secret` used to validate inbound webhooks (`X-Webhook-Secret` header).
5. Outbound webhook URL: `POST {APP_URL}/webhooks/evolution/{user_id}`.

Supported webhook events:

- `CONNECTION_UPDATE` — updates connection status (open / close / connecting).
- `MESSAGES_UPSERT` — persists inbound text messages, matches lead by digits-only phone.
- `MESSAGES_UPDATE` — updates outbound message status (delivered / read / failed).

Unknown phones are logged and dropped (no auto-lead creation in MVP).

## Seeders

Lookup data lives in `database/seeders`:

- `RoleSeeder` (business_owner, salesperson)
- `PipelineStageSeeder` (new_lead, contacted, proposal_sent, negotiation, won, lost)
- `InviteStatusSeeder`
- `WhatsappConnectionStatusSeeder`
- `MessageDirectionSeeder` / `MessageStatusSeeder` / `MessageTypeSeeder`
- `ActivityTypeSeeder`

Run with:

```bash
vendor/bin/sail artisan db:seed
```

`DatabaseSeeder` orchestrates all eight lookup seeders. Demo workspace data is intentionally not seeded — register a Business Owner via `/register` to bootstrap a workspace.

## Daily commands

```bash
vendor/bin/sail artisan test --compact          # full Pest run
vendor/bin/sail artisan test --filter=Kanban    # focused
vendor/bin/sail bin pint --dirty --format agent # format changed files
vendor/bin/sail npm run dev                     # Vite watcher
vendor/bin/sail npm run build                   # production assets
vendor/bin/sail artisan queue:work              # process queued mailables
```

## Testing

- Pest 4 Feature tests under `tests/Feature/` (cover auth, Kanban, leads, deals, WhatsApp, reports, authorization, mobile, notifications, smoke, performance, security).
- Unit tests under `tests/Unit/` (services, navigation).
- Browser tests deferred (no Pest browser plugin installed).
- DB resets per test via `RefreshDatabase` (wired in `tests/Pest.php`).

## Deployment

See `docs/onboarding.md` and Phase 15 of `docs/project-phases.md`. Production tips:

- `php artisan config:cache route:cache view:cache` (or `php artisan optimize`).
- `APP_DEBUG=false`, `APP_ENV=production`.
- Real queue worker (`database` or `redis`), not `sync`.
- Real mail driver (SES, SMTP), not `log`.
- `Model::shouldBeStrict` is auto-disabled in production by `App\Providers\AppServiceProvider`; `URL::forceScheme('https')` is enabled.
- `App\Http\Middleware\SecurityHeaders` ships HSTS / CSP / X-Frame-Options on every web response.

## Documentation

- `docs/project_description.md` — domain overview and goals.
- `docs/user_stories.md` — feature inventory by persona.
- `docs/database_schema.md` — schema map.
- `docs/project-phases.md` — phased implementation roadmap with test acceptance criteria.
- `docs/onboarding.md` — new dev quick reference.
