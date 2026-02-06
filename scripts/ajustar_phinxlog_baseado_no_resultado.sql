-- ============================================================
-- Script para ajustar phinxlog baseado no resultado encontrado
-- 
-- Encontrado apenas: 20250120130000 (CreateAdmsStrategicPlanObservations)
-- ============================================================

-- 1. Remover apenas a entrada antiga encontrada
DELETE FROM phinxlog 
WHERE version = 20250120130000;

-- 2. Inserir a nova migration (marcando como executada)
-- A migration foi movida de 20250120130000 para 20250710160010
INSERT IGNORE INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
VALUES
(20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0);

-- 3. Verificar resultado
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version = 20250710160010;

-- ============================================================
-- NOTA: As outras migrations (LGPD) não foram encontradas no phinxlog,
-- então elas NÃO foram executadas ainda. O Phinx vai executá-las
-- normalmente quando você rodar: php vendor/bin/phinx migrate
-- ============================================================

