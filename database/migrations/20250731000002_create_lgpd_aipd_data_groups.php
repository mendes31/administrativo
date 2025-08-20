<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdAipdDataGroups extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_aipd_data_groups')) {
            $this->table('lgpd_aipd_data_groups', ['id' => 'id'])
                ->addColumn('aipd_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID da AIPD'])
                ->addColumn('data_group_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'ID do grupo de dados'])
                ->addColumn('impacto_privacidade', 'enum', ['values' => ['Baixo', 'Médio', 'Alto', 'Crítico'], 'default' => 'Médio', 'null' => false, 'comment' => 'Impacto na privacidade'])
                ->addColumn('probabilidade_ocorrencia', 'enum', ['values' => ['Baixa', 'Média', 'Alta'], 'default' => 'Média', 'null' => false, 'comment' => 'Probabilidade de ocorrência'])
                ->addColumn('medidas_mitigacao', 'text', ['null' => true, 'comment' => 'Medidas de mitigação propostas'])
                ->addColumn('observacoes', 'text', ['null' => true, 'comment' => 'Observações específicas'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('aipd_id', 'lgpd_aipd', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addForeignKey('data_group_id', 'lgpd_data_groups', 'id', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
                ->addIndex(['aipd_id', 'data_group_id'], ['unique' => true])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_aipd_data_groups')) {
            $this->table('lgpd_aipd_data_groups')->drop()->save();
        }
    }
}
