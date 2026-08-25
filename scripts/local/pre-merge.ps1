param(
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))

Write-Host 'Fetching origin/main...'
git fetch origin

Write-Host 'Validating release history...'
php artisan myapes:changelog-validate --base-ref=origin/main
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host 'Running version-pinned and contract tests...'
php artisan test `
    tests/Feature/ReleaseHistoryCommandTest.php `
    tests/Feature/ChangeLogPageTest.php `
    tests/Feature/HealthAndThemeTest.php `
    tests/Feature/ModuleRollbackCompatibilityTest.php `
    tests/Feature/ProductionUpgradePreflightTest.php
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host 'Running frontend tests...'
npm run test:frontend
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

if (-not $SkipBuild) {
    Write-Host 'Building frontend assets...'
    npm run build
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

Write-Host 'Pre-merge checks passed.'
