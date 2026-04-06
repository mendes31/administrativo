<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Alguns ambientes criaram ou alteraram adms_training_applications sem as colunas de auditoria.
 * O código (TrainingUsersRepository, TrainingStatusUpdaterService, índices) assume created_at/updated_at.
 */
final class EnsureCreatedAtUpdatedAtAdmsTrainingApplications extends AbstractMigration
{
    public function change(): void
    {
        if (!$this->hasTable('adms_training_applications')) {
            return;
        }

        $table = $this->table('adms_training_applications');
        $changed = false;

        if (!$table->hasColumn('created_at')) {
            $table->addColumn('created_at', 'datetime', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data/hora de criação do registro',
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => 'Data/hora da última alteração',
            ]);
            $changed = true;
        }

        if ($changed) {
            $table->update();
        }
    }
}
