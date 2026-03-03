<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEstimatedWorkdaysToProjStages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('proj_stages')) {
            return;
        }

        $table = $this->table('proj_stages');
        if (!$table->hasColumn('estimated_workdays')) {
            $table
                ->addColumn('estimated_workdays', 'integer', [
                    'null' => false,
                    'default' => 0,
                    'signed' => false,
                    'comment' => 'Prazo previsto em dias uteis para agendamento automático',
                ])
                ->addIndex(['estimated_workdays'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('proj_stages')) {
            $table = $this->table('proj_stages');
            if ($table->hasColumn('estimated_workdays')) {
                $table->removeColumn('estimated_workdays')->update();
            }
        }
    }
}

