# MyAPES Account

MyAPES Account is the APES CIC service-user and staff portal built on Laravel for Cloudron LAMP deployments.

## Repository status

- Last verified README health review: `2026-07-28T10:24:40+01:00`
- Source-controlled application version: [`VERSION`](VERSION)
- Continuous integration and guarded deployment: [Test and deploy MyAPES Account](https://github.com/APESCIC/MyAPES-Account/actions/workflows/deploy-cloudron.yml)
- Public release history: [MyAPES Account Change Log](https://myaccount.myapes.me.uk/change-log)

## Support and maintainers

- [Report a bug](https://github.com/APESCIC/MyAPES-Account/issues/new?template=bug_report.yml)
- [Request a feature](https://github.com/APESCIC/MyAPES-Account/issues/new?template=feature_request.yml)
- [Browse existing issues](https://github.com/APESCIC/MyAPES-Account/issues)
- Maintained by [APES CIC](https://github.com/APESCIC) with repository administration by [bmurphy-apescic](https://github.com/bmurphy-apescic).

Do not disclose suspected security vulnerabilities in a public issue. This repository does not currently advertise a private vulnerability-reporting route; repository administrators must establish one before inviting external security reports.

## Core architecture

- **Authentication**:
  - Public service users: local email/password register + login
  - Staff/admin users: APES Cloudron OIDC login with LDAP group resolution for role assignment
- **Role mapping**:
  - Staff access: `position.staff`, `position.students`, `position.volunteers`
  - Admin access: `intranet.administrator`
  - Superadmin access: `intranet.superadmin`
- **Access compatibility**:
  - `identity_type` distinguishes local accounts from Cloudron OIDC identities.
  - Application access reads use `legacy_access_level`; writes also update `users.role` while that column exists.
  - This is the Phase A compatibility layer. Granular permissions and protected RBAC roles are not installed yet.
- **Core app features**: account dashboard, profile/settings, role-aware navigation, media uploads.
- **Service subsections**:
  - **APES CIC** (`/apes-cic`) - organisational support tickets
  - **APES Shelter and Rescue** (`/shelter`) - pet profiles and case management
  - **APES Pet Care** (`/petcare`) - pet profiles and consultation management
- **Cloudron service integrations**: MySQL, Redis (cache/session/queue), and sendmail-compatible SMTP delivery.

### Login entry points

- `/` - landing page with Public Login, Register, and Staff Login choices
- `/login` and `/register` - public account authentication (in local/testing, `/login` auto-signs in to the seeded public user)
- `/staff/login` - dedicated staff login page that starts Cloudron OIDC
- `/change-log` - public, searchable MyAPES Account release history for guests, public users, and staff
- local/testing only: QA role switcher (Public/Staff/Admin) available in the app layout for one-click identity switching

## Brand assets

- Canonical artwork: `resources/branding/source/apes-logo-v3.png`
- Regenerate application derivatives on Windows with:

```powershell
pwsh -NoProfile -File .\scripts\branding\generate-brand-assets.ps1
```

The generator preserves the supplied square animal artwork for the sidebar and visible brand marks, creates padded maskable icons, and places the artwork on the dark MyAPES surface for social previews.

### App-visible logo

| Asset | Purpose |
| --- | --- |
| `public/branding/logo-myapes-account.png` | Shared desktop sidebar, mobile header, landing, and staff-login logo |
| `public/branding/email-header-logo.png` | Square email/header-safe logo export |
| `public/branding/login-hero.png` | Wide dark-surface brand export |
| `public/logos/myapes-mark-128x128.png` | Compact raster mark |
| `public/logos/myapes-mark-256x256.png` | Compact high-density raster mark |

### Bearded dragon mascot

| Asset | Purpose |
| --- | --- |
| `public/mascot/bearded-dragon-natural.png` | Photorealistic dashboard and landing portrait generated for the approved Naturalist Notebook design |

The portrait is intentionally unnamed and is presented as a realistic animal rather than an anthropomorphic character.

The interface uses a light-first Naturalist Notebook theme with a persistent deep-teal desktop sidebar and an accessible mobile drawer. A saved colour-theme choice is stored locally in the browser as `myapes-theme`; the first visit always starts in light mode.
### Browser, PWA, and social assets

| Asset | Purpose |
| --- | --- |
| `public/favicon.ico` | Multi-resolution browser favicon |
| `public/favicons/favicon-16x16.png` | 16px favicon |
| `public/favicons/favicon-32x32.png` | 32px favicon |
| `public/favicons/favicon-48x48.png` | 48px favicon |
| `public/favicons/safari-pinned-tab.svg` | Safari pinned-tab icon |
| `public/icons/apple-touch-icon.png` | iOS home-screen icon |
| `public/icons/pwa-192x192.png` | PWA install icon |
| `public/icons/pwa-512x512.png` | PWA install icon |
| `public/icons/pwa-maskable-192x192.png` | Maskable PWA icon |
| `public/icons/pwa-maskable-512x512.png` | Maskable PWA icon |
| `public/social/og-image-1200x630.jpg` | Open Graph / social preview image |
| `public/site.webmanifest` | Web app manifest |
| `public/browserconfig.xml` | Microsoft tile metadata |

## Local environment setup

Run the bootstrap script from the repository root. It installs PHP/Node dependencies, creates a local `.env` from `.env.local.example`, configures SQLite plus file-backed cache/sessions, generates the app key once, migrates, seeds, and builds the frontend.

The script refuses to rewrite an environment unless `APP_ENV` is already `local` or `testing`. Local development does not require MySQL or Redis.

### One-command local QA bootstrap (deterministic test data)

Use a fresh migration+seed reset when you need the same test accounts and records every time.

### macOS / Linux

```bash
bash scripts/local/bootstrap.sh --fresh
```

### Windows PowerShell

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\bootstrap.ps1 -Fresh
```

The `--fresh` / `-Fresh` option is destructive for local data (`migrate:fresh --seed`) and is intended for QA resets.

If you need non-destructive seeding, use:

```bash
bash scripts/local/bootstrap.sh --seed
```

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\bootstrap.ps1 -Seed
```

### Seeded local QA accounts

All seeded users use this password:

- `MyAPES-Local-QA-2026!`

In local/testing, opening `/login` immediately signs into the seeded public account. Use the in-app QA role switcher to move between Public, Staff, and Admin without re-entering credentials.

| Role | Login email | Login route | Primary QA coverage |
| --- | --- | --- | --- |
| Public service user | `qa.service.user@myapes.local` | `/login` (auto-login) or QA switcher | Public dashboard, profile/settings, APES CIC tickets, shelter pets/cases, pet care pets/consultations (owner-scoped views) |
| Staff | `qa.staff@myapes.local` | QA switcher or `/staff/login` (local direct form) | Staff visibility across all user records, assignment updates, staff notes and status workflows |
| Admin | `qa.admin@myapes.local` | QA switcher or `/staff/login` (local direct form) | All staff workflows plus `/admin` dashboard and admin navigation access |
| Superadmin (optional extra coverage) | `qa.superadmin@myapes.local` | `/staff/login` (local direct login form) | Same as admin with superadmin role mapping coverage |

### Feature test matrix by seeded role

| Role | Key flows to validate quickly |
| --- | --- |
| Public | Create/view own APES CIC tickets, update own shelter/pet care records, edit profile/settings, verify owner-only visibility |
| Staff | See all users' tickets/cases/consultations, assign records to staff/admin, update statuses and staff notes |
| Admin | Run full staff workflows plus access `/admin` summary and confirm admin navigation/permissions |

### Seeded data included for quick E2E checks

- APES CIC tickets: two open/in-progress examples plus a resolved example with message history and staff/admin assignment
- Shelter: seeded pet profile with two open/in-review cases plus a closed example
- Pet Care: seeded pet profile with two open/in-progress consultations plus a closed example
- User profiles: seeded profile data for each QA account

Start Laravel, the queue listener, application logs, and Vite together with the cross-platform Composer script:

```powershell
composer run dev
```

The launcher automatically uses Laravel Pail for logs on macOS and Linux. On native Windows it follows `storage/logs/laravel.log` with PowerShell instead, because PHP's Unix-only `pcntl` extension is not available there; do not try to install `pcntl` on native Windows.

Set `APP_PORT` before starting the launcher to use a Laravel port other than `8000`. The platform wrappers below delegate to `composer run dev` and remain available when you prefer an OS-specific entry point:

### macOS / Linux

```bash
bash scripts/local/dev.sh
```

### Windows PowerShell

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\dev.ps1
```

## Release history and semantic versioning

The current application version is stored in the root `VERSION` file without a display-only `v` prefix. Reviewed public release records live in `resources/data/releases.json`; the newest record is authoritative and must match `VERSION`. The shared footer links the displayed version to the public `/change-log` hub.

Every future change merged to `main` must:

1. Prepend exactly one higher semantic version to `resources/data/releases.json`.
2. Update `VERSION` to that same version.
3. Leave every previously published record unchanged and in the same order.
4. Use a minor version for a new backward-compatible capability and a patch version for a compatible fix. While the application remains pre-1.0, document a breaking change explicitly and advance the minor version.
5. Keep public notes free of credentials, personal data, private operational identifiers, exploitable security detail, and unnecessary infrastructure detail.

Validate the current structure locally:

```powershell
php artisan myapes:changelog-validate
```

Compare a proposed release against the current remote main branch:

```powershell
git fetch origin
php artisan myapes:changelog-validate --base-ref=origin/main
```

Pull-request and `main` workflows perform the same append-only comparison. Manual workflow dispatch performs structural validation without requiring another version. This contract does not create or backfill Git tags or GitHub Releases.

## Cloudron deployment automation

Deployments are handled by `.github/workflows/deploy-cloudron.yml`.

### Deployment triggers

1. Push to `main` (automatic deployment after tests).
2. Manual deployment request via **Actions → Test and deploy MyAPES Account → Run workflow**. Manual runs always deploy the current `main` revision.

### Target app

- Cloudron app ID: `3465c63f-0e1b-4e49-8f5f-799a471055a1`

### Required GitHub secrets

| Secret | Description |
| --- | --- |
| `CLOUDRON_FQDN` | Cloudron dashboard domain used by the CLI |
| `CLOUDRON_TOKEN` | Personal Cloudron API token with permission to back up, push to, execute in, and restart the target app |

Create these as secrets in the GitHub environment named `cloudron-deploy`. Do not commit either value.

### Staff OIDC and LDAP setup

Create a one-time Cloudron OpenID client named **MyAPES Account** in
**Users → OpenID** with this callback:

`https://myaccount.myapes.me.uk/staff/auth/callback`

Use the issuer `https://my.cloudron.apes.org.uk/openid` and the
`openid profile email` scopes. Store `OIDC_ISSUER`, `OIDC_CLIENT_ID`,
`OIDC_CLIENT_SECRET`, and `OIDC_REDIRECT_URI` only in the Cloudron app
environment. The generated credentials must not be copied into GitHub,
the repository, release archives, logs, or chat.

Configure Cloudron app access for these directory groups:

- `position.staff`
- `position.students`
- `position.volunteers`
- `intranet.administrator`
- `intranet.superadmin`

The LAMP package injects rotating `CLOUDRON_LDAP_*` credentials. MyAPES uses
OIDC for authentication and LDAP membership for authorization; LDAP credentials
must not be copied into the Laravel environment file.

Before activation, production runs:

```bash
php artisan myapes:auth-check --no-interaction
```

The command validates the OIDC discovery contract, PKCE S256 support, LDAP
connectivity and all five role groups without printing client, bind or user
data. An authentication-readiness failure occurs before migrations and leaves
the previous release active.

Directory-backed sessions revalidate role membership at most every five
minutes. Removing all approved groups downgrades and signs out the user;
directory outages fail closed without changing the last stored role. Explicit
staff logout clears the MyAPES session and forces a fresh Cloudron credential
prompt on the next Staff Login. It does not end other Cloudron sessions because
the provider does not publish a global logout endpoint.

### Access compatibility synchronization

Phase A keeps the previous `users.role` field for rollback compatibility while
all application reads use `legacy_access_level`. The additive migration installs
database triggers on SQLite, MySQL, and MariaDB so legacy inserts and updates
continue to synchronize `legacy_access_level` and `identity_type` throughout
cutover and rollback. Unsupported, null, or blank legacy access levels are
rejected before they can reach the compatibility fields.

Run the idempotent reconciliation check after migrations and immediately before
activating a release:

```bash
php artisan myapes:access-compatibility-sync --no-interaction
```

The command fails without printing user data when required compatibility
fields are missing, the database guard is absent, unsupported legacy access
values exist, or reconciliation does not reach its postcondition. It
synchronizes the additive fields while `users.role` exists and becomes a
successful no-op after Phase B removes that column. Phase B must drop the
transitional database guard before dropping `users.role`.

### Deployment flow (every deployment request)

1. CI checks out the exact revision, validates the source-controlled release history, installs PHP 8.4 and Node 22 dependencies, runs the compatibility migration and command tests against MySQL plus the complete SQLite and frontend suites, validates shell scripts, and builds Vite assets.
2. CI creates an immutable production archive containing Composer production dependencies, built assets, `VERSION`, and a `REVISION` file.
3. A pre-deployment Cloudron backup is created.
4. The pinned Cloudron CLI uploads the archive into `/app/data/.deploy/<sha>`.
5. The activation script extracts to `/app/data/releases/<sha>`, links shared `.env` and Laravel storage, validates OIDC/LDAP readiness, runs migrations and cache warm-up, verifies the database compatibility guard, reconciles access data and its postcondition, then atomically updates `/app/data/current`.
6. Cloudron restarts with Apache serving `/app/data/current/public`; the queue worker and scheduler start from `/app/data/run.sh`.
7. CI verifies `/healthz` reports healthy dependencies, the expected semantic application version, and the exact deployed commit, then checks that Staff Login redirects to the expected Cloudron authorization endpoint with PKCE S256. Failed verification restores the previous code release and leaves the backup available for database recovery.

`version` in `/healthz` is the human-facing semantic application version. `release` is the immutable deployment commit SHA; neither replaces the other.

Rotating MySQL, Redis, SMTP, and LDAP credentials are read from Cloudron-provided environment variables and are not copied into the shared `.env`.

## Security and audit controls

- **Audit log model** records security-sensitive events such as:
  - OIDC login/logout and role access denials
  - profile updates
  - ticket/case/consultation lifecycle updates
  - pet profile create/update actions
- **Audit retention control**:
  - Configure `AUDIT_LOG_RETENTION_DAYS` (default `180`)
  - Run `php artisan audit:prune` to remove expired audit records
  - The scheduler is configured to run pruning daily
- **Session hardening defaults** in `.env.example`:
  - `SESSION_ENCRYPT=true`
  - `SESSION_SECURE_COOKIE=true` in the template for HTTPS deployments; local bootstrap rewrites the copied `.env` to `false` for localhost QA
  - `SESSION_HTTP_ONLY=true`
- **Upload safeguards**:
  - Avatar and pet photo uploads are restricted to JPEG/PNG/WebP
  - File replacement removes superseded media files to reduce stale exposure

## Data model highlights

- `users`: identity source, transitional legacy access level, rollback-compatible role and database guard, and LDAP groups
- `user_profiles`: shared profile/settings data
- `support_tickets` + `support_ticket_messages`: APES CIC support workflows
- `pet_profiles`: shared pet record model (`shelter` or `petcare` domain)
- `shelter_cases`: adoption/surrender/rescue/fostering tracking
- `pet_care_consultations`: consultation lifecycle tracking
