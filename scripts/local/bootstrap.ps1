$ErrorActionPreference = "Stop"

$RootDir = Resolve-Path (Join-Path $PSScriptRoot "..\..")
Set-Location $RootDir

foreach ($tool in @("php", "composer", "npm")) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        throw "Missing required tool: $tool"
    }
}

if (-not (Test-Path ".\artisan")) {
    throw "artisan was not found in $RootDir. Run this inside the Laravel project root."
}

if (-not (Test-Path ".\composer.json")) {
    throw "composer.json was not found in $RootDir. Run this inside the Laravel project root."
}

if (-not (Test-Path ".\.env")) {
    if (Test-Path ".\.env.example") {
        Copy-Item ".\.env.example" ".\.env"
        Write-Host "Created .env from .env.example"
    } else {
        throw "Neither .env nor .env.example exists."
    }
}

composer install --no-interaction --prefer-dist
npm install --no-audit --no-fund
php artisan key:generate --force
php artisan migrate --force

Write-Host "Local bootstrap complete."
