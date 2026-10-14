$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$publicRoot = Join-Path $projectRoot 'backend/public'
$phpPath = (Get-Command php -ErrorAction Stop).Source
$nodePath = (Get-Command node -ErrorAction Stop).Source
$routerPath = Join-Path $projectRoot 'backend/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
$serverPath = Join-Path $PSScriptRoot 'http-server.mjs'
foreach ($port in @(8000, 9081, 9082, 9083, 9084)) {
    if (Get-NetTCPConnection -State Listen -LocalPort $port -ErrorAction SilentlyContinue) {
        throw "Porta $port ocupada. Encerre o servidor anterior antes de iniciar."
    }
}
$previousScan = $env:PHP_INI_SCAN_DIR
$responders = @()
try {
    $env:PHP_INI_SCAN_DIR = Join-Path $projectRoot 'backend/runtime'
    foreach ($port in @(9081, 9082, 9083, 9084)) {
        $responders += Start-Process -FilePath $phpPath -ArgumentList '-S',"127.0.0.1:$port",('"' + $routerPath + '"') -WorkingDirectory $publicRoot -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $projectRoot "backend/storage/logs/php-http-$port.out.log") -RedirectStandardError (Join-Path $projectRoot "backend/storage/logs/php-http-$port.err.log")
    }
    foreach ($process in $responders) {
        if ($process.HasExited) { throw 'Um processo PHP encerrou durante a inicialização.' }
    }
    foreach ($port in @(9081, 9082, 9083, 9084)) {
        $deadline = [DateTime]::UtcNow.AddSeconds(5)
        do {
            $connection = [System.Net.Sockets.TcpClient]::new()
            try { $connection.Connect('127.0.0.1', $port); $ready = $true }
            catch { $ready = $false }
            finally { $connection.Dispose() }
            if (!$ready) { Start-Sleep -Milliseconds 50 }
        } until ($ready -or [DateTime]::UtcNow -ge $deadline)
        if (!$ready) { throw "Processo PHP na porta $port não iniciou." }
    }
    Write-Host 'API: http://127.0.0.1:8000 — leituras e ações manuais com capacidade HTTP independente.'
    & $nodePath $serverPath
} finally {
    foreach ($process in $responders) {
        if (!$process.HasExited) { Stop-Process -Id $process.Id -ErrorAction SilentlyContinue }
    }
    $env:PHP_INI_SCAN_DIR = $previousScan
}
