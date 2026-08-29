$ErrorActionPreference = "Stop"

$RootDir = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
Set-Location $RootDir

$env:LARAGON = "1"
node scripts/local/dev-runner.mjs
