# Script PowerShell para aplicar índices de performance
# Uso: .\scripts\aplicar_indices_powershell.ps1

# ============================================================
# CONFIGURAÇÕES - AJUSTE AQUI
# ============================================================
$mysqlUser = "tiaraju004_add1"
$mysqlPass = "pb3wPDi4J@1T"
$mysqlDatabase = "administrativo"
$mysqlPath = "mysql"  # Ou caminho completo: "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"

# ============================================================
# SCRIPT
# ============================================================

Write-Host "🔧 Aplicando índices de performance..." -ForegroundColor Cyan
Write-Host ""

# Verificar se o arquivo SQL existe
$sqlFile = "scripts/add_performance_indexes_crm_hierarchy.sql"
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ Arquivo não encontrado: $sqlFile" -ForegroundColor Red
    exit 1
}

# Ler o conteúdo do arquivo SQL
Write-Host "📄 Lendo arquivo SQL..." -ForegroundColor Yellow
$sqlContent = Get-Content $sqlFile -Raw

# Verificar se o MySQL está disponível
Write-Host "🔍 Verificando MySQL..." -ForegroundColor Yellow
try {
    $mysqlVersion = & $mysqlPath --version 2>&1
    Write-Host "✅ MySQL encontrado: $mysqlVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ MySQL não encontrado no PATH" -ForegroundColor Red
    Write-Host "💡 Tente especificar o caminho completo em `$mysqlPath" -ForegroundColor Yellow
    exit 1
}

# Executar o SQL
Write-Host ""
Write-Host "🚀 Executando SQL..." -ForegroundColor Yellow
Write-Host ""

try {
    # Criar arquivo temporário com o SQL
    $tempFile = [System.IO.Path]::GetTempFileName()
    $sqlContent | Out-File -FilePath $tempFile -Encoding UTF8
    
    # Executar MySQL
    $command = "$mysqlPath -u $mysqlUser -p$mysqlPass $mysqlDatabase < `"$tempFile`""
    
    # No PowerShell, precisamos usar cmd para redirecionamento
    cmd /c "$mysqlPath -u $mysqlUser -p$mysqlPass $mysqlDatabase < `"$tempFile`""
    
    # Remover arquivo temporário
    Remove-Item $tempFile -ErrorAction SilentlyContinue
    
    Write-Host ""
    Write-Host "✅ Índices aplicados com sucesso!" -ForegroundColor Green
    Write-Host ""
    Write-Host "💡 Verifique os índices no phpMyAdmin ou execute:" -ForegroundColor Cyan
    Write-Host "   SHOW INDEXES FROM crm_partners;" -ForegroundColor Gray
    Write-Host "   SHOW INDEXES FROM adms_users;" -ForegroundColor Gray
    
} catch {
    Write-Host ""
    Write-Host "❌ Erro ao executar SQL:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Tente aplicar via phpMyAdmin:" -ForegroundColor Yellow
    Write-Host "   1. Acesse phpMyAdmin" -ForegroundColor Gray
    Write-Host "   2. Selecione o banco '$mysqlDatabase'" -ForegroundColor Gray
    Write-Host "   3. Aba SQL" -ForegroundColor Gray
    Write-Host "   4. Execute: scripts/add_performance_indexes_crm_hierarchy_phpmyadmin.sql" -ForegroundColor Gray
    exit 1
}

