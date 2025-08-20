<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUpdatedAtToAdmsSessions extends AbstractMigration
{
    /**
     * Adiciona o campo updated_at na tabela adms_sessions.
     */
    public function up(): void
    {
        if ($this->hasTable('adms_sessions')) {
            $table = $this->table('adms_sessions');
            $table->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'created_at',
                'null' => false,
                'comment' => 'Última atualização da sessão',
            ])->update();
        }
    }

    /**
     * Remove o campo updated_at da tabela adms_sessions.
     */
    public function down(): void
    {
        if ($this->hasTable('adms_sessions')) {
            $table = $this->table('adms_sessions');
            $table->removeColumn('updated_at')->update();
        }
    }
} 