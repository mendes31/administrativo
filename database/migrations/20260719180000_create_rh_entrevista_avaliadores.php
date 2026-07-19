<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 2 Expand — painel interno de avaliadores da entrevista (aditivo).
 * Não envia convite/e-mail e não altera autorização.
 */
final class CreateRhEntrevistaAvaliadores extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_entrevista_avaliadores')) {
            return;
        }

        $this->table('rh_entrevista_avaliadores', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_entrevista_id', 'integer', ['signed' => false])
            ->addColumn('avaliador_id', 'integer', ['signed' => false])
            ->addColumn('papel', 'string', [
                'limit' => 20,
                'default' => 'avaliador',
                'comment' => 'principal|avaliador',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'ativo',
                'comment' => 'ativo|removido',
            ])
            ->addColumn('designado_por', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('designado_at', 'datetime', ['null' => true])
            ->addColumn('removido_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_entrevista_id', 'avaliador_id'], [
                'unique' => true,
                'name' => 'uq_rh_entrevista_avaliador',
            ])
            ->addIndex(['rh_entrevista_id', 'status'], ['name' => 'idx_rh_entrevista_avaliadores_status'])
            ->addForeignKey('rh_entrevista_id', 'rh_entrevistas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_avaliadores_entrevista',
            ])
            ->addForeignKey('avaliador_id', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_avaliadores_user',
            ])
            ->addForeignKey('designado_por', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_avaliadores_designador',
            ])
            ->create();

        // Backfill do entrevistador principal legado.
        if ($this->hasTable('rh_entrevistas')) {
            $this->execute(
                "INSERT INTO rh_entrevista_avaliadores
                    (rh_entrevista_id, avaliador_id, papel, status, designado_at, created_at)
                 SELECT e.id, e.entrevistador_id, 'principal', 'ativo', NOW(), NOW()
                 FROM rh_entrevistas e
                 WHERE e.entrevistador_id IS NOT NULL
                   AND e.entrevistador_id > 0
                 ON DUPLICATE KEY UPDATE
                    papel = 'principal',
                    status = 'ativo',
                    removido_at = NULL,
                    updated_at = NOW()"
            );
        }
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevista_avaliadores')) {
            $this->table('rh_entrevista_avaliadores')->drop()->save();
        }
    }
}
