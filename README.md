# MyAPES-Account

Laravel application with automated Cloudron deployment.

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
