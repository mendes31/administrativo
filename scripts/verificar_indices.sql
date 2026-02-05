-- ============================================================
-- Script para VERIFICAR índices existentes no banco
-- Execute este script no phpMyAdmin para ver quais índices já existem
-- ============================================================

-- ============================================================
-- VERIFICAR ÍNDICES POR TABELA
-- ============================================================

-- Ver índices da tabela crm_partners
SHOW INDEXES FROM `crm_partners`;

-- Ver índices da tabela crm_partner_tags
SHOW INDEXES FROM `crm_partner_tags`;

-- Ver índices da tabela crm_opportunities
SHOW INDEXES FROM `crm_opportunities`;

-- Ver índices da tabela adms_users (hierarquia)
SHOW INDEXES FROM `adms_users`;

-- ============================================================
-- VER TODOS OS ÍNDICES DO BANCO (VISÃO GERAL)
-- ============================================================

-- Ver todos os índices que começam com 'idx_'
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Colunas',
    NON_UNIQUE as 'Não Único',
    INDEX_TYPE as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================================
-- VERIFICAR ÍNDICES ESPECÍFICOS QUE CRIAMOS
-- ============================================================

-- Verificar se os índices de CRM foram criados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('crm_partners', 'crm_partner_tags', 'crm_opportunities')
AND INDEX_NAME LIKE 'idx_%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Verificar se os índices de hierarquia foram criados
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'adms_users'
AND INDEX_NAME IN ('idx_users_immediate_supervisor', 'idx_users_status_supervisor')
ORDER BY INDEX_NAME;

-- ============================================================
-- CONTAR ÍNDICES POR TABELA
-- ============================================================

SELECT 
    TABLE_NAME as 'Tabela',
    COUNT(DISTINCT INDEX_NAME) as 'Total de Índices'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('crm_partners', 'crm_partner_tags', 'crm_opportunities', 'adms_users')
GROUP BY TABLE_NAME
ORDER BY TABLE_NAME;

-- ============================================================
-- VERIFICAR ÍNDICES FALTANDO (COMPARAÇÃO)
-- ============================================================

-- Lista de índices que DEVERIAM existir
SELECT 
    'crm_partners' as tabela,
    'idx_crm_partners_responsible_user' as indice_esperado,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'crm_partners' 
            AND INDEX_NAME = 'idx_crm_partners_responsible_user'
        ) THEN '✅ CRIADO' 
        ELSE '❌ FALTANDO' 
    END as status
UNION ALL
SELECT 'crm_partners', 'idx_crm_partners_status',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_partners' AND INDEX_NAME = 'idx_crm_partners_status') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_partners', 'idx_crm_partners_department',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_partners' AND INDEX_NAME = 'idx_crm_partners_department') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_partner_tags', 'idx_crm_partner_tags_partner',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_partner_tags' AND INDEX_NAME = 'idx_crm_partner_tags_partner') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_partner_tags', 'idx_crm_partner_tags_tag',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_partner_tags' AND INDEX_NAME = 'idx_crm_partner_tags_tag') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_stage',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_stage') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_responsible',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_responsible') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'adms_users', 'idx_users_immediate_supervisor',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_users' AND INDEX_NAME = 'idx_users_immediate_supervisor') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'adms_users', 'idx_users_status_supervisor',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_users' AND INDEX_NAME = 'idx_users_status_supervisor') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
ORDER BY tabela, indice_esperado;

