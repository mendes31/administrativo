-- Conferência com os cards do Dashboard (mesmo período do painel em [%0] / [%1]).
-- Recorte: DocType I, não cancelada, SeqCode <> 34, OITB 104/106, LineTotal − DiscSum.
-- Utilizações de Venda + Devolução comercial (E Dev Venda = 42).
-- Demais OUSG (ignorar / bonificação / brinde) ficam de fora — iguais aos cards de faturamento.

SELECT
    IFNULL(T."SlpName", '') AS "Vendedor",
    SUM(T."Vlr") AS "VALOR TOTAL VENDA",
    SUM(T."Qtd") AS "ITENS"
FROM (
    SELECT
        T3."SlpName",
        T1."LineTotal" - IFNULL(T0."DiscSum" * T1."LineTotal"
            / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0) AS "Vlr",
        T1."Quantity" AS "Qtd"
    FROM OINV T0
    INNER JOIN INV1 T1 ON T1."DocEntry" = T0."DocEntry"
    LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
    LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
    WHERE T0."CANCELED" = 'N'
      AND T0."DocType" = 'I'
      AND IFNULL(T0."SeqCode", 0) <> 34
      AND T0."DocDate" >= [%0]
      AND T0."DocDate" <= [%1]
      AND T4."ItmsGrpCod" IN (104, 106)
      AND IFNULL(T1."Usage", 0) IN (15, 16, 24, 47, 78, 42)

    UNION ALL

    SELECT
        T3."SlpName",
        (T1."LineTotal" - IFNULL(T0."DiscSum" * T1."LineTotal"
            / NULLIF(SUM(T1."LineTotal") OVER (PARTITION BY T0."DocEntry"), 0), 0)) * -1,
        T1."Quantity" * -1
    FROM ORIN T0
    INNER JOIN RIN1 T1 ON T1."DocEntry" = T0."DocEntry"
    LEFT JOIN OSLP T3 ON T3."SlpCode" = T0."SlpCode"
    LEFT JOIN OITM T4 ON T4."ItemCode" = T1."ItemCode"
    WHERE T0."CANCELED" = 'N'
      AND T0."DocType" = 'I'
      AND IFNULL(T0."SeqCode", 0) <> 34
      AND T0."DocDate" >= [%0]
      AND T0."DocDate" <= [%1]
      AND T4."ItmsGrpCod" IN (104, 106)
      AND IFNULL(T1."Usage", 0) IN (15, 16, 24, 47, 78, 42)
) T
GROUP BY IFNULL(T."SlpName", '')
ORDER BY 1
