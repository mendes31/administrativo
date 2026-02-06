# Guia de Restauração do Banco de Dados

## 📋 Situação
Você apagou todos os registros do banco de dados na hospedagem e precisa:
1. Criar a estrutura das tabelas (migrations)
2. Registrar as páginas e dados iniciais (seeds)
3. Importar um backup de outra base

## ✅ Processo Recomendado

### **Opção 1: Migrations → Backup → Seeds (Recomendado)**

Esta é a abordagem mais segura, pois:
- As seeds são **idempotentes** (verificam se já existe antes de inserir)
- O backup pode ter dados que as seeds não têm
- As seeds só preenchem o que está faltando

#### Passo 1: Rodar as Migrations
```bash
# No servidor (via SSH ou terminal)
cd /caminho/do/projeto
php vendor/bin/phinx migrate -e production
```

Isso criará toda a estrutura das tabelas (CREATE TABLE).

#### Passo 2: Importar o Backup
```bash
# Via phpMyAdmin ou linha de comando
mysql -h mysql.tiaraju.com.br -u tiaraju004_add1 -p administrativo < backup.sql
```

**⚠️ IMPORTANTE:** 
- O backup deve conter apenas os **dados** (INSERT), não a estrutura (CREATE TABLE)
- Se o backup contiver CREATE TABLE, pode haver conflitos com as migrations

#### Passo 3: Rodar as Seeds (Opcional, mas Recomendado)
```bash
# Rodar todas as seeds
php vendor/bin/phinx seed:run -e production

# Ou rodar seeds específicas
php vendor/bin/phinx seed:run -s AddAdmsPages -e production
php vendor/bin/phinx seed:run -s AddDepartments -e production
php vendor/bin/phinx seed:run -s AddAdmsPositions -e production
```

As seeds **não vão duplicar** dados que já existem, pois verificam antes de inserir:
- `AddAdmsPages` verifica por `controller_url`
- `AddDepartments` verifica por `name`
- Outras seeds têm verificações similares

---

### **Opção 2: Migrations → Seeds → Backup (Alternativa)**

Use esta opção se:
- O backup não contém páginas do sistema
- Você quer garantir que todas as páginas estejam cadastradas antes de importar dados

#### Passo 1: Rodar as Migrations
```bash
php vendor/bin/phinx migrate -e production
```

#### Passo 2: Rodar as Seeds
```bash
php vendor/bin/phinx seed:run -e production
```

#### Passo 3: Importar o Backup
```bash
mysql -h mysql.tiaraju.com.br -u tiaraju004_add1 -p administrativo < backup.sql
```

**⚠️ ATENÇÃO:** 
- Se o backup contiver páginas com IDs diferentes, pode haver conflitos
- Verifique se o backup não sobrescreve dados importantes das seeds

---

## 🔍 Verificações Importantes

### 1. Verificar se as Migrations foram aplicadas
```sql
SELECT * FROM phinxlog ORDER BY start_time DESC;
```

### 2. Verificar se as Páginas foram cadastradas
```sql
SELECT COUNT(*) as total_paginas FROM adms_pages;
SELECT name, controller_url FROM adms_pages ORDER BY id LIMIT 10;
```

### 3. Verificar se os Departamentos foram criados
```sql
SELECT * FROM adms_departments;
```

### 4. Verificar se os Usuários foram importados
```sql
SELECT COUNT(*) as total_usuarios FROM adms_users;
```

---

## ⚠️ Problemas Comuns e Soluções

### Problema 1: "Table already exists" ao rodar migrations
**Causa:** O backup contém comandos CREATE TABLE.

**Solução:** 
- Use um backup apenas com dados (INSERT), não com estrutura
- Ou rode `php vendor/bin/phinx migrate -e production` com `--dry-run` primeiro para ver o que será executado

### Problema 2: "Duplicate entry" ao rodar seeds
**Causa:** O backup já contém os dados que as seeds tentam inserir.

**Solução:** 
- Isso é normal! As seeds verificam antes de inserir, então não devem dar erro
- Se der erro, verifique se há constraints UNIQUE no banco

### Problema 3: Páginas não aparecem no sistema
**Causa:** As páginas não foram cadastradas ou não têm permissões vinculadas.

**Solução:**
```bash
# Rodar seed de páginas
php vendor/bin/phinx seed:run -s AddAdmsPages -e production

# Rodar seed de sincronização de permissões
php vendor/bin/phinx seed:run -s SyncAccessLevelsPages -e production
```

### Problema 4: IDs diferentes entre backup e seeds
**Causa:** O backup tem IDs diferentes dos esperados pelas seeds.

**Solução:**
- As seeds usam verificações por campos únicos (não por ID), então isso não deve ser problema
- Se houver foreign keys quebradas, você precisará ajustar manualmente

---

## 📝 Checklist Final

Após restaurar o banco, verifique:

- [ ] Todas as migrations foram aplicadas (`SELECT * FROM phinxlog`)
- [ ] Páginas do sistema estão cadastradas (`SELECT COUNT(*) FROM adms_pages`)
- [ ] Usuários foram importados (`SELECT COUNT(*) FROM adms_users`)
- [ ] Departamentos existem (`SELECT * FROM adms_departments`)
- [ ] Permissões estão vinculadas (`SELECT * FROM adms_access_levels_pages LIMIT 10`)
- [ ] Sistema está acessível e funcionando

---

## 🚀 Comandos Rápidos

```bash
# 1. Migrations
php vendor/bin/phinx migrate -e production

# 2. Seeds principais
php vendor/bin/phinx seed:run -s AddDepartments -e production
php vendor/bin/phinx seed:run -s AddAdmsPositions -e production
php vendor/bin/phinx seed:run -s AddAdmsPages -e production
php vendor/bin/phinx seed:run -s SyncAccessLevelsPages -e production

# 3. Verificar status
php vendor/bin/phinx status -e production
```

---

## 📞 Suporte

Se encontrar problemas:
1. Verifique os logs: `app/logs/*.log`
2. Verifique o status das migrations: `php vendor/bin/phinx status -e production`
3. Verifique se o `.env` está configurado corretamente para produção

