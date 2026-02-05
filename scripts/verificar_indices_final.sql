-- ============================================================
-- Query FINAL para verificar índices
-- IMPORTANTE: Execute esta query no banco de dados CORRETO
-- No phpMyAdmin: Clique em "tiaraju04" ou "administrativo" no menu lateral ESQUERDO
-- Depois execute esta query
-- ============================================================

-- Esta query mostra TODOS os índices que começam com 'idx_'
-- Funciona se você selecionou o banco correto antes
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'tiaraju04'  -- ⚠️ ALTERE AQUI: 'tiaraju04' ou 'administrativo'
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;

