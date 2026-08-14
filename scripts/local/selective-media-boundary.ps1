[CmdletBinding()]
param(
    [string]$RootDir,
    [switch]$CreateAvatarLink
)

$ErrorActionPreference = "Stop"

function Assert-OrdinaryDirectory {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$Path,
        [Parameter(Mandatory = $true)]
        [string]$Description
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Container)) {
        throw "$Description is missing or is not a directory: $Path"
    }
    $item = Get-Item -LiteralPath $Path -Force
    if (($item.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
        throw "$Description must not contain a reparse point: $Path"
    }
}

function Assert-SelectiveMediaBoundary {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]
        [string]$RootDir,
        [switch]$CreateAvatarLink
    )

    $root = [System.IO.Path]::GetFullPath($RootDir)
    $publicRoot = Join-Path $root "public"
    $publicStorage = Join-Path $root "public\storage"
    $mediaMarker = Join-Path $publicStorage ".myapes-selective-media"
    $avatarLink = Join-Path $publicStorage "avatars"
    $storageRoot = Join-Path $root "storage"
    $storageApp = Join-Path $storageRoot "app"
    $storagePublic = Join-Path $storageApp "public"
    $avatarTarget = Join-Path $root "storage\app\public\avatars"

    Assert-OrdinaryDirectory -Path $root -Description "Application root"
    Assert-OrdinaryDirectory -Path $publicRoot -Description "Public root"
    Assert-OrdinaryDirectory -Path $publicStorage -Description "Selective public storage boundary"
    if (-not (Test-Path -LiteralPath $mediaMarker -PathType Leaf)) {
        throw "Selective-media marker is missing: $mediaMarker"
    }
    $mediaMarkerItem = Get-Item -LiteralPath $mediaMarker -Force
    if (($mediaMarkerItem.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -ne 0) {
        throw "Selective-media marker must not be a reparse point: $mediaMarker"
    }
    $expectedMarkerBytes = [System.Text.Encoding]::UTF8.GetBytes("myapes-selective-media:v1`n")
    $actualMarkerBytes = [System.IO.File]::ReadAllBytes($mediaMarker)
    if ([System.Convert]::ToBase64String($actualMarkerBytes) -cne [System.Convert]::ToBase64String($expectedMarkerBytes)) {
        throw "Selective-media marker content is unexpected."
    }
    $unexpectedEntries = @(Get-ChildItem -LiteralPath $publicStorage -Force | Where-Object {
        $_.Name -notin @(".myapes-selective-media", "avatars")
    })
    if ($unexpectedEntries.Count -gt 0) {
        throw "Selective public storage contains an unexpected entry: $($unexpectedEntries[0].FullName)"
    }

    Assert-OrdinaryDirectory -Path $storageRoot -Description "Shared storage root"
    Assert-OrdinaryDirectory -Path $storageApp -Description "Shared storage app directory"
    Assert-OrdinaryDirectory -Path $storagePublic -Description "Shared public storage directory"
    if (-not (Test-Path -LiteralPath $avatarTarget)) {
        [System.IO.Directory]::CreateDirectory($avatarTarget) | Out-Null
    }
    Assert-OrdinaryDirectory -Path $avatarTarget -Description "Shared avatars target"
    if (-not (Test-Path -LiteralPath $avatarLink) -and -not (Test-Path -LiteralPath $avatarLink -PathType Leaf)) {
        if (-not $CreateAvatarLink) {
            throw "Avatar public storage link is missing: $avatarLink"
        }

        Push-Location $root
        try {
            & php artisan storage:link
            if ($LASTEXITCODE -ne 0) {
                throw "Avatar public storage link creation failed with exit code $LASTEXITCODE."
            }
        } finally {
            Pop-Location
        }
    }

    $avatarLinkItem = Get-Item -LiteralPath $avatarLink -Force
    if (($avatarLinkItem.Attributes -band [System.IO.FileAttributes]::ReparsePoint) -eq 0) {
        throw "Avatar public storage path is not a link: $avatarLink"
    }
    $rawAvatarTargets = @($avatarLinkItem.Target)
    if ($rawAvatarTargets.Count -ne 1 -or [string]::IsNullOrWhiteSpace([string]$rawAvatarTargets[0])) {
        throw "Avatar public storage link has an unexpected target shape."
    }
    $rawAvatarTarget = [string]$rawAvatarTargets[0]
    $lexicalAvatarTarget = if ([System.IO.Path]::IsPathRooted($rawAvatarTarget)) {
        [System.IO.Path]::GetFullPath($rawAvatarTarget)
    } else {
        [System.IO.Path]::GetFullPath((Join-Path $avatarLinkItem.DirectoryName $rawAvatarTarget))
    }
    if ($lexicalAvatarTarget -cne [System.IO.Path]::GetFullPath($avatarTarget)) {
        throw "Avatar public storage link targets an unexpected path: $lexicalAvatarTarget"
    }
}

if ($MyInvocation.InvocationName -ne '.') {
    Assert-SelectiveMediaBoundary -RootDir $RootDir -CreateAvatarLink:$CreateAvatarLink
}
