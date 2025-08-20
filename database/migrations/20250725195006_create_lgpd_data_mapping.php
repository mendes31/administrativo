<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdDataMapping extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_data_mapping')) {
            $this->table('lgpd_data_mapping', ['id' => 'id'])
                ->addColumn('source_system', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Sistema de origem'])
                ->addColumn('source_field', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Campo origem'])
                ->addColumn('transformation_rule', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Regra de transformação'])
                ->addColumn('destination_system', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Sistema destino'])
                ->addColumn('destination_field', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Campo destino'])
                ->addColumn('observation', 'text', ['null' => true, 'comment' => 'Observações (LGPD)'])
                ->addColumn('ropa_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Relaciona com operação ROPA'])
                ->addColumn('inventory_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Relaciona com item do inventário'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('ropa_id', 'lgpd_ropa', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addForeignKey('inventory_id', 'lgpd_inventory', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addIndex(['source_system'])
                ->addIndex(['destination_system'])
                ->addIndex(['ropa_id'])
                ->addIndex(['inventory_id'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_data_mapping')) {
            $this->table('lgpd_data_mapping')->drop()->save();
        }
    }
}