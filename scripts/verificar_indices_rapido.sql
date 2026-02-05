-- ============================================================
-- Query RÁPIDA para verificar TODOS os índices criados
-- Execute esta query no phpMyAdmin (copie e cole tudo)
-- ============================================================

-- Esta query mostra TODOS os índices que começam com 'idx_'
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

