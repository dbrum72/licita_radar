param([Parameter(ValueFromRemainingArguments=$true)][string[]]$Arguments)
# The scan directory is inherited by Artisan subprocesses (server and tests).
$projectRoot = Split-Path $PSScriptRoot -Parent
$previousScan = $env:PHP_INI_SCAN_DIR
$env:PHP_INI_SCAN_DIR = Join-Path $projectRoot 'backend/runtime'
try { & php @Arguments; $resultCode = $LASTEXITCODE }
finally { $env:PHP_INI_SCAN_DIR = $previousScan }
exit $resultCode
