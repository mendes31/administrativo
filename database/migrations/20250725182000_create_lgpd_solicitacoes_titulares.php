<?php
use Phinx\Migration\AbstractMigration;

class CreateLgpdSolicitacoesTitulares extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('lgpd_solicitacoes_titulares')) {
            $this->table('lgpd_solicitacoes_titulares', ['id' => 'id'])
                ->addColumn('titular_nome', 'string', ['limit' => 150, 'null' => false, 'comment' => 'Nome do titular'])
                ->addColumn('titular_email', 'string', ['limit' => 150, 'null' => true, 'comment' => 'E-mail do titular'])
                ->addColumn('tipo', 'string', ['limit' => 100, 'null' => false, 'comment' => 'Tipo de solicitação (acesso, correção, exclusão, etc)'])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'comment' => 'Descrição detalhada'])
                ->addColumn('prioridade', 'enum', ['values' => ['Baixa', 'Média', 'Alta', 'Crítica'], 'default' => 'Média', 'null' => false, 'comment' => 'Prioridade'])
                ->addColumn('status', 'enum', ['values' => ['Pendente', 'Em andamento', 'Concluída', 'Vencida'], 'default' => 'Pendente', 'null' => false, 'comment' => 'Status da solicitação'])
                ->addColumn('responsavel', 'string', ['limit' => 100, 'null' => true, 'comment' => 'Responsável pelo atendimento'])
                ->addColumn('prazo', 'date', ['null' => true, 'comment' => 'Prazo para atendimento'])
                ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'datetime', ['null' => true, 'default' => null])
                ->addIndex(['titular_email'])
                ->create();
        }
    }

    public function down()
    {
        if ($this->hasTable('lgpd_solicitacoes_titulares')) {
            $this->table('lgpd_solicitacoes_titulares')->drop()->save();
        }
    }
} 