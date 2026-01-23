<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsSpreadsheetsTable extends AbstractMigration
{
    public function change(): void
    {
        // Verificar se a tabela já existe
        if ($this->hasTable('adms_spreadsheets')) {
            return;
        }
        
        $table = $this->table('adms_spreadsheets', ['id' => 'id', 'primary_key' => ['id']]);
        
        $table
            ->addColumn('name', 'string', ['limit' => 255, 'comment' => 'Nome da planilha'])
            ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição da planilha'])
            ->addColumn('file_name', 'string', ['limit' => 255, 'comment' => 'Nome original do arquivo'])
            ->addColumn('file_path', 'string', ['limit' => 500, 'comment' => 'Caminho do arquivo no servidor'])
            ->addColumn('file_type', 'string', ['limit' => 50, 'comment' => 'Tipo do arquivo (xlsx, xls, csv)'])
            ->addColumn('file_size', 'integer', ['signed' => false, 'comment' => 'Tamanho do arquivo em bytes'])
            ->addColumn('sheet_name', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Nome da aba/planilha (para Excel)'])
            ->addColumn('header_row', 'integer', ['default' => 1, 'comment' => 'Linha que contém os cabeçalhos (1-based)'])
            ->addColumn('data_start_row', 'integer', ['default' => 2, 'comment' => 'Linha onde começam os dados (1-based)'])
            ->addColumn('columns_config', 'text', ['null' => true, 'comment' => 'Configuração das colunas em JSON'])
            ->addColumn('total_rows', 'integer', ['default' => 0, 'comment' => 'Total de linhas de dados'])
            ->addColumn('total_columns', 'integer', ['default' => 0, 'comment' => 'Total de colunas'])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false, 'comment' => 'ID do usuário que criou'])
            ->addColumn('is_public', 'boolean', ['default' => false, 'comment' => 'Planilha pública para todos'])
            ->addColumn('category', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria da planilha'])
            ->addColumn('status', 'boolean', ['default' => true, 'comment' => 'Planilha ativa'])
            ->addColumn('last_processed_at', 'datetime', ['null' => true, 'comment' => 'Última vez que foi processada'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
            
            // Índices
            ->addIndex(['created_by'])
            ->addIndex(['category'])
            ->addIndex(['status'])
            ->addIndex(['file_type'])
            
            // Foreign keys
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION'
            ])
            
            ->create();
    }
}





