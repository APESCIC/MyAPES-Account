# Core layer

Owns platform concerns that never depend on a Module or Plugin package.

## Present after Wave 1 (#282 / #283 / #287)

- `App\Core\Auth` — OIDC identity types
- `App\Core\Accounts` — User, profiles, Access models, audit log
- `App\Core\Attachments` — shared `SupportAttachment`
- `App\Core\Maintenance` — maintenance window model
- `App\Core\Extensions` — module/plugin manifests, registries, navigation contracts, enablement models
- `App\Core\Eloquent\MorphMap` — stable morph aliases (#281)
- `App\Core\Providers` — `CoreServiceProvider`, `CoreAuthServiceProvider`, `CoreAccessServiceProvider`, `CoreAdminServiceProvider`

Plugin feature models (tickets, cases, recruitment, consultations, pet profiles) remain under `App\Models` until Waves 5–7 move them into `plugins/<slug>`.
