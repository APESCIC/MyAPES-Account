# MyAPES Account

MyAPES Account is the APES CIC service-user and staff portal built on Laravel for Cloudron LAMP deployments.

## Core architecture

- **Authentication**:
  - Public service users: local email/password register + login
  - Staff/admin users: APES Cloudron OIDC login with LDAP group resolution for role assignment
- **Role mapping**:
  - Staff access: `position.staff`, `position.students`, `position.volunteers`
  - Admin access: `admin`, `superadmin`
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
- local/testing only: QA role switcher (Public/Staff/Admin) available in the app layout for one-click identity switching

## Brand assets (MyAPES Account logo pack)

- Source pack location: `C:\Users\bmurp\Documents\Codex\2026-07-24\c\outputs\myapes-web-app-logo-pack`
- App-integrated assets are copied into `public/` with their pack folder structure.

### Header and app-visible logo

| Asset | Purpose |
| --- | --- |
| `public/logos/myapes-header-dark.svg` | Primary header/landing/staff-login logo on dark surfaces |
| `public/logos/myapes-header-light.svg` | Light-surface header logo variant |
| `public/logos/myapes-header-dark-600x128.png` | Raster fallback where SVG is unavailable |
| `public/logos/myapes-header-light-600x128.png` | Light raster fallback where SVG is unavailable |

### Mascot assets (Ember the bearded dragon)

| Asset | Purpose |
| --- | --- |
| `public/mascot/beardie-wave.svg` | Shared mascot badge used in global header, banner, and auth/admin highlights |
| `public/mascot/beardie-cozy.svg` | Wider hero illustration used on landing/dashboard-style surfaces |

The app now uses a mascot-led playful theme globally (public, staff, and admin surfaces) while preserving role clarity and workflow readability.

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

Run the bootstrap script from the repository root. It installs PHP/Node dependencies, ensures `.env` exists, generates the app key, and runs migrations.

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

- APES CIC tickets: open/in-progress and resolved examples with message history and staff/admin assignment
- Shelter: seeded pet profile with in-review and closed case examples
- Pet Care: seeded pet profile with in-progress and closed consultation examples
- User profiles: seeded profile data for each QA account

Start local development:

### macOS / Linux

```bash
bash scripts/local/dev.sh
```

### Windows PowerShell

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\dev.ps1
```

## Cloudron deployment automation

Deployments are handled by `.github/workflows/deploy-cloudron.yml`.

### Deployment triggers

1. Push to `main` (automatic deployment).
2. Manual deployment request via **Actions → Deploy to Cloudron → Run workflow**, optionally overriding the git ref.

### Target app

- Cloudron app ID: `3465c63f-0e1b-4e49-8f5f-799a471055a1`

### Required GitHub secrets

| Secret | Description |
| --- | --- |
| `CLOUDRON_SSH_HOST` | Cloudron server hostname or IP |
| `CLOUDRON_SSH_USER` | SSH user with permission to run docker/cloudron commands |
| `CLOUDRON_SSH_PRIVATE_KEY` | Private key used by GitHub Actions to SSH into Cloudron |

### Optional GitHub secrets / variables

| Name | Type | Default | Purpose |
| --- | --- | --- | --- |
| `CLOUDRON_SSH_PORT` | Secret | `22` | SSH port |
| `CLOUDRON_APP_CODE_PATH` | Variable | `/app/code` | Path inside the app container where Laravel code lives |
| `CLOUDRON_APP_CONTAINER` | Variable | auto-detected | Explicit container ID/name if label lookup is not available |
| `CLOUDRON_RESTART_STRATEGY` | Variable | `auto` | `auto`, `cloudron-cli`, or `docker` |

### Deployment flow (every deployment request)

1. CI checks all required secrets are present.
2. CI uploads `scripts/deploy/cloudron-remote-deploy.sh` to the Cloudron server.
3. Remote deployment script resolves the app container for app ID `3465c63f-0e1b-4e49-8f5f-799a471055a1`.
4. Inside the app container, it runs:
   - `git fetch --all --prune`
   - `git fetch --tags`
   - checkout the requested ref (origin branch or tag; fails if missing)
   - `composer install --no-dev --optimize-autoloader --no-interaction`
   - `php artisan migrate --force`
   - cache clear/rebuild commands
5. Script restarts the app (`cloudron restart --app <id>` when available, else docker restart fallback).
6. Any missing configuration or command failure exits non-zero and fails the deployment run.

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
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_HTTP_ONLY=true`
- **Upload safeguards**:
  - Avatar and pet photo uploads are restricted to JPEG/PNG/WebP
  - File replacement removes superseded media files to reduce stale exposure

## Data model highlights

- `users`: OIDC identity, role, and LDAP groups
- `user_profiles`: shared profile/settings data
- `support_tickets` + `support_ticket_messages`: APES CIC support workflows
- `pet_profiles`: shared pet record model (`shelter` or `petcare` domain)
- `shelter_cases`: adoption/surrender/rescue/fostering tracking
- `pet_care_consultations`: consultation lifecycle tracking
