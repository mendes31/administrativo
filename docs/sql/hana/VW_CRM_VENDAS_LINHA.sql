-- =============================================================================
-- VIEW HANA: VW_CRM_VENDAS_LINHA
-- Dashboard de Vendas CRM (Portal Administrativo)
-- SAP Business One | SAP HANA (identificadores entre aspas — case-sensitive)
-- =============================================================================
-- Pré-requisito: usuário da API SAP com SELECT nesta VIEW.
-- Executar no schema da company (SBO). Se a VIEW já existir, DROP antes.
--
-- Nota HANA: CREATE VIEW não aceita CTE (WITH ... AS). Use UNION ALL direto.
-- =============================================================================

-- DROP VIEW "VW_CRM_VENDAS_LINHA";

CREATE VIEW "VW_CRM_VENDAS_LINHA" AS
SELECT
    'Fatura'                                          AS "TipoDocumento",
    1                                                  AS "SinalDocumento",
    T0."DocEntry"                                      AS "DocEntry",
    T0."DocNum"                                        AS "DocNum",
    T0."DocDate"                                       AS "DocDate",
    YEAR(T0."DocDate")                                 AS "Ano",
    MONTH(T0."DocDate")                                AS "Mes",
    TO_VARCHAR(T0."DocDate", 'YYYY-MM')                AS "AnoMes",
    T0."DocStatus"                                     AS "DocStatus",
    T0."CANCELED"                                      AS "Cancelado",
    T0."BPLId"                                         AS "CodFilial",
    T7."BPLName"                                       AS "Filial",
    T0."SlpCode"                                       AS "CodVendedor",
    T3."SlpName"                                       AS "Vendedor",
    T0."CardCode"                                      AS "CardCode",
    T2."CardName"                                      AS "Cliente",
    T2."GroupCode"                                     AS "CodGrupoCliente",
    T5."GroupName"                                     AS "GrupoCliente",
    T2."Territory"                                     AS "CodTerritorio",
    IFNULL(NULLIF(T8."descript", ''), IFNULL(NULLIF(T2."State1", ''), 'Sem região')) AS "Regiao",
    T1."ItemCode"                                      AS "ItemCode",
    T1."Dscription"                                    AS "DescricaoItem",
    T4."ItmsGrpCod"                                    AS "CodGrupoItem",
    T6."ItmsGrpNam"                                    AS "GrupoItem",
    T1."WhsCode"                                       AS "Deposito",
    T1."Quantity"                                      AS "Quantidade",
    T1."Quantity"                                      AS "QuantidadeLiq",
    T1."Price"                                         AS "PrecoUnitario",
    T1."DiscPrcnt"                                     AS "DescontoPercentual",
    (T1."Price" * T1."Quantity")                       AS "ValorBruto",
    ((T1."Price" * T1."Quantity") - T1."LineTotal")    AS "ValorDesconto",
    T1."LineTotal"                                     AS "ValorLiquido",
    T1."LineTotal"                                     AS "ValorLiquidoSinalizado",
    IFNULL(T1."StockPrice", 0.0) * T1."Quantity"       AS "CustoTotal",
    (T1."LineTotal" - (IFNULL(T1."StockPrice", 0.0) * T1."Quantity")) AS "MargemBruta",
    T0."DocCur"                                        AS "Moeda",
    T0."DocRate"                                       AS "TaxaCambio"
FROM OINV T0
INNER JOIN INV1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT  JOIN OSLP T3 ON T3."SlpCode"  = T0."SlpCode"
LEFT  JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT  JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT  JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT  JOIN OTER T8 ON T8."territryID" = T2."Territory"
LEFT  JOIN OBPL T7 ON T7."BPLId" = T0."BPLId"
WHERE T0."CANCELED" = 'N'

UNION ALL

SELECT
    'Devolucao'                                        AS "TipoDocumento",
    -1                                                 AS "SinalDocumento",
    T0."DocEntry"                                      AS "DocEntry",
    T0."DocNum"                                        AS "DocNum",
    T0."DocDate"                                       AS "DocDate",
    YEAR(T0."DocDate")                                 AS "Ano",
    MONTH(T0."DocDate")                                AS "Mes",
    TO_VARCHAR(T0."DocDate", 'YYYY-MM')                AS "AnoMes",
    T0."DocStatus"                                     AS "DocStatus",
    T0."CANCELED"                                      AS "Cancelado",
    T0."BPLId"                                         AS "CodFilial",
    T7."BPLName"                                       AS "Filial",
    T0."SlpCode"                                       AS "CodVendedor",
    T3."SlpName"                                       AS "Vendedor",
    T0."CardCode"                                      AS "CardCode",
    T2."CardName"                                      AS "Cliente",
    T2."GroupCode"                                     AS "CodGrupoCliente",
    T5."GroupName"                                     AS "GrupoCliente",
    T2."Territory"                                     AS "CodTerritorio",
    IFNULL(NULLIF(T8."descript", ''), IFNULL(NULLIF(T2."State1", ''), 'Sem região')) AS "Regiao",
    T1."ItemCode"                                      AS "ItemCode",
    T1."Dscription"                                    AS "DescricaoItem",
    T4."ItmsGrpCod"                                    AS "CodGrupoItem",
    T6."ItmsGrpNam"                                    AS "GrupoItem",
    T1."WhsCode"                                       AS "Deposito",
    T1."Quantity"                                      AS "Quantidade",
    (T1."Quantity" * -1)                               AS "QuantidadeLiq",
    T1."Price"                                         AS "PrecoUnitario",
    T1."DiscPrcnt"                                     AS "DescontoPercentual",
    (T1."Price" * T1."Quantity")                       AS "ValorBruto",
    ((T1."Price" * T1."Quantity") - T1."LineTotal")    AS "ValorDesconto",
    T1."LineTotal"                                     AS "ValorLiquido",
    (T1."LineTotal" * -1)                              AS "ValorLiquidoSinalizado",
    (IFNULL(T1."StockPrice", 0.0) * T1."Quantity") * -1 AS "CustoTotal",
    ((T1."LineTotal" * -1) - ((IFNULL(T1."StockPrice", 0.0) * T1."Quantity") * -1)) AS "MargemBruta",
    T0."DocCur"                                        AS "Moeda",
    T0."DocRate"                                       AS "TaxaCambio"
FROM ORIN T0
INNER JOIN RIN1 T1 ON T1."DocEntry" = T0."DocEntry"
INNER JOIN OCRD T2 ON T2."CardCode" = T0."CardCode"
LEFT  JOIN OSLP T3 ON T3."SlpCode"  = T0."SlpCode"
LEFT  JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
LEFT  JOIN OITB T6 ON T6."ItmsGrpCod" = T4."ItmsGrpCod"
LEFT  JOIN OCRG T5 ON T5."GroupCode" = T2."GroupCode"
LEFT  JOIN OTER T8 ON T8."territryID" = T2."Territory"
LEFT  JOIN OBPL T7 ON T7."BPLId" = T0."BPLId"
WHERE T0."CANCELED" = 'N';

-- Conceder SELECT ao usuário da API (ajuste o usuário conforme o ambiente):
-- GRANT SELECT ON "VW_CRM_VENDAS_LINHA" TO <usuario_api>;
