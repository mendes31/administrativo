<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsDashboardsTable extends AbstractMigration
{
    public function change(): void
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('adms_dashboards')) {
            return;
        }
        
        $table = $this->table('adms_dashboards', ['id' => 'id', 'primary_key' => ['id']]);
        
        $table
            ->addColumn('name', 'string', ['limit' => 255, 'comment' => 'Nome do dashboard'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do dashboard'])
            ->addColumn('dynamic_report_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do relatório dinâmico base'])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que criou'])
            ->addColumn('is_public', 'boolean', ['default' => false, 'comment' => 'Dashboard público para todos'])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria do dashboard'])
            
            // Configurações de Medidas Calculadas (JSON)
            ->addColumn('measures_config', 'text', ['null' => true, 'comment' => 'Medidas calculadas (estilo DAX) em JSON'])
            
            // Configurações de KPIs (JSON)
            ->addColumn('kpis_config', 'text', ['null' => true, 'comment' => 'Configuração dos KPIs em JSON'])
            
            // Configurações de Gráficos (JSON)
            ->addColumn('charts_config', 'text', ['null' => true, 'comment' => 'Configuração dos gráficos em JSON'])
            
            // Configurações de Filtros (JSON)
            ->addColumn('filters_config', 'text', ['null' => true, 'comment' => 'Configuração dos filtros disponíveis em JSON'])
            
            // Layout
            ->addColumn('layout', 'string', ['limit' => 50, 'default' => 'default', 'comment' => 'Layout do dashboard (default, compact, full)'])
            
            // Contadores de uso
            ->addColumn('views_count', 'integer', ['default' => 0, 'comment' => 'Quantidade de visualizações'])
            ->addColumn('last_viewed_at', 'datetime', ['null' => true, 'comment' => 'Última visualização'])
            
            // Status
            ->addColumn('status', 'boolean', ['default' => true, 'comment' => 'Dashboard ativo'])
            
            // Timestamps
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            
            // Índices
            ->addIndex(['dynamic_report_id'])
            ->addIndex(['created_by'])
            ->addIndex(['category'])
            ->addIndex(['status'])
            
            // Foreign keys
            ->addForeignKey('dynamic_report_id', 'adms_dynamic_reports', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION'
            ])
            
            ->create();
    }
}

