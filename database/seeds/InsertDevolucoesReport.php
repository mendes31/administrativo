<?php

use Phinx\Seed\AbstractSeed;

/**
 * Criar relatório de Devoluções de Venda
 */
class InsertDevolucoesReport extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se já existe
        $existing = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = 'Devoluções de Venda'");
        
        if ($existing) {
            echo "ℹ️  Relatório 'Devoluções de Venda' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Query fornecida pelo usuário
        $sql = 'SELECT
    T0."CANCELED",
    T0."DocNum" AS "NumDoc",
    T0."Serial" AS "NFe",
    T0."DocDate" AS "Data",
    T0."BPLName" AS "Filial",
    T0."CardCode" AS "cdPN",
    T0."CardName" AS "nomePN",
    T7."GroupName" AS "nomeGrupoPN",
    T2."SlpName" AS "nomeVendedor",
    T1."ItemCode" AS "cdItem",
    T1."Dscription" AS "nomeItem",
    T5."ItmsGrpNam" AS "nomeGrupoItem",
    T1."Quantity" AS "Qtde",
    COALESCE(T1."U_CustoItem", \'0\') AS "Custo",
    T1."PriceBefDi" AS "Preco Unitario",
    COALESCE(T1."DiscPrcnt", 0) AS "Desconto%",
    T1."Price" AS "Preco Final",
    (T1."PriceBefDi" * T1."Quantity") as "Total s/ Desc",
    T1."LineTotal" AS "Total c/ Desc",
    SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum") AS "Total Itens Documento",
    CASE WHEN (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum")) > 0 
        THEN ROUND((T1."LineTotal" / (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum"))), 2) 
        ELSE 0 
    END AS "%Linha",
    COALESCE(T8."LineTotal", 0) as "Despesa add Rodapé",
    COALESCE(T0."DiscSum", 0.00) as "Desc Rodapé",
    CASE WHEN (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum")) > 0 
        THEN ((T1."LineTotal" / (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum"))) * T0."DiscSum") 
        ELSE 0 
    END AS "Distribuicao Desc Rodapé",
    CASE WHEN (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum")) > 0 
        THEN (T1."LineTotal" - ((T1."LineTotal" / (SUM(T1."LineTotal") OVER (PARTITION BY T0."DocNum"))) * T0."DiscSum" )) 
        ELSE 0 
    END AS "Valor final",
    T1."Usage" AS "cdUtilizacao",
    T3."Usage" AS "Utilizacao",
    CASE WHEN T9."StateS" = \'\' 
        THEN T9."StateB" 
        ELSE T9."StateS" 
    END AS "Estado"
FROM ORIN T0
INNER JOIN RIN1 T1 ON T0."DocEntry" = T1."DocEntry"
INNER JOIN OSLP T2 ON T0."SlpCode" = T2."SlpCode"
INNER JOIN OUSG T3 ON T1."Usage" = T3."ID"
INNER JOIN OITM T4 ON T1."ItemCode" = T4."ItemCode"
INNER JOIN OITB T5 ON T4."ItmsGrpCod" = T5."ItmsGrpCod"
INNER JOIN OCRD T6 ON T0."CardCode" = T6."CardCode"
INNER JOIN OCRG T7 ON T6."GroupCode" = T7."GroupCode"
LEFT JOIN RIN3 T8 ON T0."DocEntry" = T8."DocEntry"
INNER JOIN RIN12 T9 ON T0."DocEntry" = T9."DocEntry"
WHERE T0."DocType" = \'I\'
    AND (T4."ItmsGrpCod" = \'104\' OR T4."ItmsGrpCod" = \'106\')
    AND T0."CANCELED" = \'N\'
ORDER BY T0."DocNum", T1."LineNum"';
        
        // Inserir relatório
        $report = [
            'name' => 'Devoluções de Venda',
            'description' => 'Relatório detalhado de devoluções de venda do SAP B1',
            'created_by' => 1,
            'is_public' => 1,
            'data_source' => 'sap_b1',
            'custom_sql' => $sql,
            'query_mode' => 'custom_sql',
            'fields' => '[]',
            'filters' => '[]',
            'groupby' => '[]',
            'orderby' => '[]',
            'visualization_type' => 'table',
            'chart_config' => '[]',
            'category' => 'Vendas',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $table = $this->table('adms_dynamic_reports');
        $table->insert($report)->saveData();
        
        $reportId = $this->getAdapter()->getConnection()->lastInsertId();
        
        echo "✅ Relatório 'Devoluções de Venda' criado! (ID: {$reportId})\n";
        echo "   - Query completa de devoluções do SAP B1\n";
        echo "   - Mesma estrutura do RMVendas (para dashboards)\n";
    }
}

