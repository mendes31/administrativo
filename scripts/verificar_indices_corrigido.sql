-- ============================================================
-- Query CORRIGIDA para verificar índices
-- IMPORTANTE: Selecione o banco de dados correto ANTES de executar!
-- No phpMyAdmin: Clique em "tiaraju04" ou "administrativo" no menu lateral
-- ============================================================

-- Opção 1: Se você selecionou o banco correto, use DATABASE()
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

-- ============================================================
-- Opção 2: Use o nome do banco diretamente (substitua se necessário)
-- ============================================================

-- Se o banco se chama 'tiaraju04', use:
/*
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'tiaraju04'
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;
*/

-- Se o banco se chama 'administrativo', use:
/*
SELECT 
    TABLE_NAME as 'Tabela',
    INDEX_NAME as 'Nome do Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') as 'Colunas',
    CASE WHEN NON_UNIQUE = 0 THEN 'ÚNICO' ELSE 'NÃO ÚNICO' END as 'Tipo'
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'administrativo'
AND INDEX_NAME LIKE 'idx_%'
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
ORDER BY TABLE_NAME, INDEX_NAME;
*/

