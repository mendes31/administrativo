<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstRiscoCargoMatch;
use PDO;

/**
 * Resolve treinamentos obrigatórios: Cargo → Riscos → Treinamentos + regras diretas (necessidade).
 */
class SstTreinamentosObrigatoriosResolver extends DbConnection
{
    /**
     * @return list<array{
     *   adms_sst_treinamento_id: int,
     *   treinamento_nome: string,
     *   validade_meses: int|null,
     *   origem: string
     * }>
     */
    public function resolveForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->deduplicateRows(array_merge(
            $this->fetchFromRiscoTreinamento($userId),
            $this->fetchFromTreinamentoNecessidade($userId),
            $this->fetchFromGheTreinamento($userId)
        ));
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromRiscoTreinamento(int $userId): array
    {
        if (!$this->hasTable('adms_sst_risco_treinamento')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(rt.validade_meses, tr.validade_meses) AS validade_meses,
                    'risco_treinamento' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo rc
                    ON " . SstRiscoCargoMatch::sqlUsuario('rc', 'u') . "
                INNER JOIN adms_sst_risco_treinamento rt
                    ON rt.adms_sst_risco_id = rc.adms_sst_risco_id AND rt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = rt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE u.id = :uid";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromTreinamentoNecessidade(int $userId): array
    {
        if (!$this->hasTable('adms_sst_treinamento_necessidade')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(n.validade_meses, tr.validade_meses) AS validade_meses,
                    'necessidade' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_treinamento_necessidade n
                    ON " . SstRiscoCargoMatch::sqlUsuario('n', 'u') . "
                   AND (
                        n.adms_sst_risco_id IS NULL
                        OR EXISTS (
                            SELECT 1 FROM adms_sst_riscos_cargo rc2
                            WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                              AND " . SstRiscoCargoMatch::sqlUsuario('rc2', 'u') . "
                        )
                   )
                INNER JOIN adms_sst_treinamentos tr ON tr.id = n.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE u.id = :uid
                  AND n.obrigatorio = 1";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromGheTreinamento(int $userId): array
    {
        if (!$this->hasTable('adms_sst_ghe_treinamentos') || !$this->hasTable('adms_sst_ghe_colaboradores')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(gt.validade_meses, tr.validade_meses) AS validade_meses,
                    'ghe' AS origem
                FROM adms_sst_ghe_colaboradores gc
                INNER JOIN adms_sst_ghe g ON g.id = gc.adms_sst_ghe_id AND g.status = 'Ativo'
                INNER JOIN adms_sst_ghe_treinamentos gt ON gt.adms_sst_ghe_id = g.id AND gt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = gt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE gc.adms_user_id = :uid
                  AND gc.data_fim IS NULL";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param list<array<string, mixed>> $rows */
    private function deduplicateRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $treinamentoId = (int) ($row['adms_sst_treinamento_id'] ?? 0);
            if ($treinamentoId <= 0) {
                continue;
            }
            if (!isset($out[$treinamentoId])) {
                $out[$treinamentoId] = [
                    'adms_sst_treinamento_id' => $treinamentoId,
                    'treinamento_nome' => (string) ($row['treinamento_nome'] ?? ''),
                    'validade_meses' => isset($row['validade_meses']) ? (int) $row['validade_meses'] : null,
                    'origem' => (string) ($row['origem'] ?? ''),
                ];
                continue;
            }
            if (empty($out[$treinamentoId]['validade_meses']) && !empty($row['validade_meses'])) {
                $out[$treinamentoId]['validade_meses'] = (int) $row['validade_meses'];
            }
        }

        return array_values($out);
    }

    /**
     * Treinamentos obrigatórios efetivos para um cargo (e departamento opcional).
     *
     * @return list<array{adms_sst_treinamento_id: int, treinamento_nome: string, validade_meses: int|null, origem: string}>
     */
    public function resolveForPosition(int $positionId, ?int $departmentId = null): array
    {
        if ($positionId <= 0) {
            return [];
        }

        return $this->deduplicateRows(array_merge(
            $this->resolveFromRiscoForPosition($positionId, $departmentId),
            $this->resolveDirectForPosition($positionId, $departmentId),
            $this->resolveFromGheForPosition($positionId, $departmentId)
        ));
    }

    /** @return list<array<string, mixed>> */
    public function resolveFromRiscoForPosition(int $positionId, ?int $departmentId = null): array
    {
        if (!$this->hasTable('adms_sst_risco_treinamento') || !$this->hasTable('adms_sst_riscos_cargo')) {
            return [];
        }

        $escopo = SstRiscoCargoMatch::sqlMatrizParaCargo('rc', 'p.id', $departmentId);
        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(rt.validade_meses, tr.validade_meses) AS validade_meses,
                    'risco_treinamento' AS origem
                FROM adms_positions p
                INNER JOIN adms_sst_riscos_cargo rc
                    ON {$escopo}
                INNER JOIN adms_sst_riscos r ON r.id = rc.adms_sst_risco_id AND r.status = 'Ativo'
                INNER JOIN adms_sst_risco_treinamento rt
                    ON rt.adms_sst_risco_id = rc.adms_sst_risco_id AND rt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = rt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE p.id = :pid";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pid', $positionId, PDO::PARAM_INT);
        if ($departmentId !== null && $departmentId > 0) {
            $stmt->bindValue(':dep', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Vínculos diretos na matriz (necessidade sem risco). */
    public function resolveDirectForPosition(int $positionId, ?int $departmentId = null): array
    {
        if (!$this->hasTable('adms_sst_treinamento_necessidade')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(n.validade_meses, tr.validade_meses) AS validade_meses,
                    'matriz_direta' AS origem
                FROM adms_sst_treinamento_necessidade n
                INNER JOIN adms_sst_treinamentos tr ON tr.id = n.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE n.adms_position_id = :pid
                  AND n.adms_sst_risco_id IS NULL
                  AND n.obrigatorio = 1";
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' AND n.adms_department_id = :dep';
        } else {
            $sql .= ' AND n.adms_department_id IS NULL';
        }

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pid', $positionId, PDO::PARAM_INT);
        if ($departmentId !== null && $departmentId > 0) {
            $stmt->bindValue(':dep', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Treinamentos via GHE de colaboradores ativos neste cargo (e dept, se informado). */
    public function resolveFromGheForPosition(int $positionId, ?int $departmentId = null): array
    {
        if (!$this->hasTable('adms_sst_ghe_treinamentos') || !$this->hasTable('adms_sst_ghe_colaboradores')) {
            return [];
        }

        $deptUser = '';
        if ($departmentId !== null && $departmentId > 0) {
            $deptUser = ' AND u.user_department_id = :dep';
        }

        $sql = "SELECT DISTINCT
                    tr.id AS adms_sst_treinamento_id,
                    tr.nome AS treinamento_nome,
                    COALESCE(gt.validade_meses, tr.validade_meses) AS validade_meses,
                    'ghe' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_ghe_colaboradores gc
                    ON gc.adms_user_id = u.id AND gc.data_fim IS NULL
                INNER JOIN adms_sst_ghe g ON g.id = gc.adms_sst_ghe_id AND g.status = 'Ativo'
                INNER JOIN adms_sst_ghe_treinamentos gt ON gt.adms_sst_ghe_id = g.id AND gt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = gt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE u.user_position_id = :pid
                  AND u.status = 'Ativo'
                  AND u.data_desligamento IS NULL
                  {$deptUser}";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pid', $positionId, PDO::PARAM_INT);
        if ($departmentId !== null && $departmentId > 0) {
            $stmt->bindValue(':dep', $departmentId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
