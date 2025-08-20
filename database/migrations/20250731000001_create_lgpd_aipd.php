<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdAipd extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_aipd')) {
            $this->table('lgpd_aipd', ['id' => 'id'])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Código identificador ex: AIPD-001'])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título da avaliação'])
                ->addColumn('descricao', 'text', ['null' => true, 'comment' => 'Descrição detalhada'])
                ->addColumn('ropa_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'ROPA relacionada'])
                ->addColumn('departamento_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Departamento responsável'])
                ->addColumn('responsavel_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Responsável pela avaliação'])
                ->addColumn('data_inicio', 'date', ['null' => false, 'comment' => 'Data de início'])
                ->addColumn('data_conclusao', 'date', ['null' => true, 'comment' => 'Data de conclusão'])
                ->addColumn('status', 'enum', ['values' => ['Em Andamento', 'Concluída', 'Aprovada', 'Revisão'], 'default' => 'Em Andamento', 'null' => false, 'comment' => 'Status da avaliação'])
                ->addColumn('nivel_risco', 'enum', ['values' => ['Baixo', 'Médio', 'Alto', 'Crítico'], 'default' => 'Médio', 'null' => false, 'comment' => 'Nível de risco identificado'])
                ->addColumn('necessita_anpd', 'boolean', ['default' => false, 'comment' => 'Necessita notificação à ANPD'])
                ->addColumn('observacoes', 'text', ['null' => true, 'comment' => 'Observações gerais'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('ropa_id', 'lgpd_ropa', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addForeignKey('departamento_id', 'adms_departments', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                ->addForeignKey('responsavel_id', 'adms_users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['status'])
                ->addIndex(['nivel_risco'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_aipd')) {
            $this->table('lgpd_aipd')->drop()->save();
        }
    }
}
