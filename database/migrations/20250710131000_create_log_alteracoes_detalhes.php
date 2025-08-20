<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLogAlteracoesDetalhes extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_log_alteracoes_detalhes')) {
            $table = $this->table('adms_log_alteracoes_detalhes');
            $table->addColumn('log_alteracao_id', 'integer', ['signed' => false])
                ->addColumn('campo', 'string', ['limit' => 100])
                ->addColumn('valor_anterior', 'text', ['null' => true])
                ->addColumn('valor_novo', 'text', ['null' => true])
                ->addForeignKey('log_alteracao_id', 'adms_log_alteracoes', 'id')
                ->create();
        }
    }

    public function down(): void
    {
        $this->table('adms_log_alteracoes_detalhes')->drop()->save();
    }
} 