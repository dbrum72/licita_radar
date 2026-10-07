$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$taskName = 'LicitaRadar - Consulta diaria PNCP'
$dailyScript = Join-Path $PSScriptRoot 'daily.ps1'
$powershellPath = (Get-Command pwsh.exe).Source
if (-not (Test-Path -LiteralPath $dailyScript)) { throw 'Script da consulta diária não encontrado.' }
$action = New-ScheduledTaskAction -Execute $powershellPath -Argument ('-NoProfile -NonInteractive -File "' + $dailyScript + '"') -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -Daily -At '06:00'
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 6) -MultipleInstances IgnoreNew -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description 'Consulta PNCP para projetos de engenharia e arquitetura e processa anexos no MySQL licitaRadar_bd.' -Force | Select-Object TaskName,State
