<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Painel interno de avaliadores da entrevista (Expand Fase 2).
 * Não concede ACL e não dispara comunicação.
 */
class RhEntrevistaAvaliadoresRepository extends DbConnection
{
    public const PAPEL_PRINCIPAL = 'principal';
    public const PAPEL_AVALIADOR = 'avaliador';
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_REMOVIDO = 'removido';

    /**
     * @return list<array<string, mixed>>
     */
    public function listAtivosByEntrevista(int $entrevistaId): array
    {
        return $this->listByEntrevista($entrevistaId, true);
    }

    /**
     * Painel completo (ativos + removidos) com status do scorecard quando existir.
     *
     * @return list<array<string, mixed>>
     */
    public function listPainelByEntrevista(int $entrevistaId): array
    {
        $sql = 'SELECT a.*,
                       u.name AS avaliador_nome,
                       d.name AS designado_por_nome,
                       s.id AS scorecard_id,
                       s.status AS scorecard_status,
                       s.nota_ponderada AS scorecard_nota
                FROM rh_entrevista_avaliadores a
                LEFT JOIN adms_users u ON u.id = a.avaliador_id
                LEFT JOIN adms_users d ON d.id = a.designado_por
                LEFT JOIN rh_entrevista_scorecards s
                       ON s.rh_entrevista_id = a.rh_entrevista_id
                      AND s.avaliador_id = a.avaliador_id
                WHERE a.rh_entrevista_id = :entrevista_id
                ORDER BY
                    CASE a.papel WHEN \'principal\' THEN 0 ELSE 1 END,
                    CASE a.status WHEN \'ativo\' THEN 0 ELSE 1 END,
                    u.name ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByEntrevista(int $entrevistaId, bool $apenasAtivos = false): array
    {
        $sql = 'SELECT a.*, u.name AS avaliador_nome
                FROM rh_entrevista_avaliadores a
                LEFT JOIN adms_users u ON u.id = a.avaliador_id
                WHERE a.rh_entrevista_id = :entrevista_id';
        if ($apenasAtivos) {
            $sql .= ' AND a.status = :status_ativo';
        }
        $sql .= ' ORDER BY CASE a.papel WHEN \'principal\' THEN 0 ELSE 1 END, u.name ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        if ($apenasAtivos) {
            $stmt->bindValue(':status_ativo', self::STATUS_ATIVO, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * IDs dos avaliadores adicionais ativos (exclui o principal).
     *
     * @return list<int>
     */
    public function listIdsAdicionaisAtivos(int $entrevistaId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT avaliador_id FROM rh_entrevista_avaliadores
             WHERE rh_entrevista_id = :entrevista_id
               AND status = :status
               AND papel = :papel'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Sincroniza o painel a partir do entrevistador legado + IDs adicionais.
     * Remoção é lógica (status=removido). Não envia notificação.
     *
     * @param list<int|string> $avaliadoresAdicionaisIds
     */
    public function syncPainel(
        int $entrevistaId,
        ?int $entrevistadorPrincipalId,
        array $avaliadoresAdicionaisIds,
        int $actorId
    ): void {
        if ($entrevistaId <= 0) {
            throw new Exception('Entrevista inválida.');
        }

        $adicionais = [];
        foreach ($avaliadoresAdicionaisIds as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            if ($entrevistadorPrincipalId !== null && $id === $entrevistadorPrincipalId) {
                continue;
            }
            $adicionais[$id] = $id;
        }
        $adicionais = array_values($adicionais);

        $pdo = $this->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            if ($entrevistadorPrincipalId !== null && $entrevistadorPrincipalId > 0) {
                $this->upsertAvaliador(
                    $entrevistaId,
                    $entrevistadorPrincipalId,
                    self::PAPEL_PRINCIPAL,
                    $actorId
                );
            }

            // Principais antigos que não são mais o entrevistador → rebaixar/remover.
            $stmtOld = $pdo->prepare(
                'SELECT id, avaliador_id FROM rh_entrevista_avaliadores
                 WHERE rh_entrevista_id = :entrevista_id AND papel = :papel AND status = :status'
            );
            $stmtOld->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $stmtOld->bindValue(':papel', self::PAPEL_PRINCIPAL, PDO::PARAM_STR);
            $stmtOld->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
            $stmtOld->execute();
            foreach ($stmtOld->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $uid = (int) $row['avaliador_id'];
                if ($entrevistadorPrincipalId !== null && $uid === $entrevistadorPrincipalId) {
                    continue;
                }
                // Se ainda está na lista de adicionais, vira avaliador; senão remove.
                if (in_array($uid, $adicionais, true)) {
                    $upd = $pdo->prepare(
                        'UPDATE rh_entrevista_avaliadores
                         SET papel = :papel, status = :status, removido_at = NULL, updated_at = NOW()
                         WHERE id = :id'
                    );
                    $upd->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
                    $upd->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
                    $upd->bindValue(':id', (int) $row['id'], PDO::PARAM_INT);
                    $upd->execute();
                } else {
                    $this->markRemoved((int) $row['id']);
                }
            }

            foreach ($adicionais as $avaliadorId) {
                $this->upsertAvaliador(
                    $entrevistaId,
                    $avaliadorId,
                    self::PAPEL_AVALIADOR,
                    $actorId
                );
            }

            // Remover adicionais ativos que saíram da seleção.
            $stmtExtras = $pdo->prepare(
                'SELECT id, avaliador_id FROM rh_entrevista_avaliadores
                 WHERE rh_entrevista_id = :entrevista_id
                   AND papel = :papel
                   AND status = :status'
            );
            $stmtExtras->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $stmtExtras->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
            $stmtExtras->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
            $stmtExtras->execute();
            foreach ($stmtExtras->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                if (!in_array((int) $row['avaliador_id'], $adicionais, true)) {
                    $this->markRemoved((int) $row['id']);
                }
            }

            if ($owns) {
                $pdo->commit();
            }
        } catch (Exception $e) {
            if ($owns && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            GenerateLog::generateLog('error', 'Erro ao sincronizar painel de avaliadores.', [
                'entrevista_id' => $entrevistaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function upsertAvaliador(int $entrevistaId, int $avaliadorId, string $papel, int $actorId): void
    {
        $pdo = $this->getConnection();
        $sql = 'INSERT INTO rh_entrevista_avaliadores
                    (rh_entrevista_id, avaliador_id, papel, status, designado_por, designado_at, created_at)
                VALUES
                    (:entrevista_id, :avaliador_id, :papel, :status, :designado_por, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    papel = VALUES(papel),
                    status = VALUES(status),
                    designado_por = COALESCE(VALUES(designado_por), designado_por),
                    designado_at = CASE
                        WHEN status = \'removido\' THEN NOW()
                        ELSE COALESCE(designado_at, NOW())
                    END,
                    removido_at = NULL,
                    updated_at = NOW()';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->bindValue(':papel', $papel, PDO::PARAM_STR);
        $stmt->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(
            ':designado_por',
            $actorId > 0 ? $actorId : null,
            $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->execute();
    }

    private function markRemoved(int $id): void
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE rh_entrevista_avaliadores
             SET status = :status, removido_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->bindValue(':status', self::STATUS_REMOVIDO, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
