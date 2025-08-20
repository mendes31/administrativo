<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdTreinamentos extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_treinamentos')) {
            $this->table('lgpd_treinamentos', ['id' => 'id'])
                ->addColumn('titulo', 'string', ['limit' => 200, 'null' => false, 'comment' => 'Título do treinamento'])
                ->addColumn('categoria', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Categoria do treinamento'])
                ->addColumn('departamento_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Departamento responsável'])
                ->addColumn('duracao', 'string', ['limit' => 20, 'null' => true, 'comment' => 'Duração (ex: 2h, 4h)'])
                ->addColumn('progresso', 'string', ['limit' => 20, 'null' => true, 'comment' => 'Progresso (ex: 10/20)'])
                ->addColumn('status', 'enum', ['values' => ['Em Andamento', 'Concluído', 'Agendado'], 'default' => 'Agendado', 'null' => false, 'comment' => 'Status do treinamento'])
                ->addColumn('responsavel', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Responsável'])
                ->addColumn('data_realizacao', 'date', ['null' => true, 'comment' => 'Data de realização'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('departamento_id', 'adms_departments', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_treinamentos')) {
            $this->table('lgpd_treinamentos')->drop()->save();
        }
    }
} 