$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location (Join-Path $projectRoot 'backend/public')
$routerPath = Join-Path $projectRoot 'backend/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
& (Join-Path $PSScriptRoot 'php.ps1') -S 127.0.0.1:8000 $routerPath
