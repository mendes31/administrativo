<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Itens de grupo de etapas.
 *
 * Liga grupos de etapas (`proj_stage_groups`) às etapas unitárias (`proj_stages`)
 * definindo a sequência padrão dentro de cada grupo.
 */
final class CreateProjStageGroupItems extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_stage_group_items')) {
            return;
        }

        $this->table('proj_stage_group_items')
            ->addColumn('stage_group_id', 'integer', [
                'null' => false,
                'signed' => false,
                'comment' => 'Grupo de etapas (proj_stage_groups.id)',
            ])
            ->addColumn('stage_id', 'integer', [
                'null' => false,
                'signed' => false,
                'comment' => 'Etapa unitária (proj_stages.id)',
            ])
            ->addColumn('sequence', 'integer', [
                'null' => false,
                'default' => 1,
                'signed' => false,
                'comment' => 'Ordem da etapa dentro do grupo',
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['stage_group_id'])
            ->addIndex(['stage_id'])
            ->addIndex(['stage_group_id', 'sequence'], [
                'name' => 'idx_proj_stage_group_items_group_sequence',
                'unique' => false,
            ])
            ->addForeignKey('stage_group_id', 'proj_stage_groups', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('stage_id', 'proj_stages', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_stage_group_items')) {
            $this->table('proj_stage_group_items')->drop()->save();
        }
    }
}

