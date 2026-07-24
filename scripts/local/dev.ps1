$ErrorActionPreference = "Stop"

$RootDir = Resolve-Path (Join-Path $PSScriptRoot "..\..")
Set-Location $RootDir

if (-not (Test-Path ".\artisan")) {
    throw "artisan was not found in $RootDir. Run this inside the Laravel project root."
}

if (-not (Test-Path ".\composer.json")) {
    throw "composer.json was not found in $RootDir. Run this inside the Laravel project root."
}

$composerScripts = composer run --no-ansi 2>$null
$hasDevScript = (($composerScripts -split "`n") | Where-Object { $_ -match "^\s*dev(\s|$)" }).Count -gt 0
if ($hasDevScript) {
    composer run dev
} else {
    Write-Host "No composer 'dev' script found. Starting Laravel server only."
    $port = if ($env:APP_PORT) { $env:APP_PORT } else { "8000" }
    php artisan serve --host=127.0.0.1 --port=$port
}
