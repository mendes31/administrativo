<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsTrainingUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_users')) {
            $table = $this->table('adms_training_users');
            $table
                ->addColumn('adms_training_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_user_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('data_realizacao', 'date', ['null' => true])
                ->addColumn('status', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('nota', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
                ->addColumn('certificado', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('adms_training_id', 'adms_trainings', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_user_id', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }
    public function down(): void
    {
        $this->table('adms_training_users')->drop()->save();
    }
} 