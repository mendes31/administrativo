-- Script SQL para marcar migrations do CRM como já executadas
-- Execute este script diretamente no banco de dados de produção se preferir

-- IMPORTANTE: Ajuste as datas (start_time e end_time) conforme necessário
-- Este script assume que todas as migrations foram executadas hoje

SET @now = NOW();

-- Criar tabela phinxlog se não existir (geralmente criada automaticamente pelo Phinx)
CREATE TABLE IF NOT EXISTS `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar se já existem antes de inserir
INSERT IGNORE INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`) VALUES
(20251028100000, 'CreateCrmPartners', @now, @now),
(20251028100001, 'CreateCrmPipelineStages', @now, @now),
(20251028100002, 'CreateCrmOpportunities', @now, @now),
(20251028100003, 'CreateCrmActivities', @now, @now),
(20251028100004, 'CreateCrmStageHistory', @now, @now),
(20251028100005, 'CreateCrmNotes', @now, @now),
(20251028100006, 'CreateCrmDocuments', @now, @now),
(20251028100007, 'CreateCrmTags', @now, @now),
(20251028100008, 'CreateCrmPartnerTags', @now, @now),
(20251029110000, 'CreateCrmCustomFields', @now, @now),
(20251029120000, 'CreateCrmAutomations', @now, @now),
(20251029130000, 'AddCountryToCrmPartners', @now, @now),
(20251030120001, 'RemoveTagsColumnFromCrmPartners', @now, @now);

-- Verificar registros inseridos
SELECT * FROM `phinxlog` WHERE `version` >= 20251028100000 ORDER BY `version`;

