<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdRopa extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_ropa')) {
            $this->table('lgpd_ropa', ['id' => 'id'])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Código identificador ex: ROPA-001'])
                ->addColumn('atividade', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome da atividade/processo'])
                ->addColumn('departamento_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Departamento responsável'])
                ->addColumn('base_legal', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Base legal do tratamento'])
                ->addColumn('retencao', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Prazo de retenção'])
                ->addColumn('riscos', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Riscos identificados'])
                ->addColumn('status', 'enum', ['values' => ['Ativo', 'Revisão', 'Inativo'], 'default' => 'Ativo', 'null' => false, 'comment' => 'Status do registro'])
                ->addColumn('ultima_atualizacao', 'date', ['null' => true, 'comment' => 'Data da última atualização'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('departamento_id', 'adms_departments', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                ->addIndex(['codigo'], ['unique' => true])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_ropa')) {
            $this->table('lgpd_ropa')->drop()->save();
        }
    }
} 