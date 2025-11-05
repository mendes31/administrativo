<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * API para buscar dados do Dashboard de Vendas
 */
class SalesDashboardData
{
    public function index(): void
    {
        // Aumentar limites
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '180');
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $year = $_POST['year'] ?? date('Y');
            $month = $_POST['month'] ?? null;
            $partnerGroup = $_POST['partner_group'] ?? null;
            $salesperson = $_POST['salesperson'] ?? null;
            $customQuery = $_POST['custom_query'] ?? null;
            
            // Se tiver query customizada, usar ela; senão, usar a padrão
            if ($customQuery) {
                $sql = $this->applyFiltersToCustomQuery($customQuery, $year, $month, $partnerGroup, $salesperson);
                error_log("📝 Usando query customizada");
            } else {
                $sql = $this->buildSalesQuery($year, $month, $partnerGroup, $salesperson);
                error_log("📊 Usando query padrão");
            }
            
            $queryBuilder = new DynamicQueryBuilderService();
            $result = $queryBuilder->executeReport([
                'custom_sql' => $sql,
                'query_mode' => 'custom_sql'
            ]);
            
            if ($result['success']) {
                // Processar dados para KPIs
                $kpis = $this->calculateKPIs($result['data']);
                $result['kpis'] = $kpis;
                $result['monthly_data'] = $this->aggregateByMonth($result['data']);
                $result['salesperson_data'] = $this->aggregateBySalesperson($result['data']);
            }
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        
        exit;
    }
    
    private function applyFiltersToCustomQuery(string $query, int $year, ?int $month, ?string $partnerGroup, ?string $salesperson): string
    {
        // Substituir variável {ANO}
        $query = str_replace('{ANO}', $year, $query);
        
        // Substituir variável {MES_FILTER}
        if ($month) {
            $mesFilter = "AND MONTH(T0.\"DocDate\") = {$month}";
        } else {
            $mesFilter = "";
        }
        $query = str_replace('{MES_FILTER}', $mesFilter, $query);
        
        // Substituir variável {GRUPO_FILTER}
        if ($partnerGroup) {
            $grupoFilter = "AND T7.\"GroupName\" = '" . addslashes($partnerGroup) . "'";
        } else {
            $grupoFilter = "";
        }
        $query = str_replace('{GRUPO_FILTER}', $grupoFilter, $query);
        
        // Substituir variável {VENDEDOR_FILTER}
        if ($salesperson) {
            $vendedorFilter = "AND T2.\"SlpName\" = '" . addslashes($salesperson) . "'";
        } else {
            $vendedorFilter = "";
        }
        $query = str_replace('{VENDEDOR_FILTER}', $vendedorFilter, $query);
        
        // Substituir variáveis simples (sem _FILTER)
        if ($month) {
            $query = str_replace('{MES}', $month, $query);
        } else {
            $query = str_replace('{MES}', 'NULL', $query);
        }
        
        if ($partnerGroup) {
            $query = str_replace('{GRUPO}', "'" . addslashes($partnerGroup) . "'", $query);
        } else {
            $query = str_replace('{GRUPO}', 'NULL', $query);
        }
        
        if ($salesperson) {
            $query = str_replace('{VENDEDOR}', "'" . addslashes($salesperson) . "'", $query);
        } else {
            $query = str_replace('{VENDEDOR}', 'NULL', $query);
        }
        
        return $query;
    }
    
    private function buildSalesQuery(int $year, ?int $month, ?string $partnerGroup, ?string $salesperson): string
    {
        $sql = "SELECT
            YEAR(T0.\"DocDate\") AS \"Ano\",
            MONTH(T0.\"DocDate\") AS \"Mes\",
            T0.\"DocEntry\",
            T0.\"DocNum\" AS \"NumDoc\",
            T0.\"DocDate\" AS \"DataCriacao\",
            T0.\"CardCode\" AS \"cdPN\",
            T0.\"CardName\" AS \"nomePN\",
            T7.\"GroupName\" AS \"nomeGrupoPN\",
            T2.\"SlpCode\" AS \"cdVendedor\",
            T2.\"SlpName\" AS \"nomeVendedor\",
            T1.\"ItemCode\" AS \"cdItem\",
            T1.\"Dscription\" AS \"nomeItem\",
            T5.\"ItmsGrpNam\" AS \"nomeGrupoItem\",
            T1.\"Quantity\" AS \"Qtde\",
            T1.\"StockPrice\" AS \"Custo_Unitario\",
            (T1.\"StockPrice\" * T1.\"Quantity\") AS \"Custo_Total_Item\",
            COALESCE(T10.\"TotalImpostos\", 0) AS \"Total_Impostos\",
            ((T1.\"StockPrice\" * T1.\"Quantity\") + COALESCE(T10.\"TotalImpostos\", 0)) AS \"Custo_Impostos\",
            T1.\"PriceBefDi\" AS \"Preco_Unitario\",
            T1.\"DiscPrcnt\" AS \"DescontoPct\",
            T1.\"LineTotal\" AS \"TotalLinha\",
            T0.\"DiscSum\" AS \"DescontoRodape\"
        FROM OINV T0
        INNER JOIN INV1 T1 ON T0.\"DocEntry\" = T1.\"DocEntry\"
        INNER JOIN OSLP T2 ON T0.\"SlpCode\" = T2.\"SlpCode\"
        INNER JOIN OITM T4 ON T1.\"ItemCode\" = T4.\"ItemCode\"
        INNER JOIN OITB T5 ON T4.\"ItmsGrpCod\" = T5.\"ItmsGrpCod\"
        INNER JOIN OCRD T6 ON T0.\"CardCode\" = T6.\"CardCode\"
        INNER JOIN OCRG T7 ON T6.\"GroupCode\" = T7.\"GroupCode\"
        LEFT JOIN (
            SELECT
                \"DocEntry\",
                \"LineNum\",
                SUM(\"TaxSum\") AS \"TotalImpostos\"
            FROM INV4
            GROUP BY \"DocEntry\", \"LineNum\"
        ) T10 ON T1.\"DocEntry\" = T10.\"DocEntry\" AND T1.\"LineNum\" = T10.\"LineNum\"
        WHERE T0.\"DocType\" = 'I'
        AND T0.\"CANCELED\" = 'N'
        AND YEAR(T0.\"DocDate\") = {$year}";
        
        if ($month) {
            $sql .= " AND MONTH(T0.\"DocDate\") = {$month}";
        }
        
        if ($partnerGroup) {
            $sql .= " AND T7.\"GroupName\" = '{$partnerGroup}'";
        }
        
        if ($salesperson) {
            $sql .= " AND T2.\"SlpName\" = '{$salesperson}'";
        }
        
        $sql .= " ORDER BY T0.\"DocDate\" DESC, T0.\"DocNum\" DESC
        LIMIT 5000";
        
        return $sql;
    }
    
    private function calculateKPIs(array $data): array
    {
        if (empty($data)) {
            return [
                'faturamento' => 0,
                'custo_impostos' => 0,
                'vendas' => 0,
                'itens_vendidos' => 0,
                'ticket_medio' => 0,
                'desconto_total' => 0,
                'desconto_pct' => 0
            ];
        }
        
        $documentos = [];
        $totalFaturamento = 0;
        $totalCustoImpostos = 0;
        $totalItens = 0;
        $totalDesconto = 0;
        
        foreach ($data as $row) {
            $docNum = $row['NumDoc'] ?? $row['numdoc'];
            $documentos[$docNum] = true;
            
            $totalFaturamento += (float)($row['TotalLinha'] ?? $row['totallinha'] ?? 0);
            $totalCustoImpostos += (float)($row['Custo_Impostos'] ?? $row['custo_impostos'] ?? 0);
            $totalItens += (float)($row['Qtde'] ?? $row['qtde'] ?? 0);
            $totalDesconto += (float)($row['DescontoRodape'] ?? $row['descontorodape'] ?? 0);
        }
        
        $qtdVendas = count($documentos);
        $ticketMedio = $qtdVendas > 0 ? $totalFaturamento / $qtdVendas : 0;
        $descontoPct = $totalFaturamento > 0 ? ($totalDesconto / $totalFaturamento) * 100 : 0;
        
        return [
            'faturamento' => round($totalFaturamento, 2),
            'custo_impostos' => round($totalCustoImpostos, 2),
            'vendas' => $qtdVendas,
            'itens_vendidos' => (int)$totalItens,
            'ticket_medio' => round($ticketMedio, 2),
            'desconto_total' => round($totalDesconto, 2),
            'desconto_pct' => round($descontoPct, 2)
        ];
    }
    
    private function aggregateByMonth(array $data): array
    {
        $monthly = [];
        
        foreach ($data as $row) {
            $mes = (int)($row['Mes'] ?? $row['mes'] ?? 0);
            if (!isset($monthly[$mes])) {
                $monthly[$mes] = 0;
            }
            $monthly[$mes] += (float)($row['TotalLinha'] ?? $row['totallinha'] ?? 0);
        }
        
        ksort($monthly);
        return $monthly;
    }
    
    private function aggregateBySalesperson(array $data): array
    {
        $salespersons = [];
        
        foreach ($data as $row) {
            $vendedor = $row['nomeVendedor'] ?? $row['nomevendedor'] ?? 'Sem Vendedor';
            
            if (!isset($salespersons[$vendedor])) {
                $salespersons[$vendedor] = [
                    'faturamento' => 0,
                    'vendas' => [],
                    'itens' => 0
                ];
            }
            
            $salespersons[$vendedor]['faturamento'] += (float)($row['TotalLinha'] ?? $row['totallinha'] ?? 0);
            $salespersons[$vendedor]['vendas'][$row['NumDoc'] ?? $row['numdoc']] = true;
            $salespersons[$vendedor]['itens'] += (float)($row['Qtde'] ?? $row['qtde'] ?? 0);
        }
        
        // Contar vendas únicas
        foreach ($salespersons as &$vendor) {
            $vendor['vendas'] = count($vendor['vendas']);
            $vendor['ticket_medio'] = $vendor['vendas'] > 0 ? $vendor['faturamento'] / $vendor['vendas'] : 0;
        }
        
        // Ordenar por faturamento
        uasort($salespersons, function($a, $b) {
            return $b['faturamento'] <=> $a['faturamento'];
        });
        
        return $salespersons;
    }
}

