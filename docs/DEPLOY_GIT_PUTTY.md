# Guia de Deploy - Git e Putty

Este documento contém os comandos necessários para atualizar o repositório Git e executar comandos no servidor de produção via Putty após o deploy automático.

---

## 📋 Índice

1. [Comandos Git (Local)](#comandos-git-local)
2. [Comandos Putty (Produção)](#comandos-putty-produção)
3. [Fluxo Completo de Deploy](#fluxo-completo-de-deploy)
4. [Troubleshooting](#troubleshooting)

---

## 🔄 Comandos Git (Local)

### 1. Verificar Status das Alterações

```bash
git status
```

### 2. Adicionar Arquivos ao Stage

**Adicionar arquivos específicos:**
```bash
git add caminho/do/arquivo.php
```

**Adicionar todos os arquivos modificados:**
```bash
git add .
```

**Adicionar apenas arquivos PHP modificados:**
```bash
git add *.php
```

### 3. Fazer Commit das Alterações

```bash
git commit -m "Descrição clara e objetiva das alterações realizadas"
```

**Exemplos de mensagens de commit:**
```bash
git commit -m "feat: Adiciona funcionalidade de revogação de consentimento LGPD"
git commit -m "fix: Corrige validação de consentimento no login"
git commit -m "refactor: Ajusta estrutura de rotas para LGPD"
```

### 4. Enviar Alterações para o Repositório Remoto

**Enviar para a branch atual:**
```bash
git push origin main
```

**Ou se estiver usando outra branch:**
```bash
git push origin nome-da-branch
```

### 5. Verificar Últimos Commits

```bash
git log --oneline -10
```

### 6. Ver Diferenças Antes de Commitar

```bash
git diff
```

**Ver diferenças de um arquivo específico:**
```bash
git diff caminho/do/arquivo.php
```

---

## 🖥️ Comandos Putty (Produção)

> **⚠️ IMPORTANTE:** O deploy automático via GitHub Actions já atualiza os arquivos no servidor. Os comandos abaixo devem ser executados **após** o deploy automático ser concluído.

### 1. Conectar ao Servidor via SSH

```bash
ssh administrativotiaraju@web1102.kinghost.net
```

Ou use o Putty com as credenciais configuradas.

### 2. Navegar até o Diretório do Projeto

```bash
cd ~/www/administrativo
```

**Verificar se está no diretório correto:**
```bash
pwd
```

**Listar arquivos do diretório:**
```bash
ls -la
```

### 3. Verificar Status do Git no Servidor

```bash
git status
```

**Ver último commit aplicado:**
```bash
git log --oneline -1
```

### 4. Atualizar Dependências do Composer

**Instalar/atualizar dependências (modo produção):**
```bash
php composer.phar install --no-dev --optimize-autoloader
```

**Ou se o Composer estiver instalado globalmente:**
```bash
composer install --no-dev --optimize-autoloader
```

### 5. Regenerar Autoloader do Composer

```bash
php composer.phar dump-autoload --optimize
```

**Ou:**
```bash
composer dump-autoload --optimize
```

### 6. Executar Migrations (se necessário)

**Verificar migrations pendentes:**
```bash
php vendor/bin/phinx status -c database/phinx.php
```

**Executar migrations pendentes:**
```bash
php vendor/bin/phinx migrate -c database/phinx.php
```

**⚠️ ATENÇÃO:** Execute migrations apenas se houver novas migrations no deploy.

### 7. Executar Seeds (se necessário)

**Executar todos os seeds:**
```bash
php vendor/bin/phinx seed:run -c database/phinx.php
```

**Executar seed específico:**
```bash
php vendor/bin/phinx seed:run -c database/phinx.php -s NomeDoSeed
```

**⚠️ ATENÇÃO:** Seeds geralmente não precisam ser executados em produção, apenas em casos específicos.

### 8. Verificar Permissões de Arquivos (se necessário)

**Verificar permissões:**
```bash
ls -la app/logs/
```

**Ajustar permissões de diretórios de log (se necessário):**
```bash
chmod 755 app/logs/
chmod 644 app/logs/*.log
```

### 9. Limpar Cache (se aplicável)

**Limpar cache de opcode PHP (se configurado):**
```bash
php -r "opcache_reset();"
```

**Ou reiniciar o serviço PHP-FPM (se tiver acesso):**
```bash
sudo service php-fpm restart
```

### 10. Verificar Logs de Erro

**Ver últimas linhas do log de erros do PHP:**
```bash
tail -n 50 /var/log/php_errors.log
```

**Ou verificar logs da aplicação:**
```bash
tail -n 50 app/logs/login_debug.log
```

---

## 🔄 Fluxo Completo de Deploy

### Passo a Passo Completo

1. **No ambiente local (desenvolvimento):**
   ```bash
   # 1. Verificar alterações
   git status
   
   # 2. Adicionar arquivos
   git add .
   
   # 3. Fazer commit
   git commit -m "Descrição das alterações"
   
   # 4. Enviar para o repositório
   git push origin main
   ```

2. **Aguardar o deploy automático via GitHub Actions**
   - O GitHub Actions fará o deploy automaticamente via FTP
   - Aguardar a conclusão do workflow no GitHub

3. **No servidor de produção (via Putty):**
   ```bash
   # 1. Conectar ao servidor
   ssh administrativotiaraju@web1102.kinghost.net
   
   # 2. Navegar até o projeto
   cd ~/www/administrativo
   
   # 3. Verificar se os arquivos foram atualizados
   git log --oneline -1
   
   # 4. Atualizar dependências
   php composer.phar install --no-dev --optimize-autoloader
   
   # 5. Regenerar autoloader
   php composer.phar dump-autoload --optimize
   
   # 6. Executar migrations (se houver novas)
   php vendor/bin/phinx migrate -c database/phinx.php
   
   # 7. Verificar logs (opcional)
   tail -n 20 app/logs/login_debug.log
   ```

---

## 🔧 Troubleshooting

### Problema: Arquivos não foram atualizados no servidor

**Solução:**
1. Verificar se o GitHub Actions concluiu com sucesso
2. Verificar se o deploy automático está configurado corretamente
3. Verificar permissões de escrita no diretório do projeto

### Problema: Erro ao executar composer

**Solução:**
```bash
# Verificar se o composer.phar existe
ls -la composer.phar

# Se não existir, baixar novamente
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

### Problema: Erro ao executar migrations

**Solução:**
```bash
# Verificar status das migrations
php vendor/bin/phinx status -c database/phinx.php

# Verificar configuração do banco de dados no .env
cat .env | grep DB_
```

### Problema: Permissões negadas

**Solução:**
```bash
# Verificar usuário atual
whoami

# Verificar permissões do diretório
ls -la

# Se necessário, ajustar permissões (com cuidado)
chmod -R 755 .
chmod -R 644 *.php
```

### Problema: Autoloader não encontrado

**Solução:**
```bash
# Regenerar autoloader
php composer.phar dump-autoload --optimize

# Verificar se o vendor/autoload.php existe
ls -la vendor/autoload.php
```

---

## 📝 Notas Importantes

1. **Sempre faça backup antes de executar migrations em produção**
2. **Teste as alterações em ambiente de desenvolvimento antes de fazer deploy**
3. **Verifique os logs após cada deploy para garantir que não há erros**
4. **Mantenha o `.env` atualizado com as configurações corretas de produção**
5. **Não commite arquivos sensíveis (`.env`, senhas, chaves) no Git**

---

## 🔗 Referências

- [Documentação do Git](https://git-scm.com/doc)
- [Documentação do Composer](https://getcomposer.org/doc/)
- [Documentação do Phinx](https://book.cakephp.org/phinx/0/en/index.html)
- [GitHub Actions - Deploy Workflow](.github/workflows/deploy.yml)

---

**Última atualização:** 28/01/2026

