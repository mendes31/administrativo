-- ============================================
-- QUERIES SAP B1 - ITENS COM MAIS VENDAS
-- ============================================

-- ============================================
-- QUERY 1: TOP 50 ITENS POR QUANTIDADE VENDIDA
-- ============================================
-- Mostra os itens mais vendidos por quantidade total
-- Inclui: código, nome, quantidade total, valor total, número de vendas
SELECT TOP 50
    i."ItemCode" AS "Código Item",
    i."ItemName" AS "Nome Item",
    SUM(d."Quantity") AS "Quantidade Total",
    SUM(d."LineTotal") AS "Valor Total",
    COUNT(DISTINCT d."DocEntry") AS "Número de Vendas",
    AVG(d."Price") AS "Preço Médio"
FROM 
    "OITM" i
    INNER JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE 
    h."CANCELED" = 'N'
    AND h."DocStatus" = 'O'
GROUP BY 
    i."ItemCode", i."ItemName"
ORDER BY 
    SUM(d."Quantity") DESC;


-- ============================================
-- QUERY 2: TOP 50 ITENS POR VALOR VENDIDO
-- ============================================
-- Mostra os itens que geraram mais receita
SELECT TOP 50
    i."ItemCode" AS "Código Item",
    i."ItemName" AS "Nome Item",
    SUM(d."Quantity") AS "Quantidade Total",
    SUM(d."LineTotal") AS "Valor Total",
    COUNT(DISTINCT d."DocEntry") AS "Número de Vendas",
    AVG(d."Price") AS "Preço Médio"
FROM 
    "OITM" i
    INNER JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE 
    h."CANCELED" = 'N'
    AND h."DocStatus" = 'O'
GROUP BY 
    i."ItemCode", i."ItemName"
ORDER BY 
    SUM(d."LineTotal") DESC;


-- ============================================
-- QUERY 3: ITENS COM MAIS VENDAS (HISTÓRICO COMPLETO)
-- ============================================
-- Inclui tanto faturas ativas (INV1) quanto históricas (DINV1)
SELECT TOP 50
    i."ItemCode" AS "Código Item",
    i."ItemName" AS "Nome Item",
    SUM(COALESCE(d."Quantity", 0) + COALESCE(dh."Quantity", 0)) AS "Quantidade Total",
    SUM(COALESCE(d."LineTotal", 0) + COALESCE(dh."LineTotal", 0)) AS "Valor Total",
    COUNT(DISTINCT COALESCE(d."DocEntry", dh."DocEntry")) AS "Número de Vendas"
FROM 
    "OITM" i
    LEFT JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    LEFT JOIN "OINV" h ON h."DocEntry" = d."DocEntry" AND h."CANCELED" = 'N' AND h."DocStatus" = 'O'
    LEFT JOIN "DINV1" dh ON dh."ItemCode" = i."ItemCode"
WHERE 
    (d."ItemCode" IS NOT NULL OR dh."ItemCode" IS NOT NULL)
GROUP BY 
    i."ItemCode", i."ItemName"
HAVING 
    SUM(COALESCE(d."Quantity", 0) + COALESCE(dh."Quantity", 0)) > 0
ORDER BY 
    SUM(COALESCE(d."LineTotal", 0) + COALESCE(dh."LineTotal", 0)) DESC;


-- ============================================
-- QUERY 4: ITENS COM MAIS VENDAS (ÚLTIMOS 12 MESES)
-- ============================================
-- Filtra apenas vendas dos últimos 12 meses
SELECT TOP 50
    i."ItemCode" AS "Código Item",
    i."ItemName" AS "Nome Item",
    SUM(d."Quantity") AS "Quantidade Total",
    SUM(d."LineTotal") AS "Valor Total",
    COUNT(DISTINCT d."DocEntry") AS "Número de Vendas",
    MIN(h."DocDate") AS "Primeira Venda",
    MAX(h."DocDate") AS "Última Venda"
FROM 
    "OITM" i
    INNER JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE 
    h."CANCELED" = 'N'
    AND h."DocStatus" = 'O'
    AND h."DocDate" >= ADD_MONTHS(CURRENT_DATE, -12)
GROUP BY 
    i."ItemCode", i."ItemName"
ORDER BY 
    SUM(d."LineTotal") DESC;


-- ============================================
-- QUERY 5: ITENS COM MAIS VENDAS (ÚLTIMOS 30 DIAS)
-- ============================================
-- Filtra apenas vendas dos últimos 30 dias
SELECT TOP 50
    i."ItemCode" AS "Código Item",
    i."ItemName" AS "Nome Item",
    SUM(d."Quantity") AS "Quantidade Total",
    SUM(d."LineTotal") AS "Valor Total",
    COUNT(DISTINCT d."DocEntry") AS "Número de Vendas",
    AVG(d."Price") AS "Preço Médio"
FROM 
    "OITM" i
    INNER JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE 
    h."CANCELED" = 'N'
    AND h."DocStatus" = 'O'
    AND h."DocDate" >= ADD_DAYS(CURRENT_DATE, -30)
GROUP BY 
    i."ItemCode", i."ItemName"
ORDER BY 
    SUM(d."LineTotal") DESC;


-- ============================================
-- QUERY 6: ITENS COM MAIS VENDAS (SIMPLIFICADA - RECOMENDADA)
-- ============================================
-- Query mais simples e rápida, recomendada para uso no SQL Personalizado
-- Ordena por número de saídas (quantidade de vendas diferentes)
-- ValorTotal em decimal com 2 casas (formatação R$ feita no frontend)
SELECT TOP 50
    i."ItemCode" AS "Código",
    i."ItemName" AS "Nome do Item",
    TO_DECIMAL(SUM(d."Quantity"), 15, 2) AS "Quantidade",
    TO_DECIMAL(SUM(d."LineTotal"), 15, 2) AS "Valor Total (R$)",
    COUNT(DISTINCT d."DocEntry") AS "Nº Saídas"
FROM 
    "OITM" i
    INNER JOIN "INV1" d ON d."ItemCode" = i."ItemCode"
    INNER JOIN "OINV" h ON h."DocEntry" = d."DocEntry"
WHERE 
    h."CANCELED" = 'N'
    AND h."DocStatus" = 'O'
GROUP BY 
    i."ItemCode", i."ItemName"
ORDER BY 
    COUNT(DISTINCT d."DocEntry") DESC, SUM(d."LineTotal") DESC;

