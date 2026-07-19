<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 2 Expand — histórico append-only de reagendamento de entrevista.
 * Não altera a agenda linear nem dispara comunicação.
 */
final class CreateRhEntrevistaReagendamentos extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_entrevista_reagendamentos')) {
            return;
        }

        $this->table('rh_entrevista_reagendamentos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_entrevista_id', 'integer', ['signed' => false])
            ->addColumn('data_hora_anterior', 'datetime')
            ->addColumn('data_hora_nova', 'datetime')
            ->addColumn('motivo', 'string', ['limit' => 500])
            ->addColumn('reagendado_por', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['rh_entrevista_id', 'created_at'], [
                'name' => 'idx_rh_entrevista_reagendamentos_entrevista',
            ])
            ->addForeignKey('rh_entrevista_id', 'rh_entrevistas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_reagendamentos_entrevista',
            ])
            ->addForeignKey('reagendado_por', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_entrevista_reagendamentos_user',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevista_reagendamentos')) {
            $this->table('rh_entrevista_reagendamentos')->drop()->save();
        }
    }
}
