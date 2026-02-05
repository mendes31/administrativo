-- ============================================================
-- Script SIMPLIFICADO para verificar índices criados
-- Execute este script no phpMyAdmin
-- ============================================================

-- VER TODOS OS ÍNDICES CRIADOS (COM NOME idx_)
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

-- ============================================================
-- VERIFICAR STATUS DOS 13 ÍNDICES RECOMENDADOS
-- ============================================================

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
SELECT 'crm_partner_tags', 'idx_crm_partner_tags_composite',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_partner_tags' AND INDEX_NAME = 'idx_crm_partner_tags_composite') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_stage',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_stage') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_responsible',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_responsible') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_status',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_status') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_stage_status',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_stage_status') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'crm_opportunities', 'idx_crm_opportunities_partner',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'crm_opportunities' AND INDEX_NAME = 'idx_crm_opportunities_partner') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'adms_users', 'idx_users_immediate_supervisor',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_users' AND INDEX_NAME = 'idx_users_immediate_supervisor') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
UNION ALL
SELECT 'adms_users', 'idx_users_status_supervisor',
    CASE WHEN EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_users' AND INDEX_NAME = 'idx_users_status_supervisor') THEN '✅ CRIADO' ELSE '❌ FALTANDO' END
ORDER BY tabela, indice_esperado;

