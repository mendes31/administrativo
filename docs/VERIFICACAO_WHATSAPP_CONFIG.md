# Verificação WhatsApp Config - Próximos Passos

## ✅ Confirmado

1. **Página registrada:** ✅ ID 407, controller_url = 'whatsapp-config'
2. **Permissão Super Admin:** ✅ permission = 1, adms_access_level_id = 1

## 🔍 Próximas Verificações

### 1. Verificar se a tabela existe

Execute no banco de dados:

```sql
SHOW TABLES LIKE 'adms_whatsapp_config';
```

**Se não existir**, execute a migration:
```bash
php vendor/bin/phinx migrate -c database/phinx.php
```

### 2. Verificar estrutura da tabela (se existir)

```sql
DESCRIBE adms_whatsapp_config;
```

### 3. Verificar logs após acessar a página

**Passo a passo:**
1. Acesse a página `whatsapp-config` no navegador
2. Execute no servidor:

```bash
# Verificar logs gerais
tail -n 50 app/logs/*.log

# Verificar logs específicos do WhatsApp
tail -n 100 app/logs/*.log | grep -i "whatsapp\|WHATSAPP"

# Verificar logs de erro do PHP
tail -n 50 /var/log/php_errors.log
# ou
tail -n 50 /var/log/apache2/error.log
```

### 4. Verificar se há erro fatal no PHP

Se a página está completamente em branco, pode ser um erro fatal do PHP que não está sendo logado. 

**Verificar configuração de exibição de erros:**

Crie um arquivo temporário `test_error.php` na raiz:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "Teste de erro PHP funcionando!";
```

Acesse: `http://www.administrativotiaraju.kinghost.net/administrativo/test_error.php`

Se não mostrar nada, o problema pode ser:
- Erro fatal antes do PHP iniciar
- Problema de permissão de arquivo
- Problema de configuração do servidor

### 5. Testar o controller diretamente

Crie um arquivo `test_whatsapp_controller.php` na raiz:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Simular sessão
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Manager';
$_SESSION['user_access_level_id'] = 1;

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    echo "1. Testando instanciação do repository...\n";
    $repo = new \App\adms\Models\Repository\AdmsWhatsAppConfigRepository();
    echo "✅ Repository OK\n\n";
    
    echo "2. Testando getConfig()...\n";
    $config = $repo->getConfig();
    echo "✅ Config: " . json_encode($config) . "\n\n";
    
    echo "3. Testando instanciação do controller...\n";
    $controller = new \App\adms\Controllers\settings\WhatsAppConfig();
    echo "✅ Controller OK\n\n";
    
    echo "4. Testando método index()...\n";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    echo "✅ Index executado\n";
    echo "Tamanho da saída: " . strlen($output) . " bytes\n";
    
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
}
```

Execute via linha de comando:
```bash
php test_whatsapp_controller.php
```

### 6. Verificar se o arquivo da view existe

```bash
ls -la app/adms/Views/settings/whatsappConfig.php
```

### 7. Verificar permissões de arquivo

```bash
# Verificar permissões do controller
ls -la app/adms/Controllers/settings/WhatsAppConfig.php

# Verificar permissões da view
ls -la app/adms/Views/settings/whatsappConfig.php

# Verificar permissões do repository
ls -la app/adms/Models/Repository/AdmsWhatsAppConfigRepository.php
```

## 🎯 Diagnóstico Mais Provável

Com base nas verificações já feitas, o problema mais provável é:

1. **Tabela `adms_whatsapp_config` não existe** → Erro silencioso no repository
2. **Erro fatal no PageLayoutService** → Não está sendo capturado
3. **Erro na view** → Problema com include ou CSRFHelper
4. **Problema de autoloader** → Classe não está sendo encontrada

## 📝 Comandos Rápidos de Diagnóstico

```bash
# 1. Verificar tabela
mysql -u usuario -p administrativo -e "SHOW TABLES LIKE 'adms_whatsapp_config';"

# 2. Verificar logs mais recentes
tail -n 20 app/logs/*.log

# 3. Verificar se o arquivo existe
find . -name "WhatsAppConfig.php" -type f

# 4. Testar autoloader
php -r "require 'vendor/autoload.php'; echo class_exists('App\\adms\\Controllers\\settings\\WhatsAppConfig') ? 'OK' : 'ERRO';"
```

