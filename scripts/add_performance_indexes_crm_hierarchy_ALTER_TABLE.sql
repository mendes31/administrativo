-- ============================================================
-- Script para adicionar índices de performance
-- VERSÃO ALTERNATIVA usando ALTER TABLE
-- Use este script se CREATE INDEX não funcionar por permissões
-- CRM e Hierarquia
-- ============================================================

-- IMPORTANTE: 
-- 1. Se algum índice já existir, você verá um erro - pode ignorar e continuar
-- 2. Este script usa ALTER TABLE ADD INDEX (requer menos permissões)
-- 3. Execute um comando por vez se preferir verificar erros individuais

-- ============================================================
-- ÍNDICES PARA CRM
-- ============================================================

-- Índices para crm_partners
-- Melhora filtros por responsável e JOINs
ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_responsible_user` (`responsible_user_id`);

ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_status` (`status`);

ALTER TABLE `crm_partners` 
ADD INDEX `idx_crm_partners_department` (`department_id`);

-- Índices para crm_partner_tags
-- Melhora JOINs e busca de tags por parceiro
ALTER TABLE `crm_partner_tags` 
ADD INDEX `idx_crm_partner_tags_partner` (`partner_id`);

ALTER TABLE `crm_partner_tags` 
ADD INDEX `idx_crm_partner_tags_tag` (`tag_id`);

ALTER TABLE `crm_partner_tags` 
ADD INDEX `idx_crm_partner_tags_composite` (`partner_id`, `tag_id`);

-- Índices para crm_opportunities
-- Melhora filtros por etapa e responsável
ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_stage` (`stage_id`);

ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_responsible` (`responsible_user_id`);

ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_status` (`status`);

ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_stage_status` (`stage_id`, `status`);

ALTER TABLE `crm_opportunities` 
ADD INDEX `idx_crm_opportunities_partner` (`partner_id`);

-- ============================================================
-- ÍNDICES PARA HIERARQUIA
-- ============================================================

-- Índice crítico para hierarquia (immediate_supervisor_id)
-- Melhora drasticamente queries de organograma e hierarquia
ALTER TABLE `adms_users` 
ADD INDEX `idx_users_immediate_supervisor` (`immediate_supervisor_id`);

-- Índice composto para status + supervisor
-- Melhora filtros combinados
ALTER TABLE `adms_users` 
ADD INDEX `idx_users_status_supervisor` (`status`, `immediate_supervisor_id`);

-- ============================================================
-- FIM - Índices adicionados
-- ============================================================

