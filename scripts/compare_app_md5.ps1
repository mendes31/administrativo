# Compara local_app_md5.txt (PC) com server_app_md5.txt (baixado do servidor).
# Uso: powershell -File scripts/compare_app_md5.ps1

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
if (-not (Test-Path (Join-Path $root 'app'))) {
    $root = Split-Path -Parent $PSScriptRoot
}

$localFile = Join-Path $root 'local_app_md5.txt'
$serverFile = Join-Path $root 'server_app_md5.txt'

if (-not (Test-Path $localFile)) {
    Write-Error "Gere local_app_md5.txt primeiro (scripts/generate_local_app_md5.ps1)."
    exit 1
}
if (-not (Test-Path $serverFile)) {
    Write-Error "Baixe /tmp/server_app_md5.txt do servidor para server_app_md5.txt na raiz do projeto."
    exit 1
}

function Read-Map($path) {
    $map = @{}
    Get-Content $path | ForEach-Object {
        if ($_ -match '^([a-f0-9]{32})\s+(.+)$') {
            $map[$Matches[2].Trim()] = $Matches[1]
        }
    }
    return $map
}

$local = Read-Map $localFile
$server = Read-Map $serverFile

$onlyLocal = $local.Keys | Where-Object { -not $server.ContainsKey($_) }
$onlyServer = $server.Keys | Where-Object { -not $local.ContainsKey($_) }
$diffHash = $local.Keys | Where-Object { $server.ContainsKey($_) -and $local[$_] -ne $server[$_] }

Write-Host "Local:  $($local.Count) arquivos"
Write-Host "Server: $($server.Count) arquivos"
Write-Host "Somente no local:  $($onlyLocal.Count)"
Write-Host "Somente no server: $($onlyServer.Count)"
Write-Host "Hash diferente:    $($diffHash.Count)"

if ($diffHash.Count -gt 0) {
    Write-Host "`n--- Hash diferente (reenviar estes) ---"
    $diffHash | Select-Object -First 30 | ForEach-Object { Write-Host $_ }
    if ($diffHash.Count -gt 30) { Write-Host "... e mais $($diffHash.Count - 30)" }
}

if ($onlyLocal.Count -gt 0) {
    Write-Host "`n--- Somente no local (faltam no servidor) ---"
    $onlyLocal | Select-Object -First 20 | ForEach-Object { Write-Host $_ }
}
