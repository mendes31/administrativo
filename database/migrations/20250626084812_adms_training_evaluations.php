<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsTrainingEvaluations extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_evaluations')) {
            $table = $this->table('adms_training_evaluations');
            $table
                ->addColumn('adms_training_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('tipo', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('respostas', 'text', ['null' => true])
                ->addColumn('data', 'date', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('adms_training_user_id', 'adms_training_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }
    public function down(): void
    {
        $this->table('adms_training_evaluations')->drop()->save();
    }
} 