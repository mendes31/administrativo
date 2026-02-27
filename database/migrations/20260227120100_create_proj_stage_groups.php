<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Grupos de etapas padrão para projetos.
 *
 * Cada grupo agrupa várias etapas unitárias em uma sequência reutilizável.
 */
final class CreateProjStageGroups extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_stage_groups')) {
            return;
        }

        $this->table('proj_stage_groups')
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
                'comment' => 'Nome do grupo de etapas (ex.: Projeto P&D padrão)',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => 1,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_stage_groups')) {
            $this->table('proj_stage_groups')->drop()->save();
        }
    }
}

