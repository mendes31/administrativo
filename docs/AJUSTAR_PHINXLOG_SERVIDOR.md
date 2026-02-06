# 🔧 Ajustar phinxlog Após Reorganização de Migrations

## 📋 Situação

As migrations foram reorganizadas (movidas para novas datas), mas no servidor as migrations antigas já foram executadas e estão registradas no `phinxlog`. Precisamos ajustar o `phinxlog` para refletir as novas datas.

## 🔍 Passo 1: Verificar o que foi executado

Execute no servidor (via phpMyAdmin ou linha de comando):

```sql
-- Ver migrations relacionadas às que foram movidas
SELECT * FROM phinxlog 
WHERE version IN (
    20250206000000,  -- add_audit_fields_to_lgpd_consentimentos (antiga)
    20250206090000,  -- add_adms_user_id_to_lgpd_consentimentos (antiga)
    20250206100000,  -- add_lgpd_termo_id_to_lgpd_consentimentos (antiga)
    20250206103000,  -- create_lgpd_consentimento_arquivos (antiga)
    20250120130000,  -- create_adms_strategic_plan_observations (antiga)
    20250205180000   -- add_performance_indexes_training (antiga)
)
ORDER BY version;
```

## ✅ Passo 2: Ajustar o phinxlog

### Opção A: Via SQL (Recomendado)

Execute o script SQL `scripts/ajustar_phinxlog_apos_reorganizacao.sql` no phpMyAdmin ou via linha de comando:

```bash
mysql -h mysql.tiaraju.com.br -u tiaraju004_add1 -p tiaraju04 < scripts/ajustar_phinxlog_apos_reorganizacao.sql
```

### Opção B: Manualmente via phpMyAdmin

1. **Remover entradas antigas:**
```sql
DELETE FROM phinxlog 
WHERE version IN (
    20250206000000,
    20250206090000,
    20250206100000,
    20250206103000,
    20250120130000,
    20250205180000
);
```

2. **Inserir novas entradas (marcando como executadas):**
```sql
INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
VALUES
(20250725181010, 'AddAuditFieldsToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181020, 'AddAdmsUserIdToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181030, 'AddLgpdTermoIdToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181040, 'CreateLgpdConsentimentoArquivos', NOW(), NOW(), 0),
(20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0),
(20260128130000, 'AddPerformanceIndexesTraining', NOW(), NOW(), 0);
```

## 🚀 Passo 3: Executar Migrations

Após ajustar o `phinxlog`, execute as migrations normalmente:

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

O Phinx vai detectar que as migrations reorganizadas já foram executadas (pelo `phinxlog`) e vai continuar com as migrations pendentes.

## ⚠️ Importante

- Se alguma das novas migrations **NÃO** foi executada ainda, **NÃO** insira no `phinxlog` - deixe o Phinx executá-la normalmente.
- Este ajuste é necessário apenas se as migrations antigas já foram executadas no servidor.
- Se você está criando o banco do zero, não precisa fazer este ajuste.

## 🔍 Verificar Status

Após o ajuste, verifique o status:

```bash
php vendor/bin/phinx status -c database/phinx.php -e production
```

Todas as migrations reorganizadas devem aparecer como `up` (executadas).

