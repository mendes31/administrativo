# 📋 Comandos Phinx - Guia de Referência

## 🔍 Verificar Status das Migrations

Para verificar quais migrations foram executadas e quais estão pendentes:

```bash
php vendor/bin/phinx status -c database/phinx.php -e production
```

**Saída esperada:**
- `up` = Migration executada ✅
- `down` = Migration pendente ⏳

**Exemplo de saída:**
```
Status  Migration ID    Migration Name
------  --------------  -------------------------
up      20250120120000  FixAddCpfCelularToAdmsUsers
up      20250128193544  AdmsUsers
down    20251023150003  CreateAdmsEvaluationAttempts
```

## 🚀 Executar Migrations

Executar todas as migrations pendentes:

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

Executar até uma migration específica:

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20251023150003
```

## ⏪ Rollback (Reverter)

Reverter a última migration executada:

```bash
php vendor/bin/phinx rollback -c database/phinx.php -e production
```

Reverter até uma migration específica:

```bash
php vendor/bin/phinx rollback -c database/phinx.php -e production -t 20250128193544
```

## 🌱 Seeds (Dados Iniciais)

Executar todas as seeds:

```bash
php vendor/bin/phinx seed:run -c database/phinx.php -e production
```

Executar uma seed específica:

```bash
php vendor/bin/phinx seed:run -c database/phinx.php -e production -s NomeDaSeed
```

## 📝 Criar Nova Migration

```bash
php vendor/bin/phinx create NomeDaMigration -c database/phinx.php
```

## 📝 Criar Nova Seed

```bash
php vendor/bin/phinx seed:create NomeDaSeed -c database/phinx.php
```

## 🔧 Ambientes

O projeto suporta múltiplos ambientes:
- `development` - Desenvolvimento local
- `production` - Produção

Sempre especifique o ambiente com `-e`:
```bash
-e production  # Para produção
-e development # Para desenvolvimento (padrão)
```

## ⚠️ Dicas Importantes

1. **Sempre verifique o status antes de executar migrations:**
   ```bash
   php vendor/bin/phinx status -c database/phinx.php -e production
   ```

2. **Faça backup antes de executar migrations em produção:**
   ```bash
   mysqldump -h mysql.tiaraju.com.br -u tiaraju004_add1 -p tiaraju04 > backup_antes_migration.sql
   ```

3. **Se houver erros, verifique os logs:**
   - Logs do Phinx aparecem no terminal
   - Logs do MySQL podem estar em `/var/log/mysql/error.log` (servidor)

4. **Para ver detalhes de uma migration específica:**
   ```bash
   php vendor/bin/phinx status -c database/phinx.php -e production | grep NomeDaMigration
   ```

