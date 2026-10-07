$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location (Join-Path $projectRoot 'backend')
& (Join-Path $PSScriptRoot 'php.ps1') artisan queue:work --timeout=21000 --tries=1
