<?php

use Phinx\Seed\AbstractSeed;

/**
 * Seeder para criar Dashboard de Vendas usando o relatório RMVendas
 * 
 * Este dashboard inclui:
 * - KPIs: Faturamento, Ticket Médio, Total Documentos, Margem %
 * - Filtros: Vendedor, Grupo de Parceiro, Ano, Mês
 * - Gráficos: Faturamento por Vendedor, Distribuição por Grupo
 * - Medidas Calculadas: Ticket Médio, Margem %, Qtd Total
 */
class InsertDashboardRMVendas extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Buscar o ID do relatório RMVendas
        $reportResult = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = 'RMVendas'");
        
        if (!$reportResult) {
            echo "⚠️  Relatório 'RMVendas' não encontrado. Dashboard não será criado.\n";
            return;
        }
        
        $reportId = $reportResult['id'];
        
        // Verificar se o dashboard já existe
        $existingDashboard = $this->fetchRow(
            "SELECT id FROM adms_dashboards WHERE name = 'Dashboard de Vendas - RMVendas'"
        );
        
        if ($existingDashboard) {
            echo "ℹ️  Dashboard 'Dashboard de Vendas - RMVendas' já existe (ID: {$existingDashboard['id']})\n";
            return;
        }
        
        // Configurações do Dashboard
        $dashboardData = [
            'name' => 'Dashboard de Vendas - RMVendas',
            'description' => 'Dashboard completo de vendas com KPIs, filtros e gráficos baseado no relatório RMVendas',
            'dynamic_report_id' => $reportId,
            'created_by' => 1, // Admin
            'is_public' => 1, // Público para todos verem
            'category' => 'Vendas',
            'layout' => 'default',
            'status' => 1,
            
            // MEDIDAS CALCULADAS
            'measures_config' => json_encode([
                [
                    'name' => 'Ticket Médio',
                    'formula' => '[Total c/ Desc] / COUNT([NumDoc])',
                    'format' => 'currency'
                ],
                [
                    'name' => 'Margem %',
                    'formula' => '([Total c/ Desc] - [Custo_Total_Item]) / [Total c/ Desc] * 100',
                    'format' => 'percent'
                ],
                [
                    'name' => 'Qtd Total Vendida',
                    'formula' => 'SUM([Qtde])',
                    'format' => 'number'
                ],
                [
                    'name' => 'Custo Total',
                    'formula' => 'SUM([Custo_Total_Item])',
                    'format' => 'currency'
                ]
            ]),
            
            // KPIs
            'kpis_config' => json_encode([
                [
                    'field' => 'Total c/ Desc',
                    'label' => 'Faturamento Total',
                    'aggregation' => 'sum',
                    'format' => 'currency',
                    'icon' => 'fa-dollar-sign',
                    'color' => 'success'
                ],
                [
                    'field' => 'Ticket Médio',
                    'label' => 'Ticket Médio',
                    'aggregation' => '', // Medida calculada já tem agregação
                    'format' => 'currency',
                    'icon' => 'fa-receipt',
                    'color' => 'info'
                ],
                [
                    'field' => 'NumDoc',
                    'label' => 'Total de Documentos',
                    'aggregation' => 'count_distinct',
                    'format' => 'number',
                    'icon' => 'fa-file-invoice',
                    'color' => 'primary'
                ],
                [
                    'field' => 'Margem %',
                    'label' => 'Margem Média',
                    'aggregation' => '',
                    'format' => 'percent',
                    'icon' => 'fa-percentage',
                    'color' => 'warning'
                ],
                [
                    'field' => 'Qtd Total Vendida',
                    'label' => 'Quantidade Vendida',
                    'aggregation' => '',
                    'format' => 'number',
                    'icon' => 'fa-boxes',
                    'color' => 'secondary'
                ],
                [
                    'field' => 'Custo Total',
                    'label' => 'Custo Total',
                    'aggregation' => '',
                    'format' => 'currency',
                    'icon' => 'fa-coins',
                    'color' => 'danger'
                ]
            ]),
            
            // FILTROS (usando relatórios específicos para performance)
            'filters_config' => json_encode([
                [
                    'field' => 'nomeVendedor',
                    'label' => 'Vendedor',
                    'type' => 'text',
                    'variable' => '{VENDEDOR}',
                    'filter_report_id' => 15, // [FILTRO] Vendedores
                    'source_field' => 'Vendedor_Comprador'
                ],
                [
                    'field' => 'nomeGrupoPN',
                    'label' => 'Grupo de Parceiro',
                    'type' => 'text',
                    'variable' => '{GRUPO_PARCEIRO}',
                    'filter_report_id' => 16, // [FILTRO] Grupos de Parceiros
                    'source_field' => 'GroupName'
                ],
                [
                    'field' => 'DataCriação',
                    'label' => 'Ano',
                    'type' => 'year',
                    'variable' => '{ANO}',
                    'default_value' => date('Y'), // Ano atual
                    'required' => true // Obrigatório para limitar dados
                ],
                [
                    'field' => 'DataCriação',
                    'label' => 'Mês',
                    'type' => 'month',
                    'variable' => '{MES}'
                ]
            ]),
            
            // GRÁFICOS
            'charts_config' => json_encode([
                'chart1' => [
                    'type' => 'bar',
                    'group_by' => 'nomeVendedor',
                    'value_field' => 'Total c/ Desc',
                    'aggregation' => 'sum',
                    'title' => 'Faturamento por Vendedor',
                    'color' => '#4CAF50'
                ],
                'chart2' => [
                    'type' => 'pie',
                    'group_by' => 'nomeGrupoPN',
                    'value_field' => 'Total c/ Desc',
                    'aggregation' => 'sum',
                    'title' => 'Distribuição por Grupo de Parceiro',
                    'color' => '#2196F3'
                ],
                'chart3' => [
                    'type' => 'bar',
                    'group_by' => 'nomeGrupoItem',
                    'value_field' => 'Qtde',
                    'aggregation' => 'sum',
                    'title' => 'Quantidade Vendida por Grupo de Item',
                    'color' => '#FF9800'
                ],
                'chart4' => [
                    'type' => 'doughnut',
                    'group_by' => 'nomeVendedor',
                    'value_field' => 'NumDoc',
                    'aggregation' => 'count',
                    'title' => 'Documentos por Vendedor',
                    'color' => '#9C27B0'
                ]
            ]),
            
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Inserir dashboard
        $table = $this->table('adms_dashboards');
        $table->insert($dashboardData)->saveData();
        
        $dashboardId = $this->getAdapter()->getConnection()->lastInsertId();
        
        echo "✅ Dashboard 'Dashboard de Vendas - RMVendas' criado com sucesso! (ID: {$dashboardId})\n";
        echo "📊 Configurações:\n";
        echo "   - 4 Medidas Calculadas: Ticket Médio, Margem %, Qtd Total, Custo Total\n";
        echo "   - 6 KPIs: Faturamento, Ticket Médio, Documentos, Margem, Quantidade, Custo\n";
        echo "   - 4 Filtros: Vendedor, Grupo de Parceiro, Ano, Mês\n";
        echo "   - 4 Gráficos: Barras (Faturamento), Pizza (Grupo), Barras (Qtd), Rosca (Docs)\n";
        echo "\n";
        echo "🔗 Acesse em: Relatórios → Meus Dashboards → Dashboard de Vendas - RMVendas\n";
    }
}

