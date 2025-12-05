<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRequestTypesTable extends AbstractMigration
{
    public function change(): void
    {
        // Tabela de Tipos de Solicitação
        if (!$this->hasTable('adms_request_types')) {
            $table = $this->table('adms_request_types', ['id' => 'id', 'primary_key' => ['id']]);
            
            $table
                ->addColumn('code', 'string', ['limit' => 50, 'null' => false, 'comment' => 'Código único do tipo (ex: vacation, time_off)'])
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome do tipo (ex: Férias, Afastamento)'])
                ->addColumn('description', 'text', ['null' => true, 'comment' => 'Descrição do tipo'])
                ->addColumn('requires_manager_approval', 'boolean', ['default' => true, 'comment' => 'Se requer aprovação do gestor antes do RH'])
                ->addColumn('requires_dates', 'boolean', ['default' => false, 'comment' => 'Se requer datas (início e término)'])
                ->addColumn('requires_days', 'boolean', ['default' => false, 'comment' => 'Se requer quantidade de dias'])
                ->addColumn('requires_amount', 'boolean', ['default' => false, 'comment' => 'Se requer valor'])
                ->addColumn('icon', 'string', ['limit' => 50, 'null' => true, 'comment' => 'Ícone FontAwesome'])
                ->addColumn('color', 'string', ['limit' => 20, 'null' => true, 'default' => 'primary', 'comment' => 'Cor do badge'])
                ->addColumn('status', 'boolean', ['default' => true, 'comment' => 'Status ativo/inativo'])
                ->addColumn('sort_order', 'integer', ['default' => 0, 'comment' => 'Ordem de exibição'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP'])
                
                ->addIndex(['code'], ['unique' => true])
                ->addIndex(['status'])
                ->addIndex(['sort_order'])
                
                ->create();
        }
    }
}

