<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdTia extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_tia')) {
            $this->table('lgpd_tia', ['id' => 'id'])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Código identificador ex: TIA-001'])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título do teste'])
                ->addColumn('descricao', 'text', ['null' => true, 'comment' => 'Descrição da atividade'])
                ->addColumn('ropa_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'ROPA relacionada'])
                ->addColumn('departamento_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Departamento responsável'])
                ->addColumn('responsavel_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Responsável pelo teste'])
                ->addColumn('data_teste', 'date', ['null' => false, 'comment' => 'Data do teste'])
                ->addColumn('resultado', 'enum', ['values' => ['Baixo Risco', 'Médio Risco', 'Alto Risco', 'Necessita AIPD'], 'default' => 'Médio Risco', 'null' => false, 'comment' => 'Resultado do teste'])
                ->addColumn('justificativa', 'text', ['null' => true, 'comment' => 'Justificativa do resultado'])
                ->addColumn('recomendacoes', 'text', ['null' => true, 'comment' => 'Recomendações'])
                ->addColumn('status', 'enum', ['values' => ['Em Andamento', 'Concluído', 'Aprovado'], 'default' => 'Em Andamento', 'null' => false, 'comment' => 'Status do teste'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('ropa_id', 'lgpd_ropa', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addForeignKey('departamento_id', 'adms_departments', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                ->addForeignKey('responsavel_id', 'adms_users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['resultado'])
                ->addIndex(['status'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_tia')) {
            $this->table('lgpd_tia')->drop()->save();
        }
    }
}
