<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Etapas instanciadas dentro de cada projeto.
 *
 * Podem ser criadas a partir de etapas unitárias e/ou grupos padrão.
 */
final class CreateProjProjectStages extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('proj_project_stages')) {
            return;
        }

        $this->table('proj_project_stages')
            ->addColumn('project_id', 'integer', [
                'null' => false,
                'signed' => false,
                'comment' => 'Projeto (proj_projects.id)',
            ])
            ->addColumn('stage_id', 'integer', [
                'null' => true,
                'signed' => false,
                'comment' => 'Etapa do catálogo (proj_stages.id) se houver vínculo',
            ])
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
                'comment' => 'Nome da etapa no contexto do projeto',
            ])
            ->addColumn('sequence', 'integer', [
                'null' => false,
                'default' => 1,
                'signed' => false,
                'comment' => 'Ordem da etapa dentro do projeto',
            ])
            ->addColumn('is_cost_stage', 'boolean', [
                'null' => false,
                'default' => 0,
                'comment' => '1 = etapa relacionada à formação de custos',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'NAO_INICIADO',
                'comment' => 'Status da etapa (pode reaproveitar status de tarefa)',
            ])
            ->addColumn('percent_complete', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => false,
                'default' => 0,
                'comment' => 'Percentual concluído da etapa (0-100)',
            ])
            ->addColumn('start_date', 'date', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('expected_end_date', 'date', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('end_date', 'date', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['project_id'])
            ->addIndex(['stage_id'])
            ->addIndex(['project_id', 'sequence'], [
                'name' => 'idx_proj_project_stages_project_sequence',
                'unique' => false,
            ])
            ->addForeignKey('project_id', 'proj_projects', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('stage_id', 'proj_stages', 'id', [
                'delete' => 'SET NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('proj_project_stages')) {
            $this->table('proj_project_stages')->drop()->save();
        }
    }
}

