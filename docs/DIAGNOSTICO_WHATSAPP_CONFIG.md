# Diagnóstico - Página WhatsApp Config em Branco

## Script SQL de Diagnóstico

Execute os seguintes comandos SQL no banco de dados para verificar o problema:

```sql
-- 1. Verificar se a página está registrada
SELECT id, name, controller, controller_url, directory, page_status 
FROM adms_pages 
WHERE controller_url = 'whatsapp-config';

-- 2. Verificar permissão do Super Admin (ID = 1)
SELECT 
    alp.id,
    alp.adms_access_level_id,
    alp.adms_page_id,
    alp.permission,
    p.name as page_name,
    p.controller_url
FROM adms_access_levels_pages alp
INNER JOIN adms_pages p ON p.id = alp.adms_page_id
WHERE alp.adms_access_level_id = 1 
AND p.controller_url = 'whatsapp-config';

-- 3. Verificar se a tabela existe
SHOW TABLES LIKE 'adms_whatsapp_config';

-- 4. Verificar estrutura da tabela (se existir)
DESCRIBE adms_whatsapp_config;

-- 5. Verificar se há registros na tabela
SELECT COUNT(*) as total FROM adms_whatsapp_config;
```

## Comandos de Correção

### Se a página não estiver registrada:
```bash
php vendor/bin/phinx seed:run -c database/phinx.php -s AddAdmsPages
```

### Se a permissão não existir:
```bash
php vendor/bin/phinx seed:run -c database/phinx.php -s SyncAccessLevelsPages
```

### Se a tabela não existir:
```bash
php vendor/bin/phinx migrate -c database/phinx.php
```

## Verificar Logs de Erro em Produção

```bash
# Verificar logs do PHP
tail -n 100 /var/log/php_errors.log

# Ou logs da aplicação
tail -n 100 app/logs/*.log

# Verificar se há erros específicos do WhatsApp
grep -i "whatsapp" app/logs/*.log
```

## Teste Direto do Controller

Crie um arquivo de teste temporário para verificar se o controller funciona:

```php
<?php
// Arquivo: test_whatsapp_config.php (na raiz do projeto)
require_once __DIR__ . '/vendor/autoload.php';

// Iniciar sessão simulada
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Manager';
$_SESSION['user_access_level_id'] = 1;

// Carregar variáveis de ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Testar o controller
try {
    $controller = new \App\adms\Controllers\settings\WhatsAppConfig();
    $controller->index();
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
}
```

Execute:
```bash
php test_whatsapp_config.php
```

## Possíveis Causas e Soluções

### 1. Página não registrada no banco
**Sintoma:** Controller não é encontrado pelo roteador
**Solução:** Executar seed `AddAdmsPages`

### 2. Permissão não configurada
**Sintoma:** Página existe mas usuário não tem acesso
**Solução:** Executar seed `SyncAccessLevelsPages`

### 3. Tabela não existe
**Sintoma:** Erro ao buscar configuração
**Solução:** Executar migration `20251029100000_create_adms_whatsapp_config.php`

### 4. Erro fatal não capturado
**Sintoma:** Página em branco sem mensagem
**Solução:** Verificar logs do PHP/Apache

### 5. Problema com autoloader
**Sintoma:** Classe não encontrada
**Solução:** Executar `php composer.phar dump-autoload --optimize`

## Logs Adicionados

O controller agora possui logs detalhados. Após acessar a página, verifique:

```bash
# Verificar logs específicos do WhatsApp
tail -n 50 app/logs/*.log | grep -i "whatsapp\|WHATSAPP"
```

Os logs mostrarão exatamente onde o erro está ocorrendo:
- `=== WHATSAPP CONFIG INDEX INICIO ===`
- `Repository instanciado com sucesso`
- `Config buscada: ...`
- `Dados preparados, chamando PageLayoutService`
- `PageLayoutService executado, chamando LoadViewService`
- `=== WHATSAPP CONFIG INDEX SUCESSO ===`

Ou, em caso de erro:
- `=== ERRO FATAL em WhatsAppConfig::index() ===`
- Com mensagem, arquivo, linha e stack trace completos

