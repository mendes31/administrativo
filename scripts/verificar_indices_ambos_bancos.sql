-- ============================================================
-- Query para verificar índices em AMBOS os bancos possíveis
-- Execute esta query no phpMyAdmin (pode estar em qualquer banco)
-- ============================================================

-- Verificar no banco 'tiaraju04'
SELECT 
    'tiaraju04' as 'Banco',
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'tiaraju04'
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE

UNION ALL

-- Verificar no banco 'administrativo'
SELECT 
    'administrativo' as 'Banco',
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'administrativo'
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE

ORDER BY 'Banco', TABLE_NAME, INDEX_NAME;

