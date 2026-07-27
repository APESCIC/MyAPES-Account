[CmdletBinding()]
param(
    [string]$SourcePath
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Drawing

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
if ([string]::IsNullOrWhiteSpace($SourcePath)) {
    $SourcePath = Join-Path $repositoryRoot 'resources\branding\source\apes-logo-v3.png'
}

$sourceFile = (Resolve-Path $SourcePath).Path
$sourceImage = [System.Drawing.Image]::FromFile($sourceFile)

function New-BrandBitmap {
    param(
        [Parameter(Mandatory)]
        [string]$RelativePath,
        [Parameter(Mandatory)]
        [int]$Width,
        [Parameter(Mandatory)]
        [int]$Height,
        [double]$Scale = 1.0,
        [string]$Background = 'Transparent',
        [ValidateSet('Png', 'Jpeg', 'Icon')]
        [string]$Format = 'Png'
    )

    $destination = Join-Path $repositoryRoot $RelativePath
    $destinationDirectory = Split-Path -Parent $destination
    if (-not (Test-Path $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory | Out-Null
    }

    $bitmap = [System.Drawing.Bitmap]::new(
        $Width,
        $Height,
        [System.Drawing.Imaging.PixelFormat]::Format32bppArgb
    )
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)

    try {
        $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceOver
        $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
        $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
        $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
        $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality

        if ($Background -eq 'Transparent') {
            $graphics.Clear([System.Drawing.Color]::Transparent)
        } else {
            $graphics.Clear([System.Drawing.ColorTranslator]::FromHtml($Background))
        }

        $targetSize = [Math]::Floor([Math]::Min($Width, $Height) * $Scale)
        $targetX = [Math]::Floor(($Width - $targetSize) / 2)
        $targetY = [Math]::Floor(($Height - $targetSize) / 2)
        $graphics.DrawImage($sourceImage, $targetX, $targetY, $targetSize, $targetSize)

        $imageFormat = switch ($Format) {
            'Jpeg' { [System.Drawing.Imaging.ImageFormat]::Jpeg }
            'Icon' { [System.Drawing.Imaging.ImageFormat]::Icon }
            default { [System.Drawing.Imaging.ImageFormat]::Png }
        }

        $bitmap.Save($destination, $imageFormat)
    } finally {
        $graphics.Dispose()
        $bitmap.Dispose()
    }
}

function New-BrandIcon {
    param(
        [Parameter(Mandatory)]
        [string]$PngRelativePath,
        [Parameter(Mandatory)]
        [string]$IconRelativePath
    )

    $pngPath = Join-Path $repositoryRoot $PngRelativePath
    $iconPath = Join-Path $repositoryRoot $IconRelativePath
    $pngBytes = [System.IO.File]::ReadAllBytes($pngPath)
    $stream = [System.IO.File]::Create($iconPath)
    $writer = [System.IO.BinaryWriter]::new($stream)

    try {
        $writer.Write([uint16]0)
        $writer.Write([uint16]1)
        $writer.Write([uint16]1)
        $writer.Write([byte]48)
        $writer.Write([byte]48)
        $writer.Write([byte]0)
        $writer.Write([byte]0)
        $writer.Write([uint16]1)
        $writer.Write([uint16]32)
        $writer.Write([uint32]$pngBytes.Length)
        $writer.Write([uint32]22)
        $writer.Write($pngBytes)
    } finally {
        $writer.Dispose()
        $stream.Dispose()
    }
}

try {
    New-BrandBitmap 'public\branding\logo-myapes-account.png' 1024 1024
    New-BrandBitmap 'public\branding\email-header-logo.png' 600 600
    New-BrandBitmap 'public\branding\login-hero.png' 1200 630 0.92 '#021b20' 'Jpeg'

    New-BrandBitmap 'public\logos\myapes-badge-512x512.png' 512 512
    New-BrandBitmap 'public\logos\myapes-mark-256x256.png' 256 256
    New-BrandBitmap 'public\logos\myapes-mark-128x128.png' 128 128

    New-BrandBitmap 'public\icons\app-icon-1024x1024.png' 1024 1024
    New-BrandBitmap 'public\icons\apple-touch-icon.png' 180 180
    New-BrandBitmap 'public\icons\mstile-150x150.png' 150 150
    New-BrandBitmap 'public\icons\pwa-192x192.png' 192 192
    New-BrandBitmap 'public\icons\pwa-512x512.png' 512 512
    New-BrandBitmap 'public\icons\pwa-maskable-192x192.png' 192 192 0.80 '#021b20'
    New-BrandBitmap 'public\icons\pwa-maskable-512x512.png' 512 512 0.80 '#021b20'

    New-BrandBitmap 'public\favicons\favicon-16x16.png' 16 16
    New-BrandBitmap 'public\favicons\favicon-32x32.png' 32 32
    New-BrandBitmap 'public\favicons\favicon-48x48.png' 48 48
    New-BrandBitmap 'public\favicons\favicon-96x96.png' 96 96
    New-BrandBitmap 'public\favicon-16x16.png' 16 16
    New-BrandBitmap 'public\favicon-32x32.png' 32 32
    New-BrandBitmap 'public\favicon-48x48.png' 48 48
    New-BrandIcon 'public\favicon-48x48.png' 'public\favicon.ico'

    New-BrandBitmap 'public\android-chrome-192x192.png' 192 192
    New-BrandBitmap 'public\android-chrome-512x512.png' 512 512
    New-BrandBitmap 'public\apple-touch-icon.png' 180 180
    New-BrandBitmap 'public\pwa-maskable-512x512.png' 512 512 0.80 '#021b20'

    New-BrandBitmap 'public\social\og-image-1200x630.jpg' 1200 630 0.95 '#021b20' 'Jpeg'
    New-BrandBitmap 'public\og-image.png' 1200 630 0.95 '#021b20'
} finally {
    $sourceImage.Dispose()
}

Write-Host "Generated MyAPES brand assets from $sourceFile"
