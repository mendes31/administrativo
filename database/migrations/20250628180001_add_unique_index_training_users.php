<?php
use Phinx\Migration\AbstractMigration;

class AddUniqueIndexTrainingUsers extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('adms_training_users');
        $table->addIndex(['adms_user_id', 'adms_training_id'], ['unique' => true, 'name' => 'idx_user_training_unique'])
              ->update();
    }
} 