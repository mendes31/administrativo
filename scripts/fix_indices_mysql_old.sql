-- ============================================================
-- Script para corrigir índices únicos que excedem 767 bytes
-- Versão para MySQL 5.5 e 5.6 (sem suporte a DROP INDEX IF EXISTS)
-- Problema: VARCHAR(255) com utf8mb4 = 1020 bytes > 767 bytes
-- Solução: Remover índices únicos problemáticos
-- 
-- NOTA: As colunas permanecem VARCHAR(255)
-- A validação de unicidade é feita na aplicação PHP
-- ============================================================

-- IMPORTANTE: 
-- 1. Se você está importando um dump SQL que contém CREATE INDEX,
--    execute este script DEPOIS da importação (mesmo que tenha erros)
-- 2. Se você está criando as tabelas do zero, execute este script
--    DEPOIS de criar as tabelas
-- 3. Este script REMOVE os índices únicos problemáticos
-- 4. As colunas permanecem VARCHAR(255) - validação no PHP
-- 5. Se algum índice não existir, o comando falhará - isso é normal,
--    apenas continue com os próximos comandos

-- Tabela: adms_access_levels
-- Remover índice único (coluna permanece VARCHAR(255))
ALTER TABLE `adms_access_levels` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_departments
ALTER TABLE `adms_departments` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_positions
ALTER TABLE `adms_positions` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_payment_method
ALTER TABLE `adms_payment_method` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_frequency
ALTER TABLE `adms_frequency` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_cost_center
ALTER TABLE `adms_cost_center` 
DROP INDEX `idx_unique_name`;

-- Tabela: adms_accounts_plan (tem 2 índices únicos)
ALTER TABLE `adms_accounts_plan` 
DROP INDEX `idx_unique_name`,
DROP INDEX `idx_unique_account`;

-- ============================================================
-- FIM - Índices removidos. Agora você pode importar o SQL sem erros.
-- As colunas permanecem VARCHAR(255) - validação de unicidade no PHP
-- ============================================================

