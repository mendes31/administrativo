<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdTiaDataGroups extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_tia_data_groups')) {
            $this->table('lgpd_tia_data_groups', ['id' => 'id'])
                ->addColumn('tia_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do TIA'])
                ->addColumn('data_group_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do grupo de dados'])
                ->addColumn('volume_dados', 'enum', ['values' => ['Baixo', 'Médio', 'Alto'], 'default' => 'Médio', 'null' => false, 'comment' => 'Volume de dados processados'])
                ->addColumn('sensibilidade', 'enum', ['values' => ['Baixa', 'Média', 'Alta'], 'default' => 'Média', 'null' => false, 'comment' => 'Sensibilidade dos dados'])
                ->addColumn('observacoes', 'text', ['null' => true, 'comment' => 'Observações específicas'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('tia_id', 'lgpd_tia', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addForeignKey('data_group_id', 'lgpd_data_groups', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addIndex(['tia_id', 'data_group_id'], ['unique' => true])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_tia_data_groups')) {
            $this->table('lgpd_tia_data_groups')->drop()->save();
        }
    }
}
