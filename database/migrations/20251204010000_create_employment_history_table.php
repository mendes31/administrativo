<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmploymentHistoryTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_employment_history')) {
            $table = $this->table('adms_employment_history', [
                'id' => false,
                'primary_key' => ['id']
            ]);
            
            $table->addColumn('id', 'integer', [
                'identity' => true,
                'signed' => false
            ])
            ->addColumn('adms_user_id', 'integer', [
                'null' => false,
                'signed' => false,
                'comment' => 'ID do colaborador'
            ])
            ->addColumn('data_admissao', 'date', [
                'null' => false,
                'comment' => 'Data de admissão neste período'
            ])
            ->addColumn('data_desligamento', 'date', [
                'null' => true,
                'comment' => 'Data de desligamento neste período (null = ainda ativo)'
            ])
            ->addColumn('motivo_desligamento', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => 'Motivo do desligamento'
            ])
            ->addColumn('tipo_periodo', 'enum', [
                'values' => ['Admissão', 'Recontratação'],
                'default' => 'Admissão',
                'null' => false,
                'comment' => 'Tipo do período: primeira admissão ou recontratação'
            ])
            ->addColumn('observacoes', 'text', [
                'null' => true,
                'comment' => 'Observações adicionais sobre o período'
            ])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => false
            ])
            ->addForeignKey('adms_user_id', 'adms_users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE'
            ])
            ->addIndex(['adms_user_id'], [
                'name' => 'idx_employment_history_user_id'
            ])
            ->addIndex(['data_admissao'], [
                'name' => 'idx_employment_history_admissao'
            ])
            ->addIndex(['data_desligamento'], [
                'name' => 'idx_employment_history_desligamento'
            ])
            ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_employment_history')) {
            $this->table('adms_employment_history')->drop()->save();
        }
    }
}

