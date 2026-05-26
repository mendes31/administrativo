<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSacClients extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('sac_clients')) {
            return;
        }

        $table = $this->table('sac_clients');

        $table->addColumn('code', 'string', ['limit' => 20])
              ->addColumn('razao_social', 'string', ['limit' => 255])
              ->addColumn('nome_fantasia', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('type_person', 'enum', ['values' => ['PF', 'PJ']])
              ->addColumn('document', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('contact_name', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('phone', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('mobile', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('email', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('zip_code', 'string', ['limit' => 10, 'null' => true])
              ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('number', 'string', ['limit' => 20, 'null' => true])
              ->addColumn('complement', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('neighborhood', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('city', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('state', 'string', ['limit' => 2, 'null' => true])
              ->addColumn('segment', 'string', ['limit' => 100, 'null' => true])
              ->addColumn('notes', 'text', ['null' => true])
              ->addColumn('status', 'enum', ['values' => ['Ativo', 'Inativo', 'Bloqueado'], 'default' => 'Ativo'])
              ->addColumn('created_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('updated_by', 'integer', ['null' => true, 'signed' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])

              ->addIndex(['code'], ['unique' => true])
              ->addIndex(['document'], ['unique' => true])
              ->addIndex(['razao_social'])
              ->addIndex(['status'])
              ->addIndex(['segment'])

              ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->addForeignKey('updated_by', 'adms_users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])

              ->create();
    }
}
