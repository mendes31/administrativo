<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Painel de avaliadores da entrevista (Expand: convite/aceite).
 */
class RhEntrevistaAvaliadoresRepository extends DbConnection
{
    public const PAPEL_PRINCIPAL = 'principal';
    public const PAPEL_AVALIADOR = 'avaliador';
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_REMOVIDO = 'removido';
    public const STATUS_CONVIDADO = 'convidado';
    public const STATUS_RECUSADO = 'recusado';

    /**
     * @return list<array<string, mixed>>
     */
    public function listAtivosByEntrevista(int $entrevistaId): array
    {
        return $this->listByEntrevista($entrevistaId, true);
    }

    /**
     * Painel completo com status do scorecard quando existir.
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
                    CASE a.status
                        WHEN \'ativo\' THEN 0
                        WHEN \'convidado\' THEN 1
                        WHEN \'recusado\' THEN 2
                        ELSE 3
                    END,
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
     * IDs dos adicionais ainda no painel (ativo/convidado/recusado) — para o multi-select.
     *
     * @return list<int>
     */
    public function listIdsAdicionaisSelecionados(int $entrevistaId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT avaliador_id FROM rh_entrevista_avaliadores
             WHERE rh_entrevista_id = :entrevista_id
               AND papel = :papel
               AND status IN (\'ativo\', \'convidado\', \'recusado\')'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $stmt->execute();

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Compat: só ativos (scorecard / legado).
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

    public function getByEntrevistaAndAvaliador(int $entrevistaId, int $avaliadorId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM rh_entrevista_avaliadores
             WHERE rh_entrevista_id = :entrevista_id AND avaliador_id = :avaliador_id
             LIMIT 1'
        );
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Sincroniza o painel. Retorna IDs de avaliadores que precisam de convite (novos/reconvites).
     *
     * @param list<int|string> $avaliadoresAdicionaisIds
     * @return list<int>
     */
    public function syncPainel(
        int $entrevistaId,
        ?int $entrevistadorPrincipalId,
        array $avaliadoresAdicionaisIds,
        int $actorId
    ): array {
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
        $toInvite = [];

        $pdo = $this->getConnection();
        $owns = !$pdo->inTransaction();
        if ($owns) {
            $pdo->beginTransaction();
        }

        try {
            if ($entrevistadorPrincipalId !== null && $entrevistadorPrincipalId > 0) {
                $this->upsertPrincipal($entrevistaId, $entrevistadorPrincipalId, $actorId);
            }

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
                if ($this->upsertAdicional($entrevistaId, $avaliadorId, $actorId)) {
                    $toInvite[] = $avaliadorId;
                }
            }

            $stmtExtras = $pdo->prepare(
                'SELECT id, avaliador_id FROM rh_entrevista_avaliadores
                 WHERE rh_entrevista_id = :entrevista_id
                   AND papel = :papel
                   AND status IN (\'ativo\', \'convidado\', \'recusado\')'
            );
            $stmtExtras->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $stmtExtras->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
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

        return array_values(array_unique($toInvite));
    }

    public function aceitarConvite(int $entrevistaId, int $avaliadorId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE rh_entrevista_avaliadores
             SET status = :ativo, respondido_at = NOW(), updated_at = NOW()
             WHERE rh_entrevista_id = :entrevista_id
               AND avaliador_id = :avaliador_id
               AND status = :convidado
               AND papel = :papel'
        );
        $stmt->bindValue(':ativo', self::STATUS_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->bindValue(':convidado', self::STATUS_CONVIDADO, PDO::PARAM_STR);
        $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function recusarConvite(int $entrevistaId, int $avaliadorId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE rh_entrevista_avaliadores
             SET status = :recusado, respondido_at = NOW(), updated_at = NOW()
             WHERE rh_entrevista_id = :entrevista_id
               AND avaliador_id = :avaliador_id
               AND status = :convidado
               AND papel = :papel'
        );
        $stmt->bindValue(':recusado', self::STATUS_RECUSADO, PDO::PARAM_STR);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->bindValue(':convidado', self::STATUS_CONVIDADO, PDO::PARAM_STR);
        $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Reabre convite (convidado) e marca convidado_at. Retorna true se atualizou.
     */
    public function marcarComoConvidado(int $entrevistaId, int $avaliadorId): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE rh_entrevista_avaliadores
             SET status = :convidado,
                 convidado_at = NOW(),
                 respondido_at = NULL,
                 removido_at = NULL,
                 updated_at = NOW()
             WHERE rh_entrevista_id = :entrevista_id
               AND avaliador_id = :avaliador_id
               AND papel = :papel
               AND status IN (\'convidado\', \'recusado\', \'removido\')'
        );
        $stmt->bindValue(':convidado', self::STATUS_CONVIDADO, PDO::PARAM_STR);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function upsertPrincipal(int $entrevistaId, int $avaliadorId, int $actorId): void
    {
        $pdo = $this->getConnection();
        $sql = 'INSERT INTO rh_entrevista_avaliadores
                    (rh_entrevista_id, avaliador_id, papel, status, designado_por, designado_at, created_at)
                VALUES
                    (:entrevista_id, :avaliador_id, :papel, :status, :designado_por, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    papel = VALUES(papel),
                    status = :status_upd,
                    designado_por = COALESCE(VALUES(designado_por), designado_por),
                    designado_at = COALESCE(designado_at, NOW()),
                    removido_at = NULL,
                    updated_at = NOW()';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
        $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
        $stmt->bindValue(':papel', self::PAPEL_PRINCIPAL, PDO::PARAM_STR);
        $stmt->bindValue(':status', self::STATUS_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(':status_upd', self::STATUS_ATIVO, PDO::PARAM_STR);
        $stmt->bindValue(
            ':designado_por',
            $actorId > 0 ? $actorId : null,
            $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $stmt->execute();
    }

    /**
     * @return bool true se precisa enviar/reenviar convite
     */
    private function upsertAdicional(int $entrevistaId, int $avaliadorId, int $actorId): bool
    {
        $existing = $this->getByEntrevistaAndAvaliador($entrevistaId, $avaliadorId);
        $pdo = $this->getConnection();

        if ($existing === null) {
            $sql = 'INSERT INTO rh_entrevista_avaliadores
                        (rh_entrevista_id, avaliador_id, papel, status, designado_por, designado_at,
                         convidado_at, created_at)
                    VALUES
                        (:entrevista_id, :avaliador_id, :papel, :status, :designado_por, NOW(),
                         NOW(), NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':entrevista_id', $entrevistaId, PDO::PARAM_INT);
            $stmt->bindValue(':avaliador_id', $avaliadorId, PDO::PARAM_INT);
            $stmt->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
            $stmt->bindValue(':status', self::STATUS_CONVIDADO, PDO::PARAM_STR);
            $stmt->bindValue(
                ':designado_por',
                $actorId > 0 ? $actorId : null,
                $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
            );
            $stmt->execute();

            return true;
        }

        $status = (string) ($existing['status'] ?? '');
        if ($status === self::STATUS_ATIVO || $status === self::STATUS_CONVIDADO) {
            $upd = $pdo->prepare(
                'UPDATE rh_entrevista_avaliadores
                 SET papel = :papel, removido_at = NULL, updated_at = NOW()
                 WHERE id = :id'
            );
            $upd->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
            $upd->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
            $upd->execute();

            return $status === self::STATUS_CONVIDADO && empty($existing['convidado_at']);
        }

        // removido / recusado → reconvidar
        $upd = $pdo->prepare(
            'UPDATE rh_entrevista_avaliadores
             SET papel = :papel,
                 status = :status,
                 designado_por = COALESCE(:designado_por, designado_por),
                 designado_at = NOW(),
                 convidado_at = NOW(),
                 respondido_at = NULL,
                 removido_at = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $upd->bindValue(':papel', self::PAPEL_AVALIADOR, PDO::PARAM_STR);
        $upd->bindValue(':status', self::STATUS_CONVIDADO, PDO::PARAM_STR);
        $upd->bindValue(
            ':designado_por',
            $actorId > 0 ? $actorId : null,
            $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL
        );
        $upd->bindValue(':id', (int) $existing['id'], PDO::PARAM_INT);
        $upd->execute();

        return true;
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
