# Guia de Diagnóstico - Training KPI Dashboard em Branco

## Problema
A página `training-kpi-dashboard` funciona localmente mas fica em branco na produção.

## Possíveis Causas

1. **Erros de PHP sendo suprimidos** (mais comum)
2. **Falta de permissão** para acessar a página
3. **Erros em métodos do repository** que retornam vazio
4. **Variáveis de ambiente** não configuradas corretamente
5. **Falta de dados** no banco de produção

## Passos para Diagnóstico

### 1. Executar Script de Diagnóstico

Execute na produção:

```bash
php scripts/diagnostico_training_kpi_dashboard.php
```

Este script vai verificar:
- ✓ Se todas as classes existem
- ✓ Se a view existe
- ✓ Se a conexão com banco funciona
- ✓ Se as tabelas existem
- ✓ Se os métodos do repository funcionam
- ✓ Se há erros de configuração

### 2. Verificar Logs de Erro do PHP

**No servidor de produção, verifique:**

```bash
# Verificar último erro do PHP
tail -n 50 /var/log/php_errors.log

# Ou se usar error_log customizado
tail -n 50 /home/administrativotiaraju/www/administrativo/logs/*.log
```

### 3. Habilitar Display de Erros Temporariamente

Crie um arquivo `.user.ini` na raiz do projeto com:

```ini
display_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /home/administrativotiaraju/www/administrativo/logs/php_errors.log
```

**IMPORTANTE:** Remova este arquivo após o diagnóstico!

### 4. Verificar Permissões

Verifique se o usuário logado tem permissão para acessar a página:

```sql
SELECT p.*, per.id as permission_id
FROM adms_pages p
LEFT JOIN adms_permissions per ON per.adms_page_id = p.id
WHERE p.controller = 'TrainingKpiDashboard';
```

Verifique se o nível de acesso do usuário tem permissão:

```sql
SELECT al.name as access_level, p.name as page_name
FROM adms_access_levels al
INNER JOIN adms_permissions per ON per.adms_access_level_id = al.id
INNER JOIN adms_pages p ON p.id = per.adms_page_id
WHERE p.controller = 'TrainingKpiDashboard';
```

### 5. Verificar Se Há Dados no Banco

```sql
-- Verificar se há dados de treinamentos
SELECT COUNT(*) as total FROM adms_training_users;
SELECT COUNT(*) as total FROM adms_trainings;
SELECT COUNT(*) as total FROM adms_training_applications;
```

### 6. Testar Métodos do Repository Diretamente

Crie um arquivo `test_repository.php` na raiz:

```php
<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

try {
    $repo = new App\adms\Models\Repository\TrainingUsersRepository();
    
    echo "Testando getSummaryAll()...\n";
    $summary = $repo->getSummaryAll();
    print_r($summary);
    
    echo "\nTestando getStatusCounts()...\n";
    $status = $repo->getStatusCounts();
    print_r($status);
    
    echo "\nTestando getMonthlyRealizations()...\n";
    $monthly = $repo->getMonthlyRealizations();
    print_r($monthly);
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
```

Execute: `php test_repository.php`

## Soluções Comuns

### Solução 1: Adicionar Tratamento de Erros (JÁ IMPLEMENTADO)

O controller já foi atualizado com tratamento de erros. Se ainda assim não funcionar, pode ser:

- **Erro fatal antes do controller ser carregado**
- **Problema com autoload do Composer**
- **Problema com variáveis de ambiente**

### Solução 2: Verificar .env na Produção

Verifique se o arquivo `.env` na produção tem todas as variáveis:

```bash
cat .env | grep DB_
```

Deve ter:
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_PORT` (opcional)

### Solução 3: Verificar Autoload do Composer

Na produção, execute:

```bash
composer dump-autoload
```

### Solução 4: Verificar Permissões de Arquivo

```bash
# Verificar se os arquivos têm permissão de leitura
ls -la app/adms/Controllers/trainings/TrainingKPIDashboard.php
ls -la app/adms/Views/trainings/kpiDashboard.php
```

### Solução 5: Verificar se a Rota Está Configurada

Verifique se `TrainingKpiDashboard` está na lista de páginas privadas em `routes/LoadPageAdm.php` (já está).

## Próximos Passos

1. Execute o script de diagnóstico
2. Compartilhe os resultados
3. Verifique os logs de erro
4. Teste os métodos do repository individualmente

## Contato

Se o problema persistir após seguir todos os passos, compartilhe:
- Resultado do script de diagnóstico
- Logs de erro do PHP
- Resultado dos testes de repository
- Versão do PHP na produção (`php -v`)

