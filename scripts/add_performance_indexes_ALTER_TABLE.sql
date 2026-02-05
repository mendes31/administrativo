-- ============================================================
-- Script ALTERNATIVO usando ALTER TABLE
-- Alguns provedores permitem ALTER TABLE mas não CREATE INDEX
-- Tente este script se o CREATE INDEX não funcionar
-- ============================================================

-- IMPORTANTE: Execute um comando por vez e verifique se funcionou
-- Se algum índice já existir, você verá um erro - pode ignorar

-- Índices para adms_training_users
ALTER TABLE `adms_training_users` 
ADD INDEX `idx_training_users_user_status` (`adms_user_id`, `status`);

ALTER TABLE `adms_training_users` 
ADD INDEX `idx_training_users_training_status` (`adms_training_id`, `status`);

ALTER TABLE `adms_training_users` 
ADD INDEX `idx_training_users_created_at` (`created_at`);

-- Índices para adms_training_applications
ALTER TABLE `adms_training_applications` 
ADD INDEX `idx_training_applications_user_training_created` (`adms_user_id`, `adms_training_id`, `created_at` DESC);

ALTER TABLE `adms_training_applications` 
ADD INDEX `idx_training_applications_user_training` (`adms_user_id`, `adms_training_id`);

-- Índices para adms_users
ALTER TABLE `adms_users` 
ADD INDEX `idx_users_status_department` (`status`, `user_department_id`);

ALTER TABLE `adms_users` 
ADD INDEX `idx_users_status_position` (`status`, `user_position_id`);

-- Índices para adms_trainings
ALTER TABLE `adms_trainings` 
ADD INDEX `idx_trainings_ativo_codigo` (`ativo`, `codigo`);

-- Índices para adms_training_positions
ALTER TABLE `adms_training_positions` 
ADD INDEX `idx_training_positions_training_position` (`adms_training_id`, `adms_position_id`);

-- ============================================================
-- FIM - Índices adicionados via ALTER TABLE
-- ============================================================

