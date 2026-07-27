$ErrorActionPreference = "Stop"

$RootDir = Resolve-Path (Join-Path $PSScriptRoot "..\..")
Set-Location $RootDir

if (-not (Test-Path ".\artisan")) {
    throw "artisan was not found in $RootDir. Run this inside the Laravel project root."
}

if (-not (Test-Path ".\composer.json")) {
    throw "composer.json was not found in $RootDir. Run this inside the Laravel project root."
}

if (-not (Test-Path ".\scripts\local\dev-runner.mjs")) {
    throw "scripts/local/dev-runner.mjs was not found in $RootDir."
}

composer run dev
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}
