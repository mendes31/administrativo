-- ============================================================
-- Verificar phinxlog após execução das migrations
-- ============================================================

-- 1. Verificar se há migrations antigas que foram movidas
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version IN (
    20250206000000,  -- add_audit_fields_to_lgpd_consentimentos (antiga - foi movida)
    20250206090000,  -- add_adms_user_id_to_lgpd_consentimentos (antiga - foi movida)
    20250206100000,  -- add_lgpd_termo_id_to_lgpd_consentimentos (antiga - foi movida)
    20250206103000,  -- create_lgpd_consentimento_arquivos (antiga - foi movida)
    20250120130000,  -- create_adms_strategic_plan_observations (antiga - foi movida)
    20250205180000   -- add_performance_indexes_training (antiga - foi movida)
)
ORDER BY version;

-- 2. Verificar se as novas migrations estão presentes
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version IN (
    20250725181010,  -- add_audit_fields_to_lgpd_consentimentos (nova)
    20250725181020,  -- add_adms_user_id_to_lgpd_consentimentos (nova)
    20250725181030,  -- add_lgpd_termo_id_to_lgpd_consentimentos (nova)
    20250725181040,  -- create_lgpd_consentimento_arquivos (nova)
    20250710160010,  -- create_adms_strategic_plan_observations (nova)
    20260128130000   -- add_performance_indexes_training (nova)
)
ORDER BY version;

-- 3. Se encontrou migrations antigas acima, remova-as:
-- DELETE FROM phinxlog 
-- WHERE version IN (20250206000000, 20250206090000, 20250206100000, 20250206103000, 20250120130000, 20250205180000);

-- 4. Ver todas as migrations LGPD relacionadas
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE migration_name LIKE '%Lgpd%' OR migration_name LIKE '%lgpd%'
ORDER BY version;

-- 5. Ver todas as migrations de Strategic Plan
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE migration_name LIKE '%Strategic%' OR migration_name LIKE '%strategic%'
ORDER BY version;

