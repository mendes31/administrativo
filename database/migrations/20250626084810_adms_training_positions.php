<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsTrainingPositions extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_training_positions')) {
            $table = $this->table('adms_training_positions');
            $table
                ->addColumn('adms_training_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('adms_position_id', 'integer', ['null' => false, 'signed' => false])
                ->addColumn('obrigatorio', 'boolean', ['default' => 0, 'null' => false])
                ->addColumn('reciclagem_periodo', 'integer', ['null' => true, 'comment' => 'Período em meses'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addForeignKey('adms_training_id', 'adms_trainings', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->addForeignKey('adms_position_id', 'adms_positions', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
                ->create();
        }
    }
    public function down(): void
    {
        $this->table('adms_training_positions')->drop()->save();
    }
} 