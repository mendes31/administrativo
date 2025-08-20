<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMotivoToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if (!$table->hasColumn('motivo')) {
                $table->addColumn('motivo', 'string', [
                    'limit' => 20,
                    'default' => 'primeiro',
                    'null' => true,
                    'after' => 'tipo_vinculo',
                    'comment' => 'Motivo do ciclo: primeiro, reciclagem, retreinamento'
                ])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if ($table->hasColumn('motivo')) {
                $table->removeColumn('motivo')->update();
            }
        }
    }
} 