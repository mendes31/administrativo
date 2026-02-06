-- ============================================================
-- Script para limpar o banco de dados e começar do zero
-- 
-- ATENÇÃO: Este script vai DELETAR TODAS AS TABELAS e dados!
-- Use apenas se tiver certeza e backup dos dados importantes.
-- ============================================================

-- 1. Desabilitar verificação de foreign keys temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Limpar o phinxlog (registro de migrations)
TRUNCATE TABLE phinxlog;

-- 3. Deletar todas as tabelas (ajuste conforme necessário)
-- Se você quiser deletar tabela por tabela, use:
-- DROP TABLE IF EXISTS nome_da_tabela;

-- OU use este comando para listar e deletar todas automaticamente:
-- (Execute no phpMyAdmin ou ajuste conforme seu banco)

-- Exemplo de algumas tabelas principais (ajuste conforme necessário):
DROP TABLE IF EXISTS `lgpd_consentimento_arquivos`;
DROP TABLE IF EXISTS `lgpd_consentimentos`;
DROP TABLE IF EXISTS `lgpd_termos`;
DROP TABLE IF EXISTS `adms_strategic_plan_observations`;
DROP TABLE IF EXISTS `adms_strategic_plans`;
DROP TABLE IF EXISTS `adms_strategic_indicators`;
DROP TABLE IF EXISTS `adms_training_users`;
DROP TABLE IF EXISTS `adms_training_applications`;
DROP TABLE IF EXISTS `adms_trainings`;
DROP TABLE IF EXISTS `adms_users`;
DROP TABLE IF EXISTS `adms_departments`;
DROP TABLE IF EXISTS `adms_positions`;
DROP TABLE IF EXISTS `adms_access_levels`;
DROP TABLE IF EXISTS `adms_pages`;
-- ... (adicione outras tabelas conforme necessário)

-- 4. Reabilitar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- NOTA: Para deletar TODAS as tabelas automaticamente,
-- use o script PHP abaixo ou faça manualmente no phpMyAdmin
-- ============================================================

