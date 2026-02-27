<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Catálogo de etapas de projeto (unitárias).
 *
 * Usadas tanto diretamente nos projetos quanto dentro de grupos de etapas.
 */
final class CreateProjStages extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_stages')) {
            return;
        }

        $this->table('proj_stages')
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
                'comment' => 'Nome da etapa (ex.: Levantamento, Formação de custo)',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
            ])
            ->addColumn('sequence_default', 'integer', [
                'null' => false,
                'default' => 1,
                'signed' => false,
                'comment' => 'Ordem sugerida quando usada em grupos ou templates',
            ])
            ->addColumn('is_cost_stage', 'boolean', [
                'null' => false,
                'default' => 0,
                'comment' => '1 = etapa relacionada à formação de custos',
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
            ->addIndex(['active'])
            ->addIndex(['sequence_default'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_stages')) {
            $this->table('proj_stages')->drop()->save();
        }
    }
}

