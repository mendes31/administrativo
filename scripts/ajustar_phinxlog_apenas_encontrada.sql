-- ============================================================
-- Ajustar phinxlog - Apenas para a migration encontrada
-- 
-- Encontrada: 20250120130000 (CreateAdmsStrategicPlanObservations)
-- Nova data: 20250710160010
-- ============================================================

-- Remover entrada antiga
DELETE FROM phinxlog 
WHERE version = 20250120130000;

-- Inserir nova migration (marcando como executada)
INSERT IGNORE INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
VALUES
(20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0);

-- Verificar resultado
SELECT version, migration_name, start_time 
FROM phinxlog 
WHERE version = 20250710160010;

-- ============================================================
-- IMPORTANTE: 
-- As migrations LGPD (20250725181010, 20250725181020, etc.)
-- NÃO foram executadas ainda, então NÃO precisam ser inseridas
-- no phinxlog. O Phinx vai executá-las normalmente.
-- ============================================================

