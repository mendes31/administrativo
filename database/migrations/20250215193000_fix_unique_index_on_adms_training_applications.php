<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixUniqueIndexOnAdmsTrainingApplications extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_applications')) {
            // Cria o índice correto (não tenta remover antes)
            $this->table('adms_training_applications')
                ->addIndex(['adms_user_id', 'adms_training_id', 'nota', 'created_at'], ['unique' => true, 'name' => 'idx_unique_application'])
                ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_applications')) {
            $this->table('adms_training_applications')
                ->removeIndex(['adms_user_id', 'adms_training_id', 'nota', 'created_at'])
                ->update();
        }
    }
} 