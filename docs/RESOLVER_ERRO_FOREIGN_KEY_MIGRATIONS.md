# 🔧 Resolver Erro: Cannot add foreign key constraint

## ❌ Erro Encontrado

```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint
```

**Causa:** A migration está tentando adicionar foreign keys, mas:
1. A tabela referenciada não existe
2. A coluna referenciada não existe
3. Os tipos de dados são incompatíveis
4. Há dados na tabela que violam a foreign key

## 🔍 Diagnóstico

### Verificar se as tabelas existem:

```sql
-- No phpMyAdmin, execute:
SHOW TABLES LIKE 'adms_strategic%';
SHOW TABLES LIKE 'adms_users';
SHOW TABLES LIKE 'adms_departments';
```

### Verificar estrutura das tabelas:

```sql
-- Ver estrutura de adms_strategic_plans
DESCRIBE adms_strategic_plans;

-- Ver estrutura de adms_users
DESCRIBE adms_users;

-- Ver estrutura de adms_departments
DESCRIBE adms_departments;
```

## ✅ Soluções

### Solução 1: Executar Migrations na Ordem Correta

O problema é que você importou um backup antigo que não tinha todas as tabelas. As migrations precisam ser executadas na ordem correta:

1. **Primeiro, execute a migration que cria `adms_strategic_plans`:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20250710160000
   ```

2. **Depois, execute a migration que cria `adms_strategic_plan_observations`:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20250710160010
   ```

### Solução 2: Verificar Dependências

A migration `20250710160000` cria `adms_strategic_plans` mas depende de:
- `adms_departments` (foreign key)
- `adms_users` (foreign key)

**Verificar se existem:**
```sql
SELECT COUNT(*) FROM adms_departments;
SELECT COUNT(*) FROM adms_users;
```

Se não existirem ou estiverem vazias, você precisa:
1. Executar as migrations que criam essas tabelas primeiro
2. Ou criar registros iniciais (seeds)

### Solução 3: Pular Foreign Keys Temporariamente

Se você só quer criar a estrutura sem foreign keys:

1. **Modificar temporariamente a migration** para não adicionar foreign keys
2. **Executar as migrations**
3. **Adicionar foreign keys depois** com uma migration separada

### Solução 4: Criar Tabelas Manualmente (Se Backup Não Tinha)

Se o backup antigo não tinha `adms_strategic_plans`, você pode:

1. **Executar apenas a migration que cria a tabela:**
   ```bash
   php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20250710160000
   ```

2. **Se der erro de foreign key, criar a tabela sem foreign keys primeiro:**
   ```sql
   -- No phpMyAdmin, execute:
   CREATE TABLE IF NOT EXISTS `adms_strategic_plans` (
       `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
       `department_id` INT UNSIGNED NOT NULL,
       `responsible_id` INT UNSIGNED NOT NULL,
       `title` VARCHAR(255) NOT NULL,
       -- ... outros campos
       PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

3. **Depois adicionar foreign keys:**
   ```sql
   ALTER TABLE `adms_strategic_plans`
   ADD CONSTRAINT `fk_strategic_plans_department`
       FOREIGN KEY (`department_id`) REFERENCES `adms_departments` (`id`)
       ON DELETE RESTRICT ON UPDATE CASCADE;
   ```

## 🎯 Solução Recomendada (Passo a Passo)

### 1. Verificar Status das Migrations

```bash
php vendor/bin/phinx status -c database/phinx.php -e production
```

### 2. Identificar Qual Migration Falhou

No seu caso, foi `20250710160010` (CreateAdmsStrategicPlanObservations).

### 3. Verificar Dependências

```sql
-- Verificar se adms_strategic_plans existe
SHOW TABLES LIKE 'adms_strategic_plans';

-- Se não existir, verificar se a migration anterior foi executada
SELECT * FROM phinxlog WHERE migration_name LIKE '%StrategicPlan%';
```

### 4. Executar Migration da Tabela Pai Primeiro

```bash
# Executar migration que cria adms_strategic_plans
php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20250710160000
```

### 5. Se Der Erro, Verificar Tabelas Referenciadas

```sql
-- Verificar se adms_departments existe e tem dados
SELECT COUNT(*) FROM adms_departments;

-- Verificar se adms_users existe e tem dados
SELECT COUNT(*) FROM adms_users;
```

### 6. Executar Migration da Tabela Filha

```bash
# Agora executar migration que cria adms_strategic_plan_observations
php vendor/bin/phinx migrate -c database/phinx.php -e production -t 20250710160010
```

## 🔧 Correção Aplicada

A migration `20250710160010` foi corrigida para:
- ✅ Verificar se as tabelas referenciadas existem
- ✅ Verificar se as colunas 'id' existem
- ✅ Verificar se as foreign keys já existem antes de adicionar
- ✅ Não falhar se não conseguir adicionar foreign keys (pode adicionar depois)

## 📋 Checklist

- [ ] Verificar se `adms_strategic_plans` existe
- [ ] Verificar se `adms_departments` existe e tem dados
- [ ] Verificar se `adms_users` existe e tem dados
- [ ] Executar migration `20250710160000` primeiro
- [ ] Executar migration `20250710160010` depois
- [ ] Verificar se as foreign keys foram criadas

## 💡 Dica: Ordem de Execução

Para evitar problemas, execute as migrations nesta ordem:

1. Tabelas **pais** (sem dependências)
2. Tabelas **filhas** (com foreign keys)

**Exemplo:**
```
1. adms_departments (sem dependências)
2. adms_users (depende de departments)
3. adms_strategic_plans (depende de departments e users)
4. adms_strategic_plan_observations (depende de strategic_plans e users)
```

