<?php

use Phinx\Seed\AbstractSeed;

/**
 * Criar relatório de Vendedores/Compradores para usar em filtros
 */
class InsertVendedoresReport extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se já existe
        $existing = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Vendedores'");
        
        if ($existing) {
            echo "ℹ️  Relatório '[FILTRO] Vendedores' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Query fornecida pelo usuário
        $sql = 'SELECT
    "SlpCode" AS "Código",
    "SlpName" AS "Vendedor_Comprador",
    "Memo" AS "Função",
    CASE WHEN "U_SD_TipoVendedor" = \'I\' 
        THEN \'INTERNO\' 
        WHEN "U_SD_TipoVendedor" = \'E\' 
        THEN \'EXTERNO\' 
    END AS "Tipo",
    "Active" 
FROM OSLP 
WHERE "Active" = \'Y\'
ORDER BY "SlpCode"';
        
        // Inserir relatório
        $report = [
            'name' => '[FILTRO] Vendedores',
            'description' => 'Lista de vendedores/compradores ativos para usar em filtros de dashboards',
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
        
        echo "✅ Relatório '[FILTRO] Vendedores' criado! (ID: {$reportId})\n";
        echo "   Este relatório pode ser usado para popular filtros de vendedores\n";
    }
}

