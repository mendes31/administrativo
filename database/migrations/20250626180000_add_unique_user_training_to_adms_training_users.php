<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueUserTrainingToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            $table->addIndex(['adms_user_id', 'adms_training_id'], ['unique' => true, 'name' => 'unique_user_training'])
                  ->update();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            $table->removeIndex(['adms_user_id', 'adms_training_id'])
                  ->update();
        }
    }
} 