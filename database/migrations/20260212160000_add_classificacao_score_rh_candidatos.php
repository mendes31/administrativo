<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adiciona campo de classificação/score para ajudar na seleção de candidatos.
 * O score pode ser um número de 0 a 100 ou uma classificação textual (ex: Excelente, Bom, Regular).
 */
final class AddClassificacaoScoreRhCandidatos extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('rh_candidatos')) {
            return;
        }

        $table = $this->table('rh_candidatos');

        // Score numérico (0-100)
        if (!$table->hasColumn('score')) {
            $table->addColumn('score', 'integer', [
                'null'  => true,
                'default' => null,
                'comment' => 'Score numérico de 0 a 100 para classificação do candidato',
                'after' => 'ultima_experiencia',
            ]);
        }

        // Classificação textual (opcional, complementa o score)
        if (!$table->hasColumn('classificacao')) {
            $table->addColumn('classificacao', 'string', [
                'limit' => 50,
                'null'  => true,
                'default' => null,
                'comment' => 'Classificação textual (ex: Excelente, Bom, Regular, Baixo)',
                'after' => 'score',
            ]);
        }

        // Observações sobre a classificação
        if (!$table->hasColumn('classificacao_observacoes')) {
            $table->addColumn('classificacao_observacoes', 'text', [
                'null'  => true,
                'default' => null,
                'comment' => 'Observações sobre a classificação/score do candidato',
                'after' => 'classificacao',
            ]);
        }

        // Índice para ordenação rápida por score
        if (!$table->hasIndex('score')) {
            $table->addIndex(['score'], ['name' => 'idx_score']);
        }

        $table->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('rh_candidatos')) {
            return;
        }

        $table = $this->table('rh_candidatos');

        if ($table->hasColumn('classificacao_observacoes')) {
            $table->removeColumn('classificacao_observacoes');
        }
        if ($table->hasColumn('classificacao')) {
            $table->removeColumn('classificacao');
        }
        if ($table->hasColumn('score')) {
            $table->removeColumn('score');
        }

        $table->update();
    }
}

