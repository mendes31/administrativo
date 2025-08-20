<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUniqueUserTrainingIndexToAdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if (!$table->hasIndex(['adms_user_id', 'adms_training_id'], ['name' => 'unique_user_training'])) {
                $table->addIndex(['adms_user_id', 'adms_training_id'], ['unique' => true, 'name' => 'unique_user_training'])->update();
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            if ($table->hasIndex(['adms_user_id', 'adms_training_id'], ['name' => 'unique_user_training'])) {
                $table->removeIndexByName('unique_user_training')->update();
            }
        }
    }
} 