-- ============================================================
-- Script para adicionar índices de performance
-- VERSÃO PARA phpMyAdmin - Execute este script na aba SQL
-- CRM e Hierarquia
-- ============================================================

-- IMPORTANTE: Se algum índice já existir, você pode ignorar o erro
-- ou removê-lo manualmente antes de executar

-- ============================================================
-- ÍNDICES PARA CRM
-- ============================================================

-- Índices para crm_partners
CREATE INDEX `idx_crm_partners_responsible_user` 
ON `crm_partners` (`responsible_user_id`);

CREATE INDEX `idx_crm_partners_status` 
ON `crm_partners` (`status`);

CREATE INDEX `idx_crm_partners_department` 
ON `crm_partners` (`department_id`);

-- Índices para crm_partner_tags
CREATE INDEX `idx_crm_partner_tags_partner` 
ON `crm_partner_tags` (`partner_id`);

CREATE INDEX `idx_crm_partner_tags_tag` 
ON `crm_partner_tags` (`tag_id`);

CREATE INDEX `idx_crm_partner_tags_composite` 
ON `crm_partner_tags` (`partner_id`, `tag_id`);

-- Índices para crm_opportunities
CREATE INDEX `idx_crm_opportunities_stage` 
ON `crm_opportunities` (`stage_id`);

CREATE INDEX `idx_crm_opportunities_responsible` 
ON `crm_opportunities` (`responsible_user_id`);

CREATE INDEX `idx_crm_opportunities_status` 
ON `crm_opportunities` (`status`);

CREATE INDEX `idx_crm_opportunities_stage_status` 
ON `crm_opportunities` (`stage_id`, `status`);

CREATE INDEX `idx_crm_opportunities_partner` 
ON `crm_opportunities` (`partner_id`);

-- ============================================================
-- ÍNDICES PARA HIERARQUIA
-- ============================================================

-- Índice crítico para hierarquia
CREATE INDEX `idx_users_immediate_supervisor` 
ON `adms_users` (`immediate_supervisor_id`);

-- Índice composto para status + supervisor
CREATE INDEX `idx_users_status_supervisor` 
ON `adms_users` (`status`, `immediate_supervisor_id`);

-- ============================================================
-- FIM - Índices adicionados
-- ============================================================

