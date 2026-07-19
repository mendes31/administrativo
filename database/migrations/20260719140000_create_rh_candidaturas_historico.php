<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 1 Expand — histórico imutável de candidatura (append-only).
 * Mantém rh_candidatos_vagas.status como projeção mutável (dual-write).
 */
final class CreateRhCandidaturasHistorico extends AbstractMigration
{
    public function up(): void
    {
        $this->createHistoricoTable();
        $this->backfillFromVinculos();
    }

    public function down(): void
    {
        if ($this->hasTable('rh_candidaturas_historico')) {
            $this->table('rh_candidaturas_historico')->drop()->save();
        }
    }

    private function createHistoricoTable(): void
    {
        if ($this->hasTable('rh_candidaturas_historico')) {
            return;
        }

        $table = $this->table('rh_candidaturas_historico', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_candidatura_id', 'integer', [
                'signed' => false,
                'null' => true,
                'comment' => 'ID de rh_candidatos_vagas no momento do evento (sem FK: desvínculo é DELETE físico)',
            ])
            ->addColumn('rh_candidato_id', 'integer', ['signed' => false])
            ->addColumn('rh_vaga_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('tipo_evento', 'string', [
                'limit' => 32,
                'comment' => 'vinculada|movimentada|desvinculada|backfill',
            ])
            ->addColumn('status_anterior', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('status_novo', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('origem', 'string', [
                'limit' => 32,
                'comment' => 'pipeline|vaga|candidato|entrevista|sync|backfill',
            ])
            ->addColumn('rh_entrevista_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('motivo_codigo', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('alterado_por', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('correlation_id', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('ocorrido_em', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['rh_candidatura_id', 'ocorrido_em'], ['name' => 'idx_rh_cand_hist_candidatura_em'])
            ->addIndex(['rh_candidato_id', 'ocorrido_em'], ['name' => 'idx_rh_cand_hist_candidato_em'])
            ->addIndex(['rh_vaga_id', 'ocorrido_em'], ['name' => 'idx_rh_cand_hist_vaga_em'])
            ->addIndex(['alterado_por'], ['name' => 'idx_rh_cand_hist_ator'])
            ->addIndex(['correlation_id'], ['name' => 'idx_rh_cand_hist_correlation'])
            ->addForeignKey(
                'rh_candidato_id',
                'rh_candidatos',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_cand_hist_candidato',
                ]
            )
            ->addForeignKey(
                'rh_vaga_id',
                'rh_vagas',
                'id',
                [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_cand_hist_vaga',
                ]
            )
            ->addForeignKey(
                'rh_entrevista_id',
                'rh_entrevistas',
                'id',
                [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_cand_hist_entrevista',
                ]
            )
            ->addForeignKey(
                'alterado_por',
                'adms_users',
                'id',
                [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rh_cand_hist_ator',
                ]
            )
            ->create();
    }

    private function backfillFromVinculos(): void
    {
        if (!$this->hasTable('rh_candidaturas_historico') || !$this->hasTable('rh_candidatos_vagas')) {
            return;
        }

        $this->execute(
            "INSERT INTO rh_candidaturas_historico (
                rh_candidatura_id, rh_candidato_id, rh_vaga_id, tipo_evento,
                status_anterior, status_novo, origem, observacoes, ocorrido_em
            )
            SELECT
                cv.id,
                cv.rh_candidato_id,
                cv.rh_vaga_id,
                'backfill',
                NULL,
                cv.status,
                'backfill',
                'Estado atual capturado na implantação do histórico (data aproximada).',
                COALESCE(cv.data_ultima_atualizacao, cv.data_candidatura, cv.created_at, NOW())
            FROM rh_candidatos_vagas cv
            WHERE NOT EXISTS (
                SELECT 1
                FROM rh_candidaturas_historico h
                WHERE h.rh_candidatura_id = cv.id
                  AND h.tipo_evento = 'backfill'
            )"
        );
    }
}
