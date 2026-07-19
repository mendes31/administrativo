<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 2 Expand — scorecard estruturado de entrevista (aditivo).
 * Não altera resultado/feedback legados nem o pipeline.
 */
final class CreateRhEntrevistaScorecards extends AbstractMigration
{
    public function up(): void
    {
        $this->createScorecards();
        $this->createItens();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_entrevista_scorecard_itens')) {
            $this->table('rh_entrevista_scorecard_itens')->drop()->save();
        }
        if ($this->hasTable('rh_entrevista_scorecards')) {
            $this->table('rh_entrevista_scorecards')->drop()->save();
        }
    }

    private function createScorecards(): void
    {
        if ($this->hasTable('rh_entrevista_scorecards')) {
            return;
        }

        $this->table('rh_entrevista_scorecards', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_entrevista_id', 'integer', ['signed' => false])
            ->addColumn('avaliador_id', 'integer', ['signed' => false])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'rascunho',
                'comment' => 'rascunho|finalizado',
            ])
            ->addColumn('parecer', 'text', ['null' => true])
            ->addColumn('nota_ponderada', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => true,
            ])
            ->addColumn('finalizado_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_entrevista_id', 'avaliador_id'], [
                'unique' => true,
                'name' => 'uq_rh_scorecard_entrevista_avaliador',
            ])
            ->addForeignKey('rh_entrevista_id', 'rh_entrevistas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_scorecard_entrevista',
            ])
            ->addForeignKey('avaliador_id', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_scorecard_avaliador',
            ])
            ->create();
    }

    private function createItens(): void
    {
        if ($this->hasTable('rh_entrevista_scorecard_itens')) {
            return;
        }

        $this->table('rh_entrevista_scorecard_itens', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('scorecard_id', 'integer', ['signed' => false])
            ->addColumn('criterio_codigo', 'string', ['limit' => 64])
            ->addColumn('criterio_label', 'string', ['limit' => 120])
            ->addColumn('peso', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('nota', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('comentario', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('ordem', 'integer', ['signed' => false, 'default' => 0])
            ->addIndex(['scorecard_id', 'ordem'], ['name' => 'idx_rh_scorecard_itens_ordem'])
            ->addForeignKey('scorecard_id', 'rh_entrevista_scorecards', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_rh_scorecard_item',
            ])
            ->create();
    }
}
