<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProgressPercentageToAdmsStrategicPlans extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_strategic_plans')) {
            $table = $this->table('adms_strategic_plans');
            
            if (!$table->hasColumn('progress_percentage')) {
                $table->addColumn('progress_percentage', 'integer', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'completed',
                    'comment' => 'Percentual de progresso do plano (0-100)'
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_strategic_plans')) {
            $table = $this->table('adms_strategic_plans');
            
            if ($table->hasColumn('progress_percentage')) {
                $table->removeColumn('progress_percentage')->update();
            }
        }
    }
}



