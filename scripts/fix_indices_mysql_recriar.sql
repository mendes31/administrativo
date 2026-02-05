-- ============================================================
-- Script para RECRIAR índices únicos (execute DEPOIS da importação)
-- Este script limita as colunas para 191 caracteres e recria os índices
-- ============================================================

-- Tabela: adms_access_levels
ALTER TABLE `adms_access_levels` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_departments
ALTER TABLE `adms_departments` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_positions
ALTER TABLE `adms_positions` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_payment_method (já é VARCHAR(100), não precisa limitar)
ALTER TABLE `adms_payment_method` 
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_frequency
ALTER TABLE `adms_frequency` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_cost_center
ALTER TABLE `adms_cost_center` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`);

-- Tabela: adms_accounts_plan (tem 2 índices únicos)
ALTER TABLE `adms_accounts_plan` 
MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
MODIFY COLUMN `account` VARCHAR(191) NOT NULL,
ADD UNIQUE KEY `idx_unique_name` (`name`),
ADD UNIQUE KEY `idx_unique_account` (`account`);

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================

