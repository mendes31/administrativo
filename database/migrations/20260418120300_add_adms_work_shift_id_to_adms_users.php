<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Associa colaborador a um turno de trabalho cadastrado (opcional).
 */
final class AddAdmsWorkShiftIdToAdmsUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_users') || !$this->hasTable('adms_work_shifts')) {
            return;
        }

        $table = $this->table('adms_users');
        if ($table->hasColumn('adms_work_shift_id')) {
            return;
        }

        $table->addColumn('adms_work_shift_id', 'integer', [
            'signed' => false,
            'null' => true,
            'default' => null,
            'comment' => 'Turno de trabalho (cadastro Turnos)',
            'after' => 'immediate_supervisor_id',
        ])
            ->addForeignKey('adms_work_shift_id', 'adms_work_shifts', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_users')) {
            return;
        }
        $table = $this->table('adms_users');
        if ($table->hasForeignKey('adms_work_shift_id')) {
            $table->dropForeignKey('adms_work_shift_id');
        }
        if ($table->hasColumn('adms_work_shift_id')) {
            $table->removeColumn('adms_work_shift_id');
        }
        $table->update();
    }
}
