$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location (Join-Path $projectRoot 'backend')
& (Join-Path $PSScriptRoot 'php.ps1') artisan schedule:work
