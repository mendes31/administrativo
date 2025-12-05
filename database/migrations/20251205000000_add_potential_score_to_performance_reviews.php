<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPotentialScoreToPerformanceReviews extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('adms_performance_reviews');
        
        if (!$table->hasColumn('potential_score')) {
            $table->addColumn('potential_score', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => true,
                'after' => 'overall_score',
                'comment' => 'Nota de potencial (0-10) para Matriz 9BOX'
            ])->update();
        }
    }
}

