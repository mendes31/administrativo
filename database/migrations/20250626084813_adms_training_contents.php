<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsTrainingContents extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_contents')) {
            $table = $this->table('adms_training_contents');
            $table
                ->addColumn('adms_training_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('tipo', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('arquivo', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('link', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('descricao', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('adms_training_id', 'adms_trainings', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }
    public function down(): void
    {
        $this->table('adms_training_contents')->drop()->save();
    }
} 