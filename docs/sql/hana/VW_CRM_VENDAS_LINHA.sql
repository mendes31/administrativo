-- =============================================================================
-- VIEW HANA: VW_CRM_VENDAS_LINHA
-- Dashboard de Vendas CRM (Portal Administrativo)
-- SAP Business One | SAP HANA (identificadores entre aspas — case-sensitive)
-- =============================================================================
-- Referência do recorte (opcional). O Portal NÃO consulta esta VIEW: o sync envia
-- só CTE (OINV/ORIN) pela API SAP. Criar no HANA é opcional, para Query Manager.
--
-- Recorte: OITB 104 e 106. Todas as utilizações entram (natureza no Portal, inclusive Devolução comercial = E Dev Venda).
-- Alinhado à query de indicadores: DocType = I, SeqCode <> 34, LineTotal − DiscSum (rateado na linha).
-- Não filtrar por 400/700 em ItmsGrpCod — esses números não existem como código.
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
    IFNULL(T1."Usage", 0)                              AS "CodUtilizacao",
    IFNULL(T9."Usage", 'Sem utilização')               AS "Utilizacao",
    T1."WhsCode"                                       AS "Deposito",
    T1."Quantity"                                      AS "Quantidade",
    T1."Quantity"                                      AS "QuantidadeLiq",
    T1."Price"                                         AS "PrecoUnitario",
    T1."DiscPrcnt"                                     AS "DescontoPercentual",
    (T1."Price" * T1."Quantity")                       AS "ValorBruto",
    (T1."Price" * T1."Quantity")                       AS "ValorBrutoSinalizado",
    ((T1."Price" * T1."Quantity") - T1."LineTotal")
        + IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorDesconto",
    ((T1."Price" * T1."Quantity") - T1."LineTotal")
        + IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorDescontoSinalizado",
    T1."LineTotal" - IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorLiquido",
    T1."LineTotal" - IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorLiquidoSinalizado",
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
LEFT  JOIN OUSG T9 ON T9."ID" = T1."Usage"
WHERE T0."CANCELED" = 'N'
  AND T0."DocType" = 'I'
  AND IFNULL(T0."SeqCode", 0) <> 34
  AND (
        T4."ItmsGrpCod" IN (104, 106)
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%PROD ACABADO%'
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%USO/CONS%'
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%USO E CONSUMO%'
      )

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
    IFNULL(T1."Usage", 0)                              AS "CodUtilizacao",
    IFNULL(T9."Usage", 'Sem utilização')               AS "Utilizacao",
    T1."WhsCode"                                       AS "Deposito",
    T1."Quantity"                                      AS "Quantidade",
    (T1."Quantity" * -1)                               AS "QuantidadeLiq",
    T1."Price"                                         AS "PrecoUnitario",
    T1."DiscPrcnt"                                     AS "DescontoPercentual",
    (T1."Price" * T1."Quantity")                       AS "ValorBruto",
    (T1."Price" * T1."Quantity" * -1)                  AS "ValorBrutoSinalizado",
    ((T1."Price" * T1."Quantity") - T1."LineTotal")
        + IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorDesconto",
    (((T1."Price" * T1."Quantity") - T1."LineTotal")
        + IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0)) * -1 AS "ValorDescontoSinalizado",
    T1."LineTotal" - IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "ValorLiquido",
    (T1."LineTotal" - IFNULL(IFNULL(T0."DiscSum", 0) * T1."LineTotal" / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0)) * -1 AS "ValorLiquidoSinalizado",
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
LEFT  JOIN OUSG T9 ON T9."ID" = T1."Usage"
WHERE T0."CANCELED" = 'N'
  AND T0."DocType" = 'I'
  AND IFNULL(T0."SeqCode", 0) <> 34
  AND (
        T4."ItmsGrpCod" IN (104, 106)
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%PROD ACABADO%'
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%USO/CONS%'
        OR UPPER(IFNULL(T6."ItmsGrpNam", '')) LIKE '%USO E CONSUMO%'
      );

-- Conceder SELECT ao usuário da API (ajuste o usuário conforme o ambiente):
-- GRANT SELECT ON "VW_CRM_VENDAS_LINHA" TO <usuario_api>;
