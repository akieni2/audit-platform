# Build PDF guides from Markdown via HTML + Microsoft Edge headless.
# Usage: powershell -ExecutionPolicy Bypass -File docs/tools/build-guides-pdf.ps1

$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..\..")
$PdfDir = Join-Path $Root "docs\pdf"
$HtmlDir = Join-Path $PdfDir "_html"

$NodeCandidates = @(
    "$env:ProgramFiles\nodejs\node.exe",
    "C:\Program Files\nodejs\node.exe",
    "$env:LOCALAPPDATA\Programs\cursor\resources\app\resources\helpers\node.exe"
)

$Node = $NodeCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $Node) {
    throw "Node.js introuvable. Installez Node.js ou ouvrez le projet dans Cursor."
}

$Edge = "${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe"
if (-not (Test-Path $Edge)) {
    $Edge = "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
}
if (-not (Test-Path $Edge)) {
    throw "Microsoft Edge introuvable."
}

New-Item -ItemType Directory -Force -Path $PdfDir | Out-Null
New-Item -ItemType Directory -Force -Path $HtmlDir | Out-Null

Write-Host "Conversion Markdown -> HTML..."
& $Node (Join-Path $PSScriptRoot "md-to-html.mjs")
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

$HtmlFiles = Get-ChildItem -Path $HtmlDir -Filter "*.html" | Sort-Object Name
Write-Host "Generation PDF ($($HtmlFiles.Count) fichiers)..."

foreach ($html in $HtmlFiles) {
    $pdfName = [System.IO.Path]::ChangeExtension($html.BaseName, ".pdf")
    $pdfPath = Join-Path $PdfDir $pdfName
    $uri = [System.Uri]::new($html.FullName).AbsoluteUri

    if (Test-Path $pdfPath) {
        Remove-Item $pdfPath -Force
    }

    $edgeArgs = @(
        '--headless=new',
        '--disable-gpu',
        '--no-pdf-header-footer',
        "--print-to-pdf=$pdfPath",
        $uri
    )
    $proc = Start-Process -FilePath $Edge -ArgumentList $edgeArgs -Wait -PassThru -WindowStyle Hidden
    if ($proc.ExitCode -ne 0 -and $proc.ExitCode -ne $null) {
        Write-Warning "Edge exit code $($proc.ExitCode) pour $($html.Name)"
    }

    $ready = $false
    for ($i = 0; $i -lt 20; $i++) {
        if ((Test-Path $pdfPath) -and ((Get-Item $pdfPath -ErrorAction SilentlyContinue).Length -gt 1024)) {
            $ready = $true
            break
        }
        Start-Sleep -Milliseconds 250
    }

    if (-not $ready) {
        throw "Echec PDF: $($html.Name)"
    }

    $sizeKb = [math]::Round((Get-Item $pdfPath).Length / 1KB, 1)
    Write-Host "  OK $pdfName ($sizeKb Ko)"
}

Write-Host ""
Write-Host "PDF disponibles dans: $PdfDir"
