<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTimeUnitToInvItemOperations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');
        if (!$table->hasColumn('time_unit')) {
            $table
                ->addColumn('time_unit', 'string', [
                    'limit' => 5,
                    'default' => 'MIN',
                    'null' => false,
                    'after' => 'time_per_batch_hours',
                    'comment' => 'Unidade de tempo: MIN (minutos) ou H (horas)',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('inv_item_operations')) {
            return;
        }

        $table = $this->table('inv_item_operations');
        if ($table->hasColumn('time_unit')) {
            $table->removeColumn('time_unit')->update();
        }
    }
}

