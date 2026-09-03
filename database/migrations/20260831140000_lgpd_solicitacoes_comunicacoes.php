<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Histórico de comunicações com o titular (e-mail) e status "Aguardando titular".
 */
final class LgpdSolicitacoesComunicacoes extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('lgpd_solicitacoes_titulares')) {
            $this->execute(
                "ALTER TABLE lgpd_solicitacoes_titulares
                 MODIFY COLUMN status ENUM('Pendente','Em andamento','Aguardando titular','Concluída','Vencida')
                 NOT NULL DEFAULT 'Pendente'
                 COMMENT 'Status da solicitação'"
            );
        }

        if ($this->hasTable('lgpd_solicitacoes_comunicacoes')) {
            return;
        }

        $this->table('lgpd_solicitacoes_comunicacoes', ['id' => 'id'])
            ->addColumn('solicitacao_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('tipo', 'string', [
                'limit' => 40,
                'null' => false,
                'comment' => 'contatar|solicitar_info|responder|finalizar|interno',
            ])
            ->addColumn('assunto', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('mensagem', 'text', ['null' => false])
            ->addColumn('destinatario_email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('destinatario_nome', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('enviado', 'boolean', ['default' => false, 'null' => false])
            ->addColumn('erro_envio', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('user_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['solicitacao_id'], ['name' => 'idx_lgpd_com_solicitacao'])
            ->addForeignKey(
                'solicitacao_id',
                'lgpd_solicitacoes_titulares',
                'id',
                ['delete' => 'CASCADE', 'update' => 'CASCADE', 'constraint' => 'fk_lgpd_com_solicitacao']
            )
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('lgpd_solicitacoes_comunicacoes')) {
            $this->table('lgpd_solicitacoes_comunicacoes')->drop()->save();
        }

        if ($this->hasTable('lgpd_solicitacoes_titulares')) {
            $this->execute(
                "UPDATE lgpd_solicitacoes_titulares
                 SET status = 'Em andamento'
                 WHERE status = 'Aguardando titular'"
            );
            $this->execute(
                "ALTER TABLE lgpd_solicitacoes_titulares
                 MODIFY COLUMN status ENUM('Pendente','Em andamento','Concluída','Vencida')
                 NOT NULL DEFAULT 'Pendente'
                 COMMENT 'Status da solicitação'"
            );
        }
    }
}
