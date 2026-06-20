# Gera local_app_md5.txt na raiz do projeto.
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Get-ChildItem -Path app\adms -Recurse -Filter *.php -File |
    ForEach-Object {
        $rel = $_.FullName.Substring((Get-Location).Path.Length + 1).Replace('\', '/')
        $hash = (Get-FileHash $_.FullName -Algorithm MD5).Hash.ToLower()
        "$hash  $rel"
    } |
    Sort-Object |
    Set-Content -Encoding utf8 (Join-Path $root 'local_app_md5.txt')

$lines = (Get-Content (Join-Path $root 'local_app_md5.txt') | Measure-Object -Line).Lines
Write-Host "Gerado local_app_md5.txt com $lines linhas."
