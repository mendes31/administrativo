# 🖥️ Comandos para Executar no Servidor via SSH

## 📍 **PASSO 1: Navegar para o Diretório do Projeto**

Você está em `~` (home), mas precisa estar no diretório do projeto:

```bash
# Verificar onde você está
pwd

# Navegar para o diretório do projeto (ajuste conforme necessário)
cd ~/www/administrativo

# OU se o projeto estiver em outro local, tente:
cd /files/administrativo
# ou
cd /home/tiaraju/public_html/administrativo
# ou
cd /var/www/administrativo

# Verificar se está no diretório correto (deve mostrar vendor, database, app, etc.)
ls -la
```

## ✅ **PASSO 2: Verificar se o Vendor Existe**

```bash
# Verificar se a pasta vendor existe
ls -la vendor/

# Se não existir, você precisa rodar o Composer primeiro
php composer.phar install --no-dev --optimize-autoloader
# OU
composer install --no-dev --optimize-autoloader
```

## 🚀 **PASSO 3: Executar as Migrations**

```bash
# Verificar status das migrations
php vendor/bin/phinx status -c database/phinx.php -e production

# Executar todas as migrations
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

## 🌱 **PASSO 4: Executar as Seeds**

```bash
# Executar todas as seeds
php vendor/bin/phinx seed:run -c database/phinx.php -e production

# OU executar seeds específicas
php vendor/bin/phinx seed:run -c database/phinx.php -e production -s AddAdmsPages
php vendor/bin/phinx seed:run -c database/phinx.php -e production -s AddDepartments
```

## ⚠️ **PROBLEMAS COMUNS**

### **1. "Could not open input file: vendor/bin/phinx"**
**Causa:** Você não está no diretório correto do projeto.

**Solução:**
```bash
# Encontrar onde está o projeto
find ~ -name "phinx.php" -type f 2>/dev/null
# ou
find / -name "database/phinx.php" -type f 2>/dev/null | head -5

# Depois navegar para o diretório encontrado
cd /caminho/encontrado/..
```

### **2. "Permission denied" no .bash_history**
**Causa:** Problema de permissões no arquivo de histórico.

**Solução (não crítico, mas pode corrigir):**
```bash
# Corrigir permissões do .bash_history
chmod 600 ~/.bash_history
# ou
touch ~/.bash_history && chmod 600 ~/.bash_history
```

### **3. Warning do New Relic**
**Causa:** Extensão PHP não disponível (não é crítico).

**Solução:** Pode ignorar, é apenas um aviso.

### **4. "vendor não existe"**
**Causa:** Composer não foi executado.

**Solução:**
```bash
# Verificar se composer.phar existe
ls -la composer.phar

# Se existir, instalar dependências
php composer.phar install --no-dev --optimize-autoloader

# Se não existir, baixar o Composer
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

## 📋 **CHECKLIST RÁPIDO**

Execute na ordem:

```bash
# 1. Navegar para o projeto
cd ~/www/administrativo  # Ajuste conforme necessário

# 2. Verificar se está no lugar certo
ls -la | grep -E "vendor|database|app|.env"

# 3. Verificar se vendor existe
test -d vendor && echo "✅ Vendor existe" || echo "❌ Precisa rodar composer install"

# 4. Executar migrations
php vendor/bin/phinx migrate -c database/phinx.php -e production

# 5. Executar seeds
php vendor/bin/phinx seed:run -c database/phinx.php -e production
```

## 🔍 **ENCONTRAR O DIRETÓRIO DO PROJETO**

Se você não souber onde está o projeto:

```bash
# Procurar pelo arquivo phinx.php
find ~ -name "phinx.php" 2>/dev/null

# Procurar pela pasta database
find ~ -type d -name "database" 2>/dev/null | grep administrativo

# Procurar pelo arquivo .env
find ~ -name ".env" 2>/dev/null | grep administrativo

# Listar diretórios comuns
ls -la ~/www/
ls -la /files/
ls -la /home/tiaraju/public_html/
```

