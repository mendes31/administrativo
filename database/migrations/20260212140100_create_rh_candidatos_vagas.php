<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cria a tabela rh_candidatos_vagas para relacionamento N:N
 * entre candidatos e vagas (um candidato pode se candidatar a várias vagas,
 * uma vaga pode ter vários candidatos).
 */
final class CreateRhCandidatosVagas extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('rh_candidatos_vagas')) {
            return;
        }

        $this->table('rh_candidatos_vagas')
            ->addColumn('rh_candidato_id', 'integer', [
                'null'   => false,
                'signed' => false,
            ])
            ->addColumn('rh_vaga_id', 'integer', [
                'null'   => false,
                'signed' => false,
            ])
            ->addColumn('status', 'string', [
                'limit'   => 30,
                'null'    => false,
                'default' => 'candidatado',
                'comment' => 'candidatado, em_analise, aprovado, reprovado, desistiu',
            ])
            ->addColumn('data_candidatura', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('data_ultima_atualizacao', 'datetime', [
                'null'    => true,
                'default' => null,
            ])
            ->addColumn('observacoes', 'text', [
                'null' => true,
            ])
            ->addColumn('score_compatibilidade', 'integer', [
                'null'    => true,
                'default' => null,
                'comment' => 'Score de compatibilidade (0-100) - para auto-matching futuro',
            ])
            ->addColumn('created_at', 'datetime', [
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null'    => true,
                'default' => null,
            ])

            // Índices
            ->addIndex(['rh_candidato_id'])
            ->addIndex(['rh_vaga_id'])
            ->addIndex(['status'])
            ->addIndex(['data_candidatura'])

            // Índice único para evitar duplicatas
            ->addIndex(['rh_candidato_id', 'rh_vaga_id'], [
                'unique' => true,
                'name'   => 'idx_candidato_vaga_unique',
            ])

            // Foreign Keys
            ->addForeignKey('rh_candidato_id', 'rh_candidatos', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('rh_vaga_id', 'rh_vagas', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidatos_vagas')) {
            $this->table('rh_candidatos_vagas')->drop()->save();
        }
    }
}

