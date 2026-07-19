<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\RhEntrevistaScorecardCatalog;
use Exception;
use PDO;

class RhEntrevistaScorecardRepository extends DbConnection
{
    public function getByEntrevistaAndAvaliador(int $entrevistaId, int $avaliadorId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM rh_entrevista_scorecards
             WHERE rh_entrevista_id = :entrevista_id AND avaliador_id = :avaliador_id
             LIMIT 1'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['itens'] = $this->listItens((int) $row['id']);

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByEntrevista(int $entrevistaId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT s.*, u.name AS avaliador_nome
             FROM rh_entrevista_scorecards s
             LEFT JOIN adms_users u ON u.id = s.avaliador_id
             WHERE s.rh_entrevista_id = :entrevista_id
             ORDER BY s.id ASC'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['itens'] = $this->listItens((int) $row['id']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listItens(int $scorecardId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM rh_entrevista_scorecard_itens
             WHERE scorecard_id = :scorecard_id
             ORDER BY ordem ASC, id ASC'
        );
        $stmt->bindValue(':scorecard_id', $scorecardId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Upsert do scorecard do avaliador. Notas 0–10; pesos positivos.
     *
     * @param array{
     *   parecer?: string|null,
     *   finalizar?: bool,
     *   itens?: list<array{codigo?: string, nota?: int|string|null, comentario?: string|null}>
     * } $payload
     */
    public function saveForAvaliador(int $entrevistaId, int $avaliadorId, array $payload): int
    {
        if ($entrevistaId <= 0 || $avaliadorId <= 0) {
            throw new Exception('Entrevista ou avaliador inválido.');
        }

        $pdo = $this->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            $existing = $this->getByEntrevistaAndAvaliador($entrevistaId, $avaliadorId);
            $defaults = RhEntrevistaScorecardCatalog::defaultCriteria();
            $posted = [];
            foreach (($payload['itens'] ?? []) as $item) {
                $codigo = trim((string) ($item['codigo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }
                $posted[$codigo] = $item;
            }

            $itensNorm = [];
            foreach ($defaults as $i => $crit) {
                $codigo = $crit['codigo'];
                $post = $posted[$codigo] ?? [];
                $notaRaw = $post['nota'] ?? null;
                $nota = ($notaRaw === null || $notaRaw === '') ? null : (int) $notaRaw;
                if ($nota !== null && ($nota < 0 || $nota > 10)) {
                    throw new Exception("Nota inválida para {$crit['label']} (use 0 a 10).");
                }
                $itensNorm[] = [
                    'criterio_codigo' => $codigo,
                    'criterio_label' => $crit['label'],
                    'peso' => (int) $crit['peso'],
                    'nota' => $nota,
                    'comentario' => isset($post['comentario']) ? trim((string) $post['comentario']) : null,
                    'ordem' => ($i + 1) * 10,
                ];
            }

            $notaPonderada = RhEntrevistaScorecardCatalog::calcularNotaPonderada($itensNorm);
            $finalizar = !empty($payload['finalizar']);
            $parecer = trim((string) ($payload['parecer'] ?? ''));

            if ($existing) {
                $scorecardId = (int) $existing['id'];
                $sql = 'UPDATE rh_entrevista_scorecards
                        SET parecer = :parecer,
                            nota_ponderada = :nota_ponderada,
                            status = :status,
                            finalizado_at = CASE WHEN :finalizar = 1 THEN NOW() ELSE finalizado_at END,
                            updated_at = NOW()
                        WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':parecer', $parecer !== '' ? $parecer : null, $parecer !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':nota_ponderada', $notaPonderada, $notaPonderada !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':status', $finalizar ? 'finalizado' : 'rascunho', PDO::PARAM_STR);
                $stmt->bindValue(':finalizar', $finalizar ? 1 : 0, PDO::PARAM_INT);
                $stmt->bindValue(':id', $scorecardId, PDO::PARAM_INT);
                $stmt->execute();

                $del = $pdo->prepare('DELETE FROM rh_entrevista_scorecard_itens WHERE scorecard_id = :id');
                $del->bindValue(':id', $scorecardId, PDO::PARAM_INT);
                $del->execute();
            } else {
                $sql = 'INSERT INTO rh_entrevista_scorecards
                            (rh_entrevista_id, avaliador_id, status, parecer, nota_ponderada, finalizado_at, created_at)
                        VALUES
                            (:entrevista_id, :avaliador_id, :status, :parecer, :nota_ponderada,
                             CASE WHEN :finalizar = 1 THEN NOW() ELSE NULL END, NOW())';
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
                $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
                $stmt->bindValue(':status', $finalizar ? 'finalizado' : 'rascunho', PDO::PARAM_STR);
                $stmt->bindValue(':parecer', $parecer !== '' ? $parecer : null, $parecer !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':nota_ponderada', $notaPonderada, $notaPonderada !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt->bindValue(':finalizar', $finalizar ? 1 : 0, PDO::PARAM_INT);
                $stmt->execute();
                $scorecardId = (int) $pdo->lastInsertId();
            }

            $ins = $pdo->prepare(
                'INSERT INTO rh_entrevista_scorecard_itens
                    (scorecard_id, criterio_codigo, criterio_label, peso, nota, comentario, ordem)
                 VALUES
                    (:scorecard_id, :criterio_codigo, :criterio_label, :peso, :nota, :comentario, :ordem)'
            );
            foreach ($itensNorm as $item) {
                $ins->bindValue(':scorecard_id', $scorecardId, PDO::PARAM_INT);
                $ins->bindValue(':criterio_codigo', $item['criterio_codigo'], PDO::PARAM_STR);
                $ins->bindValue(':criterio_label', $item['criterio_label'], PDO::PARAM_STR);
                $ins->bindValue(':peso', $item['peso'], PDO::PARAM_INT);
                $ins->bindValue(':nota', $item['nota'], $item['nota'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $ins->bindValue(
                    ':comentario',
                    $item['comentario'] !== null && $item['comentario'] !== '' ? $item['comentario'] : null,
                    $item['comentario'] !== null && $item['comentario'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
                );
                $ins->bindValue(':ordem', $item['ordem'], PDO::PARAM_INT);
                $ins->execute();
            }

            if ($owns) {
                $pdo->commit();
            }

            return $scorecardId;
        } catch (Exception $e) {
            if ($owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Erro ao salvar scorecard de entrevista.', [
                'entrevista_id' => $entrevistaId,
                'avaliador_id' => $avaliadorId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Monta formulário inicial a partir do catálogo + scorecard existente.
     *
     * @return array{parecer: string, finalizar: bool, itens: list<array<string, mixed>>, nota_ponderada: float|null, status: string}
     */
    public function buildFormState(int $entrevistaId, int $avaliadorId): array
    {
        $existing = $this->getByEntrevistaAndAvaliador($entrevistaId, $avaliadorId);
        $byCode = [];
        if ($existing) {
            foreach ($existing['itens'] ?? [] as $item) {
                $byCode[$item['criterio_codigo']] = $item;
            }
        }

        $itens = [];
        foreach (RhEntrevistaScorecardCatalog::defaultCriteria() as $crit) {
            $prev = $byCode[$crit['codigo']] ?? null;
            $itens[] = [
                'codigo' => $crit['codigo'],
                'label' => $crit['label'],
                'peso' => $crit['peso'],
                'nota' => $prev['nota'] ?? '',
                'comentario' => $prev['comentario'] ?? '',
            ];
        }

        return [
            'parecer' => (string) ($existing['parecer'] ?? ''),
            'finalizar' => ($existing['status'] ?? '') === 'finalizado',
            'itens' => $itens,
            'nota_ponderada' => isset($existing['nota_ponderada']) ? (float) $existing['nota_ponderada'] : null,
            'status' => (string) ($existing['status'] ?? 'rascunho'),
        ];
    }
}
