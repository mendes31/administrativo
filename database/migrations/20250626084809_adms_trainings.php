<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AdmsTrainings extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_trainings')) {
            $table = $this->table('adms_trainings');
            $table
                ->addColumn('nome', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('codigo', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('versao', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('validade', 'date', ['null' => true])
                ->addColumn('tipo', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('instrutor', 'string', ['limit' => 255, 'null' => true])
                ->addColumn('carga_horaria', 'integer', ['null' => true])
                ->addColumn('ativo', 'boolean', ['default' => 1, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->create();
        }
    }
    public function down(): void
    {
        $this->table('adms_trainings')->drop()->save();
    }
} 