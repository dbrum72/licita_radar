$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location (Join-Path $projectRoot 'backend')
& (Join-Path $PSScriptRoot 'php.ps1') artisan radar:prune
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
& (Join-Path $PSScriptRoot 'php.ps1') artisan radar:sync
$syncExitCode = $LASTEXITCODE
# Process PDFs even when the upstream consultation ended with partial results.
& (Join-Path $PSScriptRoot 'php.ps1') artisan queue:work --stop-when-empty --timeout=21000 --tries=1
exit $syncExitCode
