# Guia: Migração para Novo Domínio

## 🎯 Objetivo

Migrar o projeto para um novo domínio/hospedagem, garantindo que todas as dependências e configurações estejam corretas.

---

## ✅ Passo a Passo Completo

### 1️⃣ **Executar Composer (OBRIGATÓRIO)**

**Sim, você DEVE executar o Composer!** O diretório `vendor` pode estar incompleto ou gerado com outra versão do PHP.

#### Via Putty (Recomendado):

```bash
# 1. Acessar o diretório do projeto
cd /home/tiaraju/www/administrativo

# 2. Verificar versão PHP
php -v

# 3. Verificar se composer.phar existe
ls -la composer.phar

# 4. Se não existir, baixar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# 5. Executar Composer Install
php composer.phar install --no-dev --optimize-autoloader
```

**⚠️ NÃO precisa apagar o vendor antes!** O Composer vai verificar e instalar/atualizar o que for necessário.

**Se ainda assim der erro, aí sim pode limpar:**
```bash
rm -rf vendor/
php composer.phar install --no-dev --optimize-autoloader
```

---

### 2️⃣ **Verificar Arquivo .env**

O arquivo `.env` precisa ter as configurações corretas para o novo domínio.

```bash
# Verificar se .env existe
ls -la .env

# Editar .env (substituir pelos valores corretos)
nano .env
```

**Variáveis que PRECISAM ser atualizadas:**

```env
# URL do novo domínio
URL_ADM=https://tiaraju.com.br/administrativo/

# Banco de dados (se mudou)
DB_HOST=localhost
DB_NAME=nome_do_banco
DB_USER=usuario_banco
DB_PASS=senha_banco
DB_PORT=3306

# Email do administrador
EMAIL_ADM=raffaell_mendez@hotmail.com

# Timezone
APP_TIMEZONE=America/Sao_Paulo
```

**⚠️ IMPORTANTE:** Após editar, salve o arquivo (Ctrl+O, Enter, Ctrl+X no nano).

---

### 3️⃣ **Verificar Permissões**

```bash
# Verificar permissões do .env (deve ser 644)
ls -la .env
chmod 644 .env

# Verificar permissões do diretório vendor
chmod -R 755 vendor/

# Verificar permissões do diretório logs
chmod -R 755 app/logs/
chmod -R 755 logs/
```

---

### 4️⃣ **Verificar Banco de Dados**

Certifique-se de que:

1. **Banco de dados foi criado** no novo servidor
2. **Migrations foram executadas:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php
   ```
3. **Seeds foram executados (se necessário):**
   ```bash
   php vendor/bin/phinx seed:run -c database/phinx.php
   ```

---

### 5️⃣ **Verificar Configuração do Servidor Web**

#### Apache (.htaccess)

Verificar se o arquivo `.htaccess` está presente e correto:

```bash
ls -la .htaccess
```

**Conteúdo mínimo necessário:**
```apache
RewriteEngine On
RewriteBase /administrativo/

# Redirecionar para index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]

# Regra para raiz
RewriteCond %{REQUEST_URI} ^/administrativo/?$
RewriteRule ^$ index.php?url=dashboard [QSA,L]
```

---

### 6️⃣ **Verificar Versão PHP**

O servidor está usando PHP 8.2 (vejo `/opt/remi/php82/` no erro).

**Verificar extensões necessárias:**
```bash
php -m | grep -i pdo
php -m | grep -i mysql
php -m | grep -i curl
php -m | grep -i mbstring
php -m | grep -i xml
php -m | grep -i zip
```

**Extensões obrigatórias:**
- `pdo`
- `pdo_mysql`
- `curl`
- `mbstring`
- `xml`
- `zip`

---

## 🔍 Checklist de Migração

Execute este checklist completo:

### ✅ Dependências
- [ ] `composer.phar` existe no servidor
- [ ] Executado `php composer.phar install --no-dev --optimize-autoloader`
- [ ] Verificado que `vendor/composer/ClassLoader.php` existe
- [ ] Verificado que `vendor/autoload.php` existe

### ✅ Configuração
- [ ] Arquivo `.env` existe e está configurado
- [ ] `URL_ADM` atualizado para novo domínio
- [ ] Credenciais do banco de dados corretas
- [ ] `EMAIL_ADM` configurado

### ✅ Banco de Dados
- [ ] Banco de dados criado
- [ ] Migrations executadas (`phinx migrate`)
- [ ] Seeds executados (se necessário)

### ✅ Permissões
- [ ] `.env` com permissão 644
- [ ] `vendor/` com permissão 755
- [ ] `logs/` com permissão 755

### ✅ Servidor Web
- [ ] Arquivo `.htaccess` presente
- [ ] PHP 8.2 instalado e funcionando
- [ ] Extensões PHP necessárias habilitadas

### ✅ Testes
- [ ] Site acessível via navegador
- [ ] Login funcionando
- [ ] Sem erros no console do navegador
- [ ] Logs não mostram erros críticos

---

## 🚀 Comandos Rápidos (Copiar e Colar)

Execute estes comandos **na ordem** no Putty:

```bash
# ============================================
# 1. NAVEGAR PARA O DIRETÓRIO
# ============================================
cd /home/tiaraju/www/administrativo

# ============================================
# 2. VERIFICAR VERSÃO PHP
# ============================================
echo "=== PHP VERSION ==="
php -v

# ============================================
# 3. VERIFICAR COMPOSER
# ============================================
echo "=== COMPOSER ==="
if [ -f composer.phar ]; then
    echo "✅ composer.phar existe"
else
    echo "❌ Baixando composer.phar..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
fi

# ============================================
# 4. EXECUTAR COMPOSER INSTALL
# ============================================
echo "=== EXECUTANDO COMPOSER INSTALL ==="
php composer.phar install --no-dev --optimize-autoloader

# ============================================
# 5. VERIFICAR SE FUNCIONOU
# ============================================
echo "=== VERIFICANDO INSTALAÇÃO ==="
if [ -f vendor/composer/ClassLoader.php ]; then
    echo "✅ ClassLoader.php existe"
else
    echo "❌ ClassLoader.php NÃO encontrado - tentando limpar e reinstalar..."
    rm -rf vendor/
    php composer.phar install --no-dev --optimize-autoloader
fi

# ============================================
# 6. VERIFICAR .ENV
# ============================================
echo "=== VERIFICANDO .ENV ==="
if [ -f .env ]; then
    echo "✅ .env existe"
    echo "⚠️  Verifique se URL_ADM está correto:"
    grep "URL_ADM" .env
else
    echo "❌ .env NÃO encontrado - CRIE O ARQUIVO!"
fi

# ============================================
# 7. VERIFICAR PERMISSÕES
# ============================================
echo "=== AJUSTANDO PERMISSÕES ==="
chmod 644 .env 2>/dev/null
chmod -R 755 vendor/ 2>/dev/null
chmod -R 755 app/logs/ 2>/dev/null
chmod -R 755 logs/ 2>/dev/null

# ============================================
# 8. VERIFICAR EXTENSÕES PHP
# ============================================
echo "=== EXTENSÕES PHP ==="
php -m | grep -i pdo
php -m | grep -i mysql
php -m | grep -i curl

# ============================================
# 9. EXECUTAR MIGRATIONS (SE NECESSÁRIO)
# ============================================
echo "=== MIGRATIONS ==="
echo "⚠️  Execute manualmente se necessário:"
echo "php vendor/bin/phinx migrate -c database/phinx.php"

echo ""
echo "✅ DIAGNÓSTICO COMPLETO!"
```

---

## ⚠️ Problemas Comuns e Soluções

### Problema 1: "ClassLoader.php not found" após composer install

**Solução:**
```bash
# Limpar e reinstalar
rm -rf vendor/
php composer.phar install --no-dev --optimize-autoloader
```

---

### Problema 2: "Erro 001" (Conexão com banco)

**Solução:**
1. Verificar `.env` - credenciais do banco estão corretas?
2. Verificar se banco de dados existe
3. Testar conexão manualmente:
   ```bash
   mysql -h [DB_HOST] -u [DB_USER] -p[DB_PASS] [DB_NAME]
   ```

---

### Problema 3: "404 Not Found"

**Solução:**
1. Verificar se `.htaccess` existe
2. Verificar se `mod_rewrite` está habilitado no Apache
3. Verificar se `URL_ADM` no `.env` está correto

---

### Problema 4: "Composer dependencies require PHP >= 8.2.0"

**Causa:** Dependências requerem PHP 8.2+, mas servidor tem PHP 7.4.

**Solução:** 
- Verificar versão PHP: `php -v`
- Se for PHP 7.4, ajustar `composer.json` (ver `ANALISE_COMPATIBILIDADE_PHP74.md`)
- Ou atualizar PHP no servidor para 8.2+

---

## 📋 Resumo: O Que Fazer

### ✅ **SIM, execute o Composer:**
```bash
php composer.phar install --no-dev --optimize-autoloader
```

### ❌ **NÃO precisa apagar vendor** (a menos que dê erro):
- O Composer vai verificar e atualizar automaticamente
- Só apague se o `composer install` falhar

### ✅ **Depois do Composer, verifique:**
1. Arquivo `.env` configurado corretamente
2. Banco de dados criado e migrations executadas
3. Permissões corretas
4. `.htaccess` presente

---

## 🎯 Ordem de Execução Recomendada

1. **Executar Composer** (resolve o erro atual)
2. **Configurar .env** (URL, banco de dados)
3. **Executar Migrations** (criar tabelas)
4. **Verificar Permissões**
5. **Testar Site**

---

## 💡 Dica Final

**Sempre execute o Composer após:**
- ✅ Migrar para novo servidor
- ✅ Atualizar código via Git
- ✅ Mudar versão PHP
- ✅ Ver erro de "ClassLoader.php not found"

O Composer garante que todas as dependências estejam instaladas e compatíveis com a versão PHP do servidor.


