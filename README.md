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
- `/login` and `/register` - public account authentication
- `/staff/login` - dedicated staff login page that starts Cloudron OIDC

## Brand assets (MyAPES Account variant)

- Source master used for derivation: `resources/branding/source/apes-logo-v2.png`
- Generated assets directory: `public/branding/`

| Asset | Size | Purpose |
| --- | --- | --- |
| `public/branding/logo-myapes-account.png` | 1024x1194 | Main APES logo variant with MyAPES Account sub-brand tag |
| `public/branding/logo-myapes-account-horizontal.png` | 1600x700 | Horizontal lockup for headers and staff sign-in |
| `public/branding/logo-myapes-account-stacked.png` | 720x830 | Stacked lockup for constrained layouts |
| `public/branding/login-hero.png` | 1600x600 | Landing page hero image |
| `public/branding/email-header-logo.png` | 600x120 | Email header logo |
| `public/branding/logo-myapes-account-mono-dark.png` | 1024x1194 | Monochrome dark variant |
| `public/branding/logo-myapes-account-mono-light.png` | 1024x1194 | Monochrome light variant |
| `public/branding/logo-myapes-account-print-light.png` | 1024x1194 | Print-ready variant for light backgrounds |
| `public/branding/logo-myapes-account-print-dark.png` | 1024x1194 | Print-ready variant for dark backgrounds |

### App icon and preview set

| Asset | Size | Purpose |
| --- | --- | --- |
| `public/favicon.ico` | 64x64 (ico) | Browser tab icon |
| `public/favicon-16x16.png` | 16x16 | Browser favicon |
| `public/favicon-32x32.png` | 32x32 | Browser favicon |
| `public/favicon-48x48.png` | 48x48 | Browser/favicon fallback |
| `public/apple-touch-icon.png` | 180x180 | iOS home-screen icon |
| `public/android-chrome-192x192.png` | 192x192 | Android/PWA icon |
| `public/android-chrome-512x512.png` | 512x512 | Android/PWA icon |
| `public/pwa-maskable-512x512.png` | 512x512 | Maskable PWA icon |
| `public/og-image.png` | 1200x630 | Social sharing preview image |
| `public/site.webmanifest` | n/a | PWA icon manifest |

## Local environment setup

Run the bootstrap script from the repository root. It installs PHP/Node dependencies, ensures `.env` exists, generates the app key, and runs migrations.

### macOS / Linux

```bash
bash scripts/local/bootstrap.sh
```

### Windows PowerShell

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local\bootstrap.ps1
```

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
