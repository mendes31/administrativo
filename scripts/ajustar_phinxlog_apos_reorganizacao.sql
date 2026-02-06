-- ============================================================
-- Script para ajustar phinxlog após reorganização de migrations
-- 
-- Este script:
-- 1. Remove entradas das migrations antigas que foram movidas
-- 2. Insere entradas para as novas migrations (marcando como executadas)
-- ============================================================

-- Verificar migrations executadas relacionadas às que foram movidas
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

-- ============================================================
-- OPÇÃO 1: Se as migrations antigas foram executadas,
-- remover do phinxlog e inserir as novas
-- ============================================================

-- Remover entradas antigas
DELETE FROM phinxlog 
WHERE version IN (
    20250206000000,  -- add_audit_fields_to_lgpd_consentimentos
    20250206090000,  -- add_adms_user_id_to_lgpd_consentimentos
    20250206100000,  -- add_lgpd_termo_id_to_lgpd_consentimentos
    20250206103000,  -- create_lgpd_consentimento_arquivos
    20250120130000,  -- create_adms_strategic_plan_observations
    20250205180000   -- add_performance_indexes_training
);

-- Inserir novas migrations (marcando como já executadas)
-- Apenas se não existirem ainda
INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
SELECT * FROM (
    SELECT 20250725181010 as version, 'AddAuditFieldsToLgpdConsentimentos' as migration_name, NOW() as start_time, NOW() as end_time, 0 as breakpoint
    UNION ALL
    SELECT 20250725181020, 'AddAdmsUserIdToLgpdConsentimentos', NOW(), NOW(), 0
    UNION ALL
    SELECT 20250725181030, 'AddLgpdTermoIdToLgpdConsentimentos', NOW(), NOW(), 0
    UNION ALL
    SELECT 20250725181040, 'CreateLgpdConsentimentoArquivos', NOW(), NOW(), 0
    UNION ALL
    SELECT 20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0
    UNION ALL
    SELECT 20260128130000, 'AddPerformanceIndexesTraining', NOW(), NOW(), 0
) AS novas
WHERE NOT EXISTS (
    SELECT 1 FROM phinxlog WHERE phinxlog.version = novas.version
);

-- ============================================================
-- OPÇÃO 2: Se preferir fazer manualmente, use estes comandos:
-- ============================================================

-- Ver todas as migrations executadas
-- SELECT * FROM phinxlog ORDER BY version DESC LIMIT 20;

-- Verificar se alguma das novas migrations já foi executada
-- SELECT * FROM phinxlog 
-- WHERE version IN (20250725181010, 20250725181020, 20250725181030, 20250725181040, 20250710160010, 20260128130000);

-- ============================================================
-- FIM
-- ============================================================

