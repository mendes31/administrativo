<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSpreadsheetSupportToDashboards extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_dashboards');
        
        // Adicionar coluna para suportar planilhas como fonte de dados
        if (!$table->hasColumn('spreadsheet_id')) {
            $table
                ->addColumn('spreadsheet_id', 'integer', [
                    'signed' => false, 
                    'null' => true, 
                    'after' => 'dynamic_report_id',
                    'comment' => 'ID da planilha (fonte de dados alternativa)'
                ])
                ->addIndex(['spreadsheet_id'])
                ->addForeignKey('spreadsheet_id', 'adms_spreadsheets', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION'
                ])
                ->update();
        }
        
        // Adicionar coluna para indicar tipo de fonte de dados
        if (!$table->hasColumn('data_source_type')) {
            $table
                ->addColumn('data_source_type', 'string', [
                    'limit' => 20, 
                    'default' => 'report',
                    'after' => 'spreadsheet_id',
                    'comment' => 'Tipo de fonte: report, spreadsheet, mixed'
                ])
                ->update();
        }
    }
}





