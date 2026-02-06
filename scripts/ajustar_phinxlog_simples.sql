-- ============================================================
-- Script SIMPLES para ajustar phinxlog após reorganização
-- Execute este script no phpMyAdmin ou via linha de comando
-- ============================================================

-- 1. Verificar se as migrations antigas foram executadas
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version IN (20250206000000, 20250206090000, 20250206100000, 20250206103000, 20250120130000, 20250205180000)
ORDER BY version;

-- 2. Se encontrou resultados acima, execute os comandos abaixo:

-- Remover entradas antigas
DELETE FROM phinxlog 
WHERE version IN (20250206000000, 20250206090000, 20250206100000, 20250206103000, 20250120130000, 20250205180000);

-- Inserir novas migrations (apenas se não existirem)
INSERT IGNORE INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
VALUES
(20250725181010, 'AddAuditFieldsToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181020, 'AddAdmsUserIdToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181030, 'AddLgpdTermoIdToLgpdConsentimentos', NOW(), NOW(), 0),
(20250725181040, 'CreateLgpdConsentimentoArquivos', NOW(), NOW(), 0),
(20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0),
(20260128130000, 'AddPerformanceIndexesTraining', NOW(), NOW(), 0);

-- 3. Verificar resultado
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version IN (20250725181010, 20250725181020, 20250725181030, 20250725181040, 20250710160010, 20260128130000)
ORDER BY version;

