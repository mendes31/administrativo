<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona o campo default_page à tabela adms_pages.
 *
 * Este campo indica se a página deve ser considerada
 * "padrão" para novos níveis de acesso e para a
 * inicialização automática de permissões.
 */
final class AddDefaultPageToAdmsPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $table = $this->table('adms_pages');

        if (!$table->hasColumn('default_page')) {
            $table
                ->addColumn('default_page', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'public_page',
                ])
                ->update();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $table = $this->table('adms_pages');

        if ($table->hasColumn('default_page')) {
            $table
                ->removeColumn('default_page')
                ->update();
        }
    }
}

