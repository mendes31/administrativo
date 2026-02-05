-- ============================================================
-- Script para adicionar índices de performance nas tabelas de treinamentos
-- VERSÃO PARA phpMyAdmin - Execute este script na aba SQL
-- ============================================================

-- IMPORTANTE: Se algum índice já existir, você pode ignorar o erro
-- ou removê-lo manualmente antes de executar

-- Índices para adms_training_users
-- Melhora JOINs e filtros por status
CREATE INDEX `idx_training_users_user_status` 
ON `adms_training_users` (`adms_user_id`, `status`);

CREATE INDEX `idx_training_users_training_status` 
ON `adms_training_users` (`adms_training_id`, `status`);

CREATE INDEX `idx_training_users_created_at` 
ON `adms_training_users` (`created_at`);

-- Índices para adms_training_applications
-- Melhora busca de última aplicação (resolve N+1)
CREATE INDEX `idx_training_applications_user_training_created` 
ON `adms_training_applications` (`adms_user_id`, `adms_training_id`, `created_at` DESC);

CREATE INDEX `idx_training_applications_user_training` 
ON `adms_training_applications` (`adms_user_id`, `adms_training_id`);

-- Índices para adms_users
-- Melhora filtros por status e departamento
CREATE INDEX `idx_users_status_department` 
ON `adms_users` (`status`, `user_department_id`);

CREATE INDEX `idx_users_status_position` 
ON `adms_users` (`status`, `user_position_id`);

-- Índices para adms_trainings
-- Melhora filtros por código e status
CREATE INDEX `idx_trainings_ativo_codigo` 
ON `adms_trainings` (`ativo`, `codigo`);

-- Índices para adms_training_positions
-- Melhora JOIN com positions
CREATE INDEX `idx_training_positions_training_position` 
ON `adms_training_positions` (`adms_training_id`, `adms_position_id`);

-- ============================================================
-- FIM - Índices adicionados
-- ============================================================

