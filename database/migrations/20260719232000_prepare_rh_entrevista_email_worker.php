<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Prepara snapshots ainda não enviados para o template v2 (SMTP habilitável).
 *
 * Somente registros recorded/ready/blocked são atualizados. Histórico terminal
 * (sent/failed/cancelled/processing) permanece imutável.
 */
final class PrepareRhEntrevistaEmailWorker extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_entrevista_comunicacoes')) {
            return;
        }

        $this->execute(
            "UPDATE rh_entrevista_comunicacoes
             SET template_version = 2,
                 body_text_snapshot = REPLACE(
                     body_text_snapshot,
                     'Esta mensagem ainda não é enviada automaticamente (registro interno).',
                     'Esta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.'
                 ),
                 body_html_snapshot = REPLACE(
                     body_html_snapshot,
                     '<p><em>Envio automático ainda não habilitado.</em></p>',
                     '<p><small>Esta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.</small></p>'
                 ),
                 updated_at = NOW()
             WHERE template_version = 1
               AND template_key IN ('rh.entrevista.agendada', 'rh.entrevista.reagendada')
               AND status IN ('recorded', 'ready', 'blocked')"
        );

        $table = $this->table('rh_entrevista_comunicacoes');
        if (!$table->hasIndex(['status', 'id'])) {
            $table->addIndex(
                ['status', 'id'],
                ['name' => 'idx_rh_entrevista_comunicacoes_worker']
            )->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_entrevista_comunicacoes')) {
            return;
        }

        $table = $this->table('rh_entrevista_comunicacoes');
        if ($table->hasIndexByName('idx_rh_entrevista_comunicacoes_worker')) {
            $table->removeIndexByName('idx_rh_entrevista_comunicacoes_worker')->update();
        }

        // Não rebaixa snapshots v2: eles podem ter sido criados após o deploy e
        // restaurar o aviso antigo tornaria o conteúdo incorreto.
    }
}
