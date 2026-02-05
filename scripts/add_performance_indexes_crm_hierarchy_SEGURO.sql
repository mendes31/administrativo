-- ============================================================
-- Script para adicionar índices de performance
-- VERSÃO SEGURA - Verifica se existe antes de criar
-- CRM e Hierarquia
-- ============================================================

-- IMPORTANTE: 
-- Este script verifica se o índice já existe antes de criar
-- Evita erros de "Duplicate key name"
-- Compatível com MySQL 5.5+

-- ============================================================
-- ÍNDICES PARA CRM
-- ============================================================

-- Índices para crm_partners
-- Verificar e criar idx_crm_partners_responsible_user
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partners' 
    AND INDEX_NAME = 'idx_crm_partners_responsible_user'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partners` ADD INDEX `idx_crm_partners_responsible_user` (`responsible_user_id`)',
    'SELECT "Índice idx_crm_partners_responsible_user já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_partners_status
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partners' 
    AND INDEX_NAME = 'idx_crm_partners_status'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partners` ADD INDEX `idx_crm_partners_status` (`status`)',
    'SELECT "Índice idx_crm_partners_status já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_partners_department
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partners' 
    AND INDEX_NAME = 'idx_crm_partners_department'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partners` ADD INDEX `idx_crm_partners_department` (`department_id`)',
    'SELECT "Índice idx_crm_partners_department já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índices para crm_partner_tags
-- Verificar e criar idx_crm_partner_tags_partner
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partner_tags' 
    AND INDEX_NAME = 'idx_crm_partner_tags_partner'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partner_tags` ADD INDEX `idx_crm_partner_tags_partner` (`partner_id`)',
    'SELECT "Índice idx_crm_partner_tags_partner já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_partner_tags_tag
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partner_tags' 
    AND INDEX_NAME = 'idx_crm_partner_tags_tag'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partner_tags` ADD INDEX `idx_crm_partner_tags_tag` (`tag_id`)',
    'SELECT "Índice idx_crm_partner_tags_tag já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_partner_tags_composite
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_partner_tags' 
    AND INDEX_NAME = 'idx_crm_partner_tags_composite'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_partner_tags` ADD INDEX `idx_crm_partner_tags_composite` (`partner_id`, `tag_id`)',
    'SELECT "Índice idx_crm_partner_tags_composite já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Índices para crm_opportunities
-- Verificar e criar idx_crm_opportunities_stage
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_opportunities' 
    AND INDEX_NAME = 'idx_crm_opportunities_stage'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_opportunities` ADD INDEX `idx_crm_opportunities_stage` (`stage_id`)',
    'SELECT "Índice idx_crm_opportunities_stage já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_opportunities_responsible
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_opportunities' 
    AND INDEX_NAME = 'idx_crm_opportunities_responsible'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_opportunities` ADD INDEX `idx_crm_opportunities_responsible` (`responsible_user_id`)',
    'SELECT "Índice idx_crm_opportunities_responsible já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_opportunities_status
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_opportunities' 
    AND INDEX_NAME = 'idx_crm_opportunities_status'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_opportunities` ADD INDEX `idx_crm_opportunities_status` (`status`)',
    'SELECT "Índice idx_crm_opportunities_status já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_opportunities_stage_status
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_opportunities' 
    AND INDEX_NAME = 'idx_crm_opportunities_stage_status'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_opportunities` ADD INDEX `idx_crm_opportunities_stage_status` (`stage_id`, `status`)',
    'SELECT "Índice idx_crm_opportunities_stage_status já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_crm_opportunities_partner
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'crm_opportunities' 
    AND INDEX_NAME = 'idx_crm_opportunities_partner'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `crm_opportunities` ADD INDEX `idx_crm_opportunities_partner` (`partner_id`)',
    'SELECT "Índice idx_crm_opportunities_partner já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- ÍNDICES PARA HIERARQUIA
-- ============================================================

-- Verificar e criar idx_users_immediate_supervisor
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'adms_users' 
    AND INDEX_NAME = 'idx_users_immediate_supervisor'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `adms_users` ADD INDEX `idx_users_immediate_supervisor` (`immediate_supervisor_id`)',
    'SELECT "Índice idx_users_immediate_supervisor já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e criar idx_users_status_supervisor
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'adms_users' 
    AND INDEX_NAME = 'idx_users_status_supervisor'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `adms_users` ADD INDEX `idx_users_status_supervisor` (`status`, `immediate_supervisor_id`)',
    'SELECT "Índice idx_users_status_supervisor já existe" as mensagem'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- FIM - Índices adicionados
-- ============================================================

SELECT 'Script executado com sucesso! Verifique os índices criados.' as resultado;

