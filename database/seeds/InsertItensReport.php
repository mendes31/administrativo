<?php

use Phinx\Seed\AbstractSeed;

/**
 * Criar relatório de Itens para usar em filtros
 */
class InsertItensReport extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se já existe
        $existing = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Itens'");
        
        if ($existing) {
            echo "ℹ️  Relatório '[FILTRO] Itens' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Query fornecida pelo usuário
        $sql = 'SELECT
    T0."ItemCode" AS "cdItem",
    T0."ItemName" AS "nomeItem",
    T0."ItmsGrpCod" AS "GrupoItensID",
    T1."ItmsGrpNam" AS "GrupoItens"
FROM OITM T0
INNER JOIN OITB T1 ON T0."ItmsGrpCod" = T1."ItmsGrpCod"
ORDER BY T0."ItemCode"';
        
        // Inserir relatório
        $report = [
            'name' => '[FILTRO] Itens',
            'description' => 'Lista de itens/produtos para usar em filtros de dashboards',
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
            'category' => 'Filtros',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $table = $this->table('adms_dynamic_reports');
        $table->insert($report)->saveData();
        
        $reportId = $this->getAdapter()->getConnection()->lastInsertId();
        
        echo "✅ Relatório '[FILTRO] Itens' criado! (ID: {$reportId})\n";
        echo "   Este relatório pode ser usado para popular filtros de itens\n";
    }
}

