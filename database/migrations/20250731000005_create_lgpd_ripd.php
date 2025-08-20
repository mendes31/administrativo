<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdRipd extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_ripd')) {
            $this->table('lgpd_ripd', ['id' => 'id'])
                ->addColumn('codigo', 'string', ['limit' => 20, 'null' => false, 'comment' => 'Código identificador ex: RIPD-001'])
                ->addColumn('aipd_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'AIPD relacionada'])
                ->addColumn('titulo', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Título do relatório'])
                ->addColumn('versao', 'string', ['limit' => 10, 'null' => false, 'default' => '1.0', 'comment' => 'Versão do relatório'])
                ->addColumn('data_elaboracao', 'date', ['null' => false, 'comment' => 'Data de elaboração'])
                ->addColumn('elaborador_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'Elaborador do relatório'])
                ->addColumn('revisor_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Revisor do relatório'])
                ->addColumn('aprovador_id', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Aprovador final'])
                ->addColumn('status', 'enum', ['values' => ['Rascunho', 'Em Revisão', 'Aprovado', 'Rejeitado'], 'default' => 'Rascunho', 'null' => false, 'comment' => 'Status do relatório'])
                ->addColumn('data_aprovacao', 'date', ['null' => true, 'comment' => 'Data de aprovação'])
                ->addColumn('observacoes_revisao', 'text', ['null' => true, 'comment' => 'Observações da revisão'])
                ->addColumn('observacoes_aprovacao', 'text', ['null' => true, 'comment' => 'Observações da aprovação'])
                ->addColumn('conclusao_geral', 'text', ['null' => false, 'comment' => 'Conclusão geral do relatório'])
                ->addColumn('recomendacoes_finais', 'text', ['null' => false, 'comment' => 'Recomendações finais'])
                ->addColumn('proximos_passos', 'text', ['null' => true, 'comment' => 'Próximos passos a serem tomados'])
                ->addColumn('prazo_implementacao', 'date', ['null' => true, 'comment' => 'Prazo para implementação das medidas'])
                ->addColumn('responsavel_implementacao', 'integer', ['null' => true, 'signed' => false, 'comment' => 'Responsável pela implementação'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addForeignKey('aipd_id', 'lgpd_aipd', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                ->addForeignKey('elaborador_id', 'adms_users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'NO_ACTION'])
                ->addForeignKey('revisor_id', 'adms_users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addForeignKey('aprovador_id', 'adms_users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addForeignKey('responsavel_implementacao', 'adms_users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
                ->addIndex(['codigo'], ['unique' => true])
                ->addIndex(['aipd_id'])
                ->addIndex(['status'])
                ->addIndex(['data_elaboracao'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_ripd')) {
            $this->table('lgpd_ripd')->drop()->save();
        }
    }
}
