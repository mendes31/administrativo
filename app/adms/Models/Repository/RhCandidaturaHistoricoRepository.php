<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Histórico append-only de candidaturas (Fase 1 Expand).
 * Não possui update/delete de domínio — apenas inserção e leitura.
 */
class RhCandidaturaHistoricoRepository extends DbConnection
{
    public const TIPO_VINCULADA = 'vinculada';
    public const TIPO_MOVIMENTADA = 'movimentada';
    public const TIPO_DESVINCULADA = 'desvinculada';
    public const TIPO_BACKFILL = 'backfill';
    public const TIPO_OFERTA = 'oferta';

    public const ORIGEM_PIPELINE = 'pipeline';
    public const ORIGEM_VAGA = 'vaga';
    public const ORIGEM_CANDIDATO = 'candidato';
    public const ORIGEM_ENTREVISTA = 'entrevista';
    public const ORIGEM_SYNC = 'sync';
    public const ORIGEM_BACKFILL = 'backfill';
    public const ORIGEM_PORTAL = 'portal';
    public const ORIGEM_OFERTA = 'oferta';

    /**
     * @param array{
     *   rh_candidatura_id?: int|null,
     *   rh_candidato_id: int,
     *   rh_vaga_id?: int|null,
     *   tipo_evento: string,
     *   status_anterior?: string|null,
     *   status_novo?: string|null,
     *   origem: string,
     *   rh_entrevista_id?: int|null,
     *   motivo_codigo?: string|null,
     *   observacoes?: string|null,
     *   alterado_por?: int|null,
     *   correlation_id?: string|null
     * } $dados
     */
    public function registrar(array $dados): int
    {
        $candidatoId = (int) ($dados['rh_candidato_id'] ?? 0);
        $tipo = trim((string) ($dados['tipo_evento'] ?? ''));
        $origem = trim((string) ($dados['origem'] ?? ''));

        if ($candidatoId <= 0 || $tipo === '' || $origem === '') {
            throw new Exception('Dados obrigatórios do histórico de candidatura incompletos.');
        }

        $sql = 'INSERT INTO rh_candidaturas_historico
                    (rh_candidatura_id, rh_candidato_id, rh_vaga_id, tipo_evento,
                     status_anterior, status_novo, origem, rh_entrevista_id,
                     motivo_codigo, observacoes, alterado_por, correlation_id, ocorrido_em)
                VALUES
                    (:rh_candidatura_id, :rh_candidato_id, :rh_vaga_id, :tipo_evento,
                     :status_anterior, :status_novo, :origem, :rh_entrevista_id,
                     :motivo_codigo, :observacoes, :alterado_por, :correlation_id, NOW())';

        $stmt = $this->getConnection()->prepare($sql);
        $candidaturaId = isset($dados['rh_candidatura_id']) ? (int) $dados['rh_candidatura_id'] : null;
        $vagaId = isset($dados['rh_vaga_id']) ? (int) $dados['rh_vaga_id'] : null;
        $entrevistaId = isset($dados['rh_entrevista_id']) ? (int) $dados['rh_entrevista_id'] : null;
        $alteradoPor = array_key_exists('alterado_por', $dados)
            ? ($dados['alterado_por'] !== null ? (int) $dados['alterado_por'] : null)
            : $this->resolverAtorSessao();

        $stmt->bindValue(
            ':rh_candidatura_id',
            $candidaturaId !== null && $candidaturaId > 0 ? $candidaturaId : null,
            $candidaturaId !== null && $candidaturaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->bindValue(':rh_candidato_id', $candidatoId, PDO::PARAM_INT);
        $stmt->bindValue(
            ':rh_vaga_id',
            $vagaId !== null && $vagaId > 0 ? $vagaId : null,
            $vagaId !== null && $vagaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->bindValue(':tipo_evento', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(
            ':status_anterior',
            $dados['status_anterior'] ?? null,
            isset($dados['status_anterior']) && $dados['status_anterior'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':status_novo',
            $dados['status_novo'] ?? null,
            isset($dados['status_novo']) && $dados['status_novo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(':origem', $origem, PDO::PARAM_STR);
        $stmt->bindValue(
            ':rh_entrevista_id',
            $entrevistaId !== null && $entrevistaId > 0 ? $entrevistaId : null,
            $entrevistaId !== null && $entrevistaId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':motivo_codigo',
            $dados['motivo_codigo'] ?? null,
            isset($dados['motivo_codigo']) && $dados['motivo_codigo'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':observacoes',
            $dados['observacoes'] ?? null,
            isset($dados['observacoes']) && $dados['observacoes'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':alterado_por',
            $alteradoPor,
            $alteradoPor !== null ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->bindValue(
            ':correlation_id',
            $dados['correlation_id'] ?? null,
            isset($dados['correlation_id']) && $dados['correlation_id'] !== null ? PDO::PARAM_STR : PDO::PARAM_NULL
        );

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception($errorInfo[2] ?? 'Falha ao gravar histórico de candidatura.');
        }

        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByCandidato(int $candidatoId, int $limit = 100): array
    {
        if ($candidatoId <= 0) {
            return [];
        }

        $limit = max(1, min(500, $limit));

        $sql = "SELECT h.*,
                       v.titulo AS vaga_titulo,
                       u.name AS alterado_por_nome
                FROM rh_candidaturas_historico h
                LEFT JOIN rh_vagas v ON v.id = h.rh_vaga_id
                LEFT JOIN adms_users u ON u.id = h.alterado_por
                WHERE h.rh_candidato_id = :candidato_id
                ORDER BY h.ocorrido_em DESC, h.id DESC
                LIMIT {$limit}";

        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao listar histórico de candidatura.', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function resolverAtorSessao(): ?int
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return (int) $_SESSION['user_id'];
    }
}
