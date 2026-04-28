# Database Schema — sgCrm

Suggested schema for **sgCrm** in [DBML](https://dbml.dbdiagram.io/home) format. Targets Laravel 13 + MySQL 8. Aligned with `docs/project_description.md` and `docs/user_stories.md`.

## Conventions

- **No enum / string-enum columns.** Every categorical field uses a lookup table referenced by `*_id` foreign key.
- **File/image uploads** stored as `*_path` string columns. Multi-file relations live in dedicated tables.
- **Multi-tenant isolation**: every domain table includes `company_id` (FK → `companies`).
- **Lead email uniqueness** scoped per company via composite unique `(company_id, email)`.
- **Soft deletes** on aggregate roots that benefit from recovery (`leads`, `deals`, `users`).
- **Timestamps** (`created_at`, `updated_at`) on all domain tables. `deleted_at` where soft-deletes apply.
- **Indexes** on every foreign key plus on hot lookup columns (`email`, `phone`, `external_id`, status fields).
- **IDs**: `bigIncrements` (unsigned bigint).
- **Money**: `decimal(15,2)` for `deals.value` to avoid floating-point drift.

---

```dbml
// =====================================================
// sgCrm — Database Schema (DBML)
// =====================================================

Project sgCrm {
  database_type: 'MySQL'
  Note: 'Lightweight sales-focused CRM with WhatsApp integration via Evolution API v2'
}

// -----------------------------------------------------
// 1. Tenancy & Identity
// -----------------------------------------------------

Table companies {
  id            bigint        [pk, increment]
  name          varchar(255)  [not null]
  created_at    timestamp
  updated_at    timestamp

  Note: 'Tenant root. Created during Business Owner registration.'
}

Table roles {
  id            bigint        [pk, increment]
  name          varchar(100)  [not null]
  slug          varchar(100)  [not null, unique]
  description   varchar(255)
  is_active     boolean       [not null, default: true]
  created_at    timestamp
  updated_at    timestamp

  Note: 'Domain roles. Seeded: business_owner, salesperson.'

  Indexes {
    slug [unique]
  }
}

Table users {
  id                  bigint        [pk, increment]
  company_id          bigint        [not null, ref: > companies.id]
  role_id             bigint        [not null, ref: > roles.id]
  name                varchar(255)  [not null]
  email               varchar(255)  [not null, unique]
  email_verified_at   timestamp
  password            varchar(255)  [not null]
  avatar_path         varchar(2048]
  remember_token      varchar(100)
  is_active           boolean       [not null, default: true]
  must_change_password boolean      [not null, default: false]
  last_login_at       timestamp
  created_at          timestamp
  updated_at          timestamp
  deleted_at          timestamp

  Note: 'All users scoped to one company. Unique email globally.'

  Indexes {
    company_id
    role_id
    email [unique]
    is_active
  }
}

// Laravel default tables (kept for completeness)

Table password_reset_tokens {
  email       varchar(255)  [pk]
  token       varchar(255)  [not null]
  created_at  timestamp
}

Table sessions {
  id              varchar(255)  [pk]
  user_id         bigint        [ref: > users.id]
  ip_address      varchar(45)
  user_agent      text
  payload         longtext      [not null]
  last_activity   integer       [not null]

  Indexes {
    user_id
    last_activity
  }
}

// -----------------------------------------------------
// 2. Invitations
// -----------------------------------------------------

Table invite_statuses {
  id          bigint        [pk, increment]
  name        varchar(100)  [not null]
  slug        varchar(100)  [not null, unique]
  is_active   boolean       [not null, default: true]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: pending, accepted, expired, revoked.'
}

Table invites {
  id                  bigint        [pk, increment]
  company_id          bigint        [not null, ref: > companies.id]
  invited_by_user_id  bigint        [not null, ref: > users.id]
  role_id             bigint        [not null, ref: > roles.id]
  status_id           bigint        [not null, ref: > invite_statuses.id]
  name                varchar(255)  [not null]
  email               varchar(255)  [not null]
  token               varchar(128)  [not null, unique]
  expires_at          timestamp     [not null]
  accepted_at         timestamp
  accepted_user_id    bigint        [ref: > users.id]
  created_at          timestamp
  updated_at          timestamp

  Note: 'Email-link invite flow. Token signed + expiring.'

  Indexes {
    (company_id, email)
    token [unique]
    status_id
    expires_at
  }
}

// -----------------------------------------------------
// 3. Pipeline (fixed stages)
// -----------------------------------------------------

Table pipeline_stages {
  id              bigint        [pk, increment]
  name            varchar(100)  [not null]
  slug            varchar(100)  [not null, unique]
  position        integer       [not null]
  is_terminal     boolean       [not null, default: false]
  is_won          boolean       [not null, default: false]
  is_lost         boolean       [not null, default: false]
  description     varchar(255)
  is_active       boolean       [not null, default: true]
  created_at      timestamp
  updated_at      timestamp

  Note: 'Fixed pipeline. Seeded: new_lead, contacted, proposal_sent, negotiation, won (terminal+won), lost (terminal+lost).'

  Indexes {
    slug [unique]
    position
    is_active
  }
}

// -----------------------------------------------------
// 4. Leads
// -----------------------------------------------------

Table leads {
  id              bigint        [pk, increment]
  company_id      bigint        [not null, ref: > companies.id]
  owner_user_id   bigint        [not null, ref: > users.id]
  name            varchar(255)  [not null]
  email           varchar(255)  [not null]
  phone           varchar(32)
  notes           text
  created_at      timestamp
  updated_at      timestamp
  deleted_at      timestamp

  Note: 'Email unique per company. Phone used for WhatsApp; not unique.'

  Indexes {
    (company_id, email) [unique]
    company_id
    owner_user_id
    phone
  }
}

// -----------------------------------------------------
// 5. Deals
// -----------------------------------------------------

Table deals {
  id              bigint        [pk, increment]
  company_id      bigint        [not null, ref: > companies.id]
  lead_id         bigint        [not null, ref: > leads.id]
  owner_user_id   bigint        [not null, ref: > users.id]
  stage_id        bigint        [not null, ref: > pipeline_stages.id]
  title           varchar(255)  [not null]
  value           decimal(15,2) [not null, default: 0]
  loss_reason     text
  won_at          timestamp
  lost_at         timestamp
  created_at      timestamp
  updated_at      timestamp
  deleted_at      timestamp

  Note: 'loss_reason free-text, required when stage = lost (enforced app-side). Owner inherited from lead.'

  Indexes {
    company_id
    lead_id
    owner_user_id
    stage_id
    (owner_user_id, stage_id)
    won_at
    lost_at
  }
}

Table deal_notes {
  id              bigint        [pk, increment]
  deal_id         bigint        [not null, ref: > deals.id]
  user_id         bigint        [not null, ref: > users.id]
  body            text          [not null]
  created_at      timestamp
  updated_at      timestamp

  Note: 'Internal append-only notes on deals.'

  Indexes {
    deal_id
    user_id
    created_at
  }
}

// -----------------------------------------------------
// 6. Activity History (audit log)
// -----------------------------------------------------

Table activity_types {
  id          bigint        [pk, increment]
  name        varchar(100)  [not null]
  slug        varchar(100)  [not null, unique]
  description varchar(255)
  is_active   boolean       [not null, default: true]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: lead_created, lead_updated, lead_reassigned, deal_created, deal_updated, stage_changed, value_changed, ownership_changed, note_added, message_sent, message_received, deal_won, deal_lost.'
}

Table activities {
  id                  bigint        [pk, increment]
  company_id          bigint        [not null, ref: > companies.id]
  activity_type_id    bigint        [not null, ref: > activity_types.id]
  user_id             bigint        [ref: > users.id]
  lead_id             bigint        [ref: > leads.id]
  deal_id             bigint        [ref: > deals.id]
  before_value        text
  after_value         text
  metadata            json
  created_at          timestamp

  Note: 'Append-only timeline. user_id nullable for system events. metadata holds activity-specific JSON payload.'

  Indexes {
    company_id
    activity_type_id
    user_id
    lead_id
    deal_id
    (deal_id, created_at)
    created_at
  }
}

// -----------------------------------------------------
// 7. WhatsApp Integration (Evolution API v2)
// -----------------------------------------------------

Table whatsapp_connection_statuses {
  id          bigint        [pk, increment]
  name        varchar(100)  [not null]
  slug        varchar(100)  [not null, unique]
  is_active   boolean       [not null, default: true]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: disconnected, pending, connected, failed.'
}

Table whatsapp_connections {
  id                  bigint        [pk, increment]
  user_id             bigint        [not null, unique, ref: > users.id]
  company_id          bigint        [not null, ref: > companies.id]
  status_id           bigint        [not null, ref: > whatsapp_connection_statuses.id]
  instance_name       varchar(191)  [not null, unique]
  phone_number        varchar(32)
  qr_code_path        varchar(2048)
  connected_at        timestamp
  disconnected_at     timestamp
  last_checked_at     timestamp
  webhook_secret      varchar(128)
  created_at          timestamp
  updated_at          timestamp

  Note: 'One Evolution instance per Salesperson. instance_name passed to Evolution API.'

  Indexes {
    user_id [unique]
    company_id
    status_id
    instance_name [unique]
  }
}

Table message_directions {
  id          bigint        [pk, increment]
  name        varchar(50)   [not null]
  slug        varchar(50)   [not null, unique]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: inbound, outbound.'
}

Table message_statuses {
  id          bigint        [pk, increment]
  name        varchar(50)   [not null]
  slug        varchar(50)   [not null, unique]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: pending, sent, delivered, read, failed.'
}

Table message_types {
  id          bigint        [pk, increment]
  name        varchar(50)   [not null]
  slug        varchar(50)   [not null, unique]
  created_at  timestamp
  updated_at  timestamp

  Note: 'Seeded: text, image, audio, video, document, location.'
}

Table messages {
  id                          bigint        [pk, increment]
  company_id                  bigint        [not null, ref: > companies.id]
  whatsapp_connection_id      bigint        [not null, ref: > whatsapp_connections.id]
  lead_id                     bigint        [not null, ref: > leads.id]
  deal_id                     bigint        [ref: > deals.id]
  user_id                     bigint        [ref: > users.id]
  direction_id                bigint        [not null, ref: > message_directions.id]
  status_id                   bigint        [not null, ref: > message_statuses.id]
  message_type_id             bigint        [not null, ref: > message_types.id]
  external_id                 varchar(191)
  body                        text
  media_path                  varchar(2048)
  sent_at                     timestamp
  delivered_at                timestamp
  read_at                     timestamp
  failed_at                   timestamp
  error_message               text
  created_at                  timestamp
  updated_at                  timestamp

  Note: 'WhatsApp messages tied to a lead and optionally a deal. user_id null for inbound. external_id = Evolution message id.'

  Indexes {
    company_id
    whatsapp_connection_id
    lead_id
    deal_id
    user_id
    direction_id
    status_id
    external_id
    (lead_id, created_at)
    (deal_id, created_at)
  }
}

// -----------------------------------------------------
// 8. Laravel Infrastructure (queue, cache, notifications)
// -----------------------------------------------------

Table jobs {
  id              bigint        [pk, increment]
  queue           varchar(255)  [not null]
  payload         longtext      [not null]
  attempts        tinyint       [not null]
  reserved_at     integer
  available_at    integer       [not null]
  created_at      integer       [not null]

  Indexes {
    queue
  }
}

Table failed_jobs {
  id          bigint        [pk, increment]
  uuid        varchar(255)  [not null, unique]
  connection  text          [not null]
  queue       text          [not null]
  payload     longtext      [not null]
  exception   longtext      [not null]
  failed_at   timestamp     [not null, default: `CURRENT_TIMESTAMP`]
}

Table cache {
  key         varchar(255)  [pk]
  value       mediumtext    [not null]
  expiration  integer       [not null]
}

Table cache_locks {
  key         varchar(255)  [pk]
  owner       varchar(255)  [not null]
  expiration  integer       [not null]
}

Table notifications {
  id              char(36)      [pk]
  type            varchar(255)  [not null]
  notifiable_type varchar(255)  [not null]
  notifiable_id   bigint        [not null]
  data            text          [not null]
  read_at         timestamp
  created_at      timestamp
  updated_at      timestamp

  Indexes {
    (notifiable_type, notifiable_id)
    read_at
  }
}
```

---

## Relationship Summary

- `companies` 1—N `users` · `leads` · `deals` · `activities` · `messages` · `whatsapp_connections` · `invites`
- `users` N—1 `roles` (business_owner / salesperson)
- `users` 1—N `leads` (as `owner_user_id`) · `deals` (as `owner_user_id`) · `deal_notes` · `activities`
- `users` 1—1 `whatsapp_connections`
- `leads` 1—N `deals` · `messages`
- `deals` N—1 `pipeline_stages` · `leads`
- `deals` 1—N `deal_notes` · `activities` · `messages`
- `whatsapp_connections` 1—N `messages`
- `invites` N—1 `companies` · `roles` · `invite_statuses` · `users` (inviter, accepter)

---

## Seed Data (Lookups)

| Table | Seeded values |
|-------|---------------|
| `roles` | business_owner, salesperson |
| `pipeline_stages` | new_lead, contacted, proposal_sent, negotiation, won (`is_won`), lost (`is_lost`) |
| `invite_statuses` | pending, accepted, expired, revoked |
| `whatsapp_connection_statuses` | disconnected, pending, connected, failed |
| `message_directions` | inbound, outbound |
| `message_statuses` | pending, sent, delivered, read, failed |
| `message_types` | text, image, audio, video, document, location |
| `activity_types` | lead_created, lead_updated, lead_reassigned, deal_created, deal_updated, stage_changed, value_changed, ownership_changed, note_added, message_sent, message_received, deal_won, deal_lost |

---

## Key Constraints & Application Rules

- `leads.(company_id, email)` unique — enforces lead uniqueness scoped per tenant.
- `users.email` globally unique.
- `whatsapp_connections.user_id` unique — one instance per Salesperson.
- `whatsapp_connections.instance_name` unique — Evolution API requirement.
- `deals.loss_reason` required when `deals.stage_id` resolves to the `lost` stage (enforced via Form Request + Policy).
- `deals.owner_user_id` always equals `leads.owner_user_id` at creation; reassignment cascades from lead to all its deals (handled by service / observer).
- All Eloquent models scoped by `company_id` via global scope to guarantee tenant isolation.
- File/image columns (`avatar_path`, `qr_code_path`, `media_path`) store relative paths under Laravel's storage disk.
