<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 0.5/2 Expand — outbox genérica de eventos + intenções de comunicação de entrevista.
 * Não envia SMTP; apenas registra pending/recorded.
 */
final class CreateDomainEventOutboxAndRhEntrevistaComunicacoes extends AbstractMigration
{
    public function up(): void
    {
        $this->createOutbox();
        $this->createComunicacoes();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevista_comunicacoes')) {
            $this->table('rh_entrevista_comunicacoes')->drop()->save();
        }
        if ($this->hasTable('adms_domain_event_outbox')) {
            $this->table('adms_domain_event_outbox')->drop()->save();
        }
    }

    private function createOutbox(): void
    {
        if ($this->hasTable('adms_domain_event_outbox')) {
            return;
        }

        $this->table('adms_domain_event_outbox', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('event_name', 'string', ['limit' => 120])
            ->addColumn('event_version', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('aggregate_type', 'string', ['limit' => 64])
            ->addColumn('aggregate_id', 'integer', ['signed' => false])
            ->addColumn('idempotency_key', 'string', ['limit' => 190])
            ->addColumn('correlation_id', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('payload_json', 'text')
            ->addColumn('privacy_classification', 'string', [
                'limit' => 30,
                'default' => 'pessoal',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'pending',
                'comment' => 'pending|processing|published|failed',
            ])
            ->addColumn('attempt_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('available_at', 'datetime', ['null' => true])
            ->addColumn('locked_at', 'datetime', ['null' => true])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('last_error', 'text', ['null' => true])
            ->addColumn('occurred_at', 'datetime')
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['idempotency_key'], [
                'unique' => true,
                'name' => 'uq_adms_domain_event_outbox_idempotency',
            ])
            ->addIndex(['status', 'available_at'], ['name' => 'idx_adms_domain_event_outbox_poll'])
            ->addIndex(['aggregate_type', 'aggregate_id'], ['name' => 'idx_adms_domain_event_outbox_aggregate'])
            ->create();
    }

    private function createComunicacoes(): void
    {
        if ($this->hasTable('rh_entrevista_comunicacoes')) {
            return;
        }

        $table = $this->table('rh_entrevista_comunicacoes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_entrevista_id', 'integer', ['signed' => false])
            ->addColumn('rh_entrevista_reagendamento_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('outbox_event_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('channel', 'string', ['limit' => 20, 'default' => 'email'])
            ->addColumn('purpose', 'string', [
                'limit' => 30,
                'comment' => 'agendamento|reagendamento',
            ])
            ->addColumn('template_key', 'string', ['limit' => 80])
            ->addColumn('template_version', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('recipient_name', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('recipient_address', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('subject_snapshot', 'string', ['limit' => 250])
            ->addColumn('body_html_snapshot', 'text')
            ->addColumn('body_text_snapshot', 'text', ['null' => true])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'recorded',
                'comment' => 'recorded|ready|processing|sent|failed|cancelled|blocked',
            ])
            ->addColumn('attempt_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('processing_at', 'datetime', ['null' => true])
            ->addColumn('sent_at', 'datetime', ['null' => true])
            ->addColumn('failed_at', 'datetime', ['null' => true])
            ->addColumn('cancelled_at', 'datetime', ['null' => true])
            ->addColumn('provider_message_id', 'string', ['limit' => 190, 'null' => true])
            ->addColumn('last_error', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_entrevista_id', 'created_at'], [
                'name' => 'idx_rh_entrevista_comunicacoes_entrevista',
            ])
            ->addIndex(['outbox_event_id'], [
                'unique' => true,
                'name' => 'uq_rh_entrevista_comunicacoes_outbox',
            ])
            ->addForeignKey('rh_entrevista_id', 'rh_entrevistas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_comunicacoes_entrevista',
            ])
            ->addForeignKey('outbox_event_id', 'adms_domain_event_outbox', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_comunicacoes_outbox',
            ]);

        if ($this->hasTable('rh_entrevista_reagendamentos')) {
            $table->addForeignKey(
                'rh_entrevista_reagendamento_id',
                'rh_entrevista_reagendamentos',
                'id',
                [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_entrevista_comunicacoes_reagendamento',
                ]
            );
        }

        $table->create();
    }
}
