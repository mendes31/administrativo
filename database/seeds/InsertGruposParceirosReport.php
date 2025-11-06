<?php

use Phinx\Seed\AbstractSeed;

/**
 * Criar relatório de Grupos de Parceiros para usar em filtros
 */
class InsertGruposParceirosReport extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se já existe
        $existing = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Grupos de Parceiros'");
        
        if ($existing) {
            echo "ℹ️  Relatório '[FILTRO] Grupos de Parceiros' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Query fornecida pelo usuário
        $sql = 'SELECT
    "T0"."GroupCode",
    "T0"."GroupName",
    "T0"."GroupType",
    "T0"."Locked",
    "T0"."DataSource",
    "T0"."UserSign",
    "T0"."PriceList",
    "T0"."DiscRel",
    "T0"."EffecPrice",
    "T0"."U_Bpx"
FROM OCRG T0
ORDER BY T0."GroupName"';
        
        // Inserir relatório
        $report = [
            'name' => '[FILTRO] Grupos de Parceiros',
            'description' => 'Lista de grupos de parceiros de negócios para usar em filtros de dashboards',
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
        
        echo "✅ Relatório '[FILTRO] Grupos de Parceiros' criado! (ID: {$reportId})\n";
        echo "   Este relatório pode ser usado para popular filtros de grupos\n";
    }
}

