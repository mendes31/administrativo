<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 1 Expand — catálogo de etapas do pipeline ATS (mesmos 5 códigos).
 * Rótulo, ordem e classe visual configuráveis; códigos permanece estáveis.
 */
final class CreateRhPipelineStages extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->seedDefaults();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_pipeline_stages')) {
            $this->table('rh_pipeline_stages')->drop()->save();
        }
    }

    private function createTable(): void
    {
        if ($this->hasTable('rh_pipeline_stages')) {
            return;
        }

        $this->table('rh_pipeline_stages', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('code', 'string', ['limit' => 30])
            ->addColumn('label', 'string', ['limit' => 100])
            ->addColumn('display_order', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('column_class', 'string', [
                'limit' => 64,
                'default' => 'bg-light',
                'comment' => 'Classe Bootstrap do corpo da coluna Kanban',
            ])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['code'], ['unique' => true, 'name' => 'uq_rh_pipeline_stages_code'])
            ->addIndex(['display_order'], ['name' => 'idx_rh_pipeline_stages_order'])
            ->addIndex(['is_active'], ['name' => 'idx_rh_pipeline_stages_active'])
            ->create();
    }

    private function seedDefaults(): void
    {
        if (!$this->hasTable('rh_pipeline_stages')) {
            return;
        }

        $stages = [
            ['candidatado', 'Candidatado', 10, 'bg-light'],
            ['em_entrevista', 'Em Entrevista', 20, 'bg-warning-subtle'],
            ['aprovado', 'Aprovado', 30, 'bg-success-subtle'],
            ['reprovado', 'Reprovado', 40, 'bg-danger-subtle'],
            ['desistiu', 'Desistiu', 50, 'bg-secondary-subtle'],
        ];

        foreach ($stages as [$code, $label, $order, $columnClass]) {
            $existing = $this->fetchRow(
                "SELECT id FROM rh_pipeline_stages WHERE code = '{$code}' LIMIT 1"
            );
            if ($existing) {
                continue;
            }

            $this->table('rh_pipeline_stages')->insert([
                'code' => $code,
                'label' => $label,
                'display_order' => $order,
                'column_class' => $columnClass,
                'is_active' => 1,
            ])->saveData();
        }
    }
}
