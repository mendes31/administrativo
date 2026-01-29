# Troubleshooting - Página WhatsApp Config em Branco

## Problema
A página `whatsapp-config` está aparecendo em branco em produção, mesmo logado como Super Administrador.

## Possíveis Causas

### 1. Tabela `adms_whatsapp_config` não existe
**Sintoma:** Página em branco, erro silencioso no log.

**Solução:**
```bash
# No servidor (via Putty)
cd www/administrativo
php vendor/bin/phinx migrate -c database/phinx.php
```

### 2. Erro de permissão na página
**Sintoma:** Página em branco, usuário não tem permissão.

**Solução:**
- Verificar se a página está registrada em `adms_pages`
- Verificar se o Super Admin tem permissão em `adms_access_levels_pages`

### 3. Erro PHP fatal não capturado
**Sintoma:** Página em branco, erro no log do PHP.

**Solução:**
- Verificar logs do PHP em produção
- Verificar se todas as classes estão sendo carregadas corretamente

## Diagnóstico Passo a Passo

### Passo 1: Verificar se a tabela existe
```sql
-- No banco de dados
SHOW TABLES LIKE 'adms_whatsapp_config';
```

Se não existir, execute a migration:
```bash
php vendor/bin/phinx migrate -c database/phinx.php
```

### Passo 2: Verificar se a página está registrada
```sql
-- Verificar se a página existe
SELECT * FROM adms_pages WHERE controller_url = 'whatsapp-config';
```

Se não existir, execute a seed:
```bash
php vendor/bin/phinx seed:run -c database/phinx.php -s AddAdmsPages
```

### Passo 3: Verificar permissões do Super Admin
```sql
-- Verificar permissão do Super Admin (ID = 1)
SELECT * FROM adms_access_levels_pages 
WHERE adms_access_level_id = 1 
AND adms_page_id = (SELECT id FROM adms_pages WHERE controller_url = 'whatsapp-config');
```

Se não existir, execute:
```bash
php vendor/bin/phinx seed:run -c database/phinx.php -s SyncAccessLevelsPages
```

### Passo 4: Verificar logs de erro
```bash
# No servidor
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
# ou verificar logs do PHP
tail -f app/logs/*.log
```

### Passo 5: Testar diretamente o controller
Acesse via URL direta e verifique o erro:
```
http://www.administrativotiaraju.kinghost.net/administrativo/whatsapp-config
```

## Correções Aplicadas

### 1. Tratamento de Erro no Controller
- Adicionado `try-catch` para capturar erros
- Log de erros detalhado
- Redirecionamento seguro em caso de erro

### 2. Tratamento de Erro no Repository
- Adicionado `try-catch` no método `getConfig()`
- Retorna array vazio se a tabela não existir
- Log de erros para diagnóstico

### 3. Migration Protegida
- Adicionado `hasTable()` para evitar erro se a tabela já existir
- Migration pode ser executada múltiplas vezes sem erro

## Comandos de Correção Rápida

```bash
# 1. Executar migrations pendentes
php vendor/bin/phinx migrate -c database/phinx.php

# 2. Executar seeds necessárias
php vendor/bin/phinx seed:run -c database/phinx.php -s AddAdmsPages
php vendor/bin/phinx seed:run -c database/phinx.php -s SyncAccessLevelsPages

# 3. Verificar se tudo está OK
php vendor/bin/phinx status -c database/phinx.php
```

## Verificação Final

Após executar os comandos acima, verifique:

1. ✅ Tabela `adms_whatsapp_config` existe
2. ✅ Página `whatsapp-config` está em `adms_pages`
3. ✅ Super Admin tem permissão na página
4. ✅ Controller `WhatsAppConfig` existe e está acessível
5. ✅ View `whatsappConfig.php` existe

## Se o Problema Persistir

1. Verificar logs de erro do PHP/Apache
2. Verificar se há erros de sintaxe no código
3. Verificar se o autoloader está atualizado:
   ```bash
   composer dump-autoload --optimize
   ```
4. Verificar permissões de arquivo no servidor
5. Verificar se há cache do navegador (limpar cache)

