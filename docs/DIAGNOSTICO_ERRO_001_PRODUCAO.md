# Diagnóstico: Erro 001 em Produção

## 🔍 Problema Identificado

**Erro exibido:**
```
Erro 001: Por favor tente novamente. Caso o problema persista, entre em contato com o adminstrador raffaell_mendez@hotmail.com
```

**Localização do erro:**
- Arquivo: `app/adms/Models/Services/DbConnection.php`
- Linha: 71
- Causa: Falha na conexão com o banco de dados (PDOException)

---

## 🔴 Possíveis Causas

### 1. **Variáveis de Ambiente (.env) não carregadas**

**Sintoma:** `$_ENV['DB_HOST']`, `$_ENV['DB_USER']`, etc. estão vazias ou não definidas.

**Verificação:**
```bash
# No Putty, verificar se o arquivo .env existe
cd /home/administrativotiaraju/www/administrativo
ls -la .env

# Verificar conteúdo (sem expor senhas)
cat .env | grep DB_
```

**Solução:**
- Verificar se o arquivo `.env` existe e está no local correto
- Verificar se as variáveis estão definidas corretamente
- Verificar se há espaços extras ou caracteres especiais

---

### 2. **Credenciais do Banco de Dados Incorretas**

**Sintoma:** Credenciais mudaram ou estão incorretas no `.env`.

**Verificação:**
```bash
# Testar conexão manualmente (substituir pelos valores do .env)
mysql -h [DB_HOST] -u [DB_USER] -p[DB_PASS] [DB_NAME]
```

**Solução:**
- Verificar credenciais no `.env`
- Confirmar com o provedor de hospedagem se as credenciais estão corretas
- Verificar se o banco de dados existe e está acessível

---

### 3. **Servidor de Banco de Dados Inacessível**

**Sintoma:** O servidor MySQL/MariaDB não está respondendo.

**Verificação:**
```bash
# Testar conectividade com o servidor
ping [DB_HOST]

# Testar porta MySQL (geralmente 3306)
telnet [DB_HOST] 3306
# ou
nc -zv [DB_HOST] 3306
```

**Solução:**
- Verificar se o servidor MySQL está rodando
- Verificar firewall/regras de acesso
- Contatar suporte da hospedagem

---

### 4. **Extensão PDO não habilitada**

**Sintoma:** PHP não consegue usar PDO para conectar ao banco.

**Verificação:**
```bash
# Verificar extensões PHP habilitadas
php -m | grep -i pdo
php -m | grep -i mysql
```

**Solução:**
- Habilitar extensão PDO no PHP
- Habilitar extensão PDO_MySQL
- Reiniciar servidor web (Apache/Nginx)

---

### 5. **Arquivo .env corrompido ou com encoding incorreto**

**Sintoma:** Arquivo existe mas não está sendo lido corretamente.

**Verificação:**
```bash
# Verificar encoding
file .env

# Verificar se há caracteres especiais
cat -A .env | head -20
```

**Solução:**
- Recriar arquivo `.env` com encoding UTF-8
- Remover caracteres especiais ou BOM
- Verificar permissões do arquivo (deve ser 644)

---

### 6. **Problema com autoload do Composer**

**Sintoma:** Classes não estão sendo carregadas corretamente.

**Verificação:**
```bash
# Verificar se vendor/autoload.php existe
ls -la vendor/autoload.php

# Verificar se composer foi executado
ls -la vendor/
```

**Solução:**
```bash
# Executar composer install/update
php composer.phar install --no-dev --optimize-autoloader
```

---

### 7. **Problema com PHP 7.4 vs 8.2**

**Sintoma:** Código desenvolvido em PHP 8.2 não funciona em PHP 7.4.

**Verificação:**
```bash
# Verificar versão PHP em produção
php -v

# Verificar logs de erro PHP
tail -n 50 /var/log/php_errors.log
# ou
tail -n 50 app/logs/*.log
```

**Solução:**
- Verificar se há erros de sintaxe PHP 8.0+ no código
- Verificar se dependências do Composer são compatíveis com PHP 7.4
- Ver seção "Compatibilidade PHP 7.4" no documento `ANALISE_COMPATIBILIDADE_PHP74.md`

---

## 🔧 Passos de Diagnóstico

### Passo 1: Verificar Logs

```bash
# No Putty, acessar o diretório do projeto
cd /home/administrativotiaraju/www/administrativo

# Verificar logs da aplicação
tail -n 100 app/logs/*.log

# Verificar logs do PHP
tail -n 100 /var/log/php_errors.log
# ou (dependendo da hospedagem)
tail -n 100 /var/log/apache2/error.log
```

---

### Passo 2: Verificar Arquivo .env

```bash
# Verificar se existe
ls -la .env

# Verificar conteúdo (cuidado com senhas!)
cat .env

# Verificar variáveis específicas
grep "DB_" .env
```

**Variáveis necessárias:**
```env
DB_HOST=localhost
DB_NAME=administrativo
DB_USER=usuario
DB_PASS=senha
DB_PORT=3306
```

---

### Passo 3: Testar Conexão Manualmente

Criar arquivo de teste `test_db.php`:

```php
<?php
require './vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], $options);
    echo "✅ Conexão com banco de dados realizada com sucesso!";
    
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage();
}
```

**Executar:**
```bash
php test_db.php
```

**⚠️ IMPORTANTE:** Remover o arquivo `test_db.php` após o teste por segurança!

---

### Passo 4: Verificar Versão PHP

```bash
# Verificar versão PHP
php -v

# Verificar extensões
php -m | grep -i pdo
php -m | grep -i mysql
```

---

### Passo 5: Verificar Permissões

```bash
# Verificar permissões do .env
ls -la .env

# Deve ser 644 (rw-r--r--)
chmod 644 .env

# Verificar permissões do diretório
ls -la .
```

---

## 🚨 Soluções Rápidas

### Solução 1: Recarregar .env

```bash
# No Putty
cd /home/administrativotiaraju/www/administrativo

# Verificar se .env existe e tem conteúdo
cat .env

# Se não existir ou estiver vazio, recriar
nano .env
```

**Conteúdo mínimo necessário:**
```env
DB_HOST=localhost
DB_NAME=administrativo
DB_USER=seu_usuario
DB_PASS=sua_senha
DB_PORT=3306
URL_ADM=http://www.administrativotiaraju.kinghost.net/administrativo/
EMAIL_ADM=raffaell_mendez@hotmail.com
APP_TIMEZONE=America/Sao_Paulo
```

---

### Solução 2: Reexecutar Composer

```bash
cd /home/administrativotiaraju/www/administrativo

# Atualizar autoloader
php composer.phar dump-autoload --optimize

# Se necessário, reinstalar dependências
php composer.phar install --no-dev --optimize-autoloader
```

---

### Solução 3: Verificar Logs de Erro PHP

```bash
# Verificar logs do PHP
tail -n 50 /var/log/php_errors.log

# Ou logs do Apache
tail -n 50 /var/log/apache2/error.log

# Ou logs da aplicação
tail -n 50 app/logs/*.log
```

---

### Solução 4: Testar Acesso ao index.php

```bash
# Testar se index.php está acessível
curl -I http://www.administrativotiaraju.kinghost.net/administrativo/index.php

# Testar com parâmetro
curl -I "http://www.administrativotiaraju.kinghost.net/administrativo/index.php?url=dashboard"
```

---

## 📋 Checklist de Verificação

- [ ] Arquivo `.env` existe e está no local correto
- [ ] Variáveis `DB_*` estão definidas no `.env`
- [ ] Credenciais do banco de dados estão corretas
- [ ] Servidor MySQL está acessível
- [ ] Extensão PDO está habilitada no PHP
- [ ] Extensão PDO_MySQL está habilitada no PHP
- [ ] Composer autoloader está atualizado
- [ ] Permissões do arquivo `.env` estão corretas (644)
- [ ] Versão PHP é compatível (7.4 ou superior)
- [ ] Logs não mostram outros erros

---

## 🔍 Comandos de Diagnóstico Completo

Execute estes comandos no Putty para diagnóstico completo:

```bash
# 1. Navegar para o diretório
cd /home/administrativotiaraju/www/administrativo

# 2. Verificar versão PHP
echo "=== PHP VERSION ==="
php -v

# 3. Verificar extensões
echo "=== PHP EXTENSIONS ==="
php -m | grep -i pdo
php -m | grep -i mysql

# 4. Verificar arquivo .env
echo "=== .ENV FILE ==="
ls -la .env
echo "--- DB Variables ---"
grep "DB_" .env | sed 's/=.*/=***/'  # Ocultar valores

# 5. Verificar logs
echo "=== APPLICATION LOGS ==="
tail -n 20 app/logs/*.log 2>/dev/null || echo "Nenhum log encontrado"

# 6. Verificar Composer
echo "=== COMPOSER ==="
ls -la vendor/autoload.php 2>/dev/null && echo "✅ Autoload existe" || echo "❌ Autoload não encontrado"

# 7. Testar conexão (criar arquivo temporário)
echo "=== DATABASE CONNECTION TEST ==="
cat > /tmp/test_db_connection.php << 'EOF'
<?php
require './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();
try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    echo "✅ Conexão OK\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
EOF
php /tmp/test_db_connection.php
rm /tmp/test_db_connection.php
```

---

## 💡 Próximos Passos

1. **Executar comandos de diagnóstico** acima
2. **Verificar logs** para identificar o erro específico
3. **Testar conexão manual** com o banco de dados
4. **Verificar se .env está correto** e acessível
5. **Verificar versão PHP** e compatibilidade

---

## ⚠️ IMPORTANTE

**Se o problema persistir após verificar todos os itens acima:**

1. Verificar se há mudanças recentes no código que foram deployadas
2. Verificar se a hospedagem mudou configurações
3. Verificar se há problemas de compatibilidade PHP 7.4 vs 8.2
4. Contatar suporte da hospedagem para verificar:
   - Status do servidor MySQL
   - Configurações PHP
   - Logs do servidor web

---

## 📞 Informações para Suporte

Ao contatar o suporte da hospedagem, forneça:

1. **Versão PHP:** `php -v`
2. **Extensões habilitadas:** `php -m`
3. **Erro específico dos logs**
4. **Resultado do teste de conexão**
5. **Configuração do .env** (sem senhas)


