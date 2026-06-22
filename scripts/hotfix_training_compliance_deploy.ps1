# Hotfix imediato — Dashboard de Necessidades (Erro 004)
#
# Envia via SCP os ficheiros que o deploy FTP costuma pular quando o estado está desalinhado.
# Depois, no PuTTY:
#   cd /home/tiaraju/www/administrativo
#   php scripts/generate_ftp_deploy_state.php
#   php scripts/verify_production_deploy.php
#   php vendor/bin/phinx migrate -c database/phinx.php -e production
#
# Uso (PowerShell, na raiz do projeto):
#   .\scripts\hotfix_training_compliance_deploy.ps1

$ErrorActionPreference = "Stop"

$HostRemote = "tiaraju02@web119.kinghost.net"
$RemoteBase = "/home/tiaraju/www/administrativo"
$LocalRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

$Files = @(
    "routes/LoadPageAdm.php",
    "app/adms/Controllers/trainings/TrainingComplianceDashboard.php",
    "app/adms/Controllers/Services/PageLayoutService.php",
    "app/adms/Models/Repository/TrainingUsersRepository.php",
    "app/adms/Models/Repository/TrainingsRepository.php",
    "app/adms/Views/trainings/complianceDashboard.php",
    "app/adms/Views/trainings/partials/complianceDashboardCharts.php",
    "app/adms/Views/trainings/partials/complianceDashboardSections.php",
    "app/adms/Views/partials/menu.php",
    "scripts/generate_ftp_deploy_state.php",
    "scripts/verify_production_deploy.php",
    "scripts/deploy_excludes.php",
    "scripts/deploy_config.php",
    "scripts/detect_ftp_deploy_root.php",
    "scripts/deploy_critical_manifest.php",
    "scripts/verify_ftp_deploy_hashes.php",
    "scripts/upload_ftp_sync_state.php"
)

Write-Host "=== Hotfix Training Compliance Dashboard ===" -ForegroundColor Cyan
Write-Host "Origem: $LocalRoot"
Write-Host "Destino: ${HostRemote}:${RemoteBase}"
Write-Host ""

foreach ($rel in $Files) {
    $local = Join-Path $LocalRoot ($rel -replace '/', '\')
    if (-not (Test-Path $local)) {
        Write-Host "AVISO: ficheiro local ausente: $rel" -ForegroundColor Yellow
        continue
    }
    $remote = "$RemoteBase/$rel"
    Write-Host "Enviando: $rel"
    scp $local "${HostRemote}:${remote}"
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Falha ao enviar $rel (exit $LASTEXITCODE)"
    }
}

Write-Host ""
Write-Host "Concluido. No PuTTY execute:" -ForegroundColor Green
Write-Host "  cd /home/tiaraju/www/administrativo"
Write-Host "  rm -rf administrativo Administrativo   # apagar pasta aninhada errada"
Write-Host "  php scripts/generate_ftp_deploy_state.php"
Write-Host "  php scripts/verify_production_deploy.php"
Write-Host "  php vendor/bin/phinx migrate -c database/phinx.php -e production"
