$ErrorActionPreference = "Stop"

$RootDir = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$LogDir = Join-Path $RootDir "storage\logs"
$LogPath = Join-Path $LogDir "laravel.log"

if (-not (Test-Path -LiteralPath $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

if (-not (Test-Path -LiteralPath $LogPath)) {
    New-Item -ItemType File -Path $LogPath -Force | Out-Null
}

Get-Content -LiteralPath $LogPath -Tail 20 -Wait
