<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddReciclagemFieldsToAdmsTrainings extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if (!$table->hasColumn('reciclagem')) {
                $table->addColumn('reciclagem', 'boolean', [
                    'default' => false,
                    'null' => false,
                    'after' => 'instrutor',
                    'comment' => 'Se o treinamento exige reciclagem'
                ]);
            }
            if (!$table->hasColumn('reciclagem_periodo')) {
                $table->addColumn('reciclagem_periodo', 'integer', [
                    'null' => true,
                    'after' => 'reciclagem',
                    'comment' => 'Período de reciclagem em meses'
                ]);
            }
            $table->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            if ($table->hasColumn('reciclagem')) {
                $table->removeColumn('reciclagem');
            }
            if ($table->hasColumn('reciclagem_periodo')) {
                $table->removeColumn('reciclagem_periodo');
            }
            $table->update();
        }
    }
} 