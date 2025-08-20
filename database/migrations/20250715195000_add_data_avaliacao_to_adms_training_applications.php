<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDataAvaliacaoToAdmsTrainingApplications extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_applications')) {
            $table = $this->table('adms_training_applications');
            if (!$table->hasColumn('data_avaliacao')) {
                $table->addColumn('data_avaliacao', 'date', [
                    'null' => true,
                    'after' => 'data_realizacao',
                    'comment' => 'Data de avaliação do treinamento'
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_applications')) {
            $table = $this->table('adms_training_applications');
            if ($table->hasColumn('data_avaliacao')) {
                $table->removeColumn('data_avaliacao')->update();
            }
        }
    }
} 