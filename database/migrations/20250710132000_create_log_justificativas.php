<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLogJustificativas extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_justificativas')) {
            $table = $this->table('adms_log_justificativas');
            $table->addColumn('log_alteracao_id', 'integer', ['signed' => false])
                ->addColumn('justificativa', 'text')
                ->addColumn('assinatura', 'string', ['limit' => 255])
                ->addColumn('data_justificativa', 'datetime')
                ->addForeignKey('log_alteracao_id', 'adms_log_alteracoes', 'id')
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_log_justificativas')->drop()->save();
    }
} 