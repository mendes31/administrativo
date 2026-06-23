<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

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
            $this->fetchFromTreinamentoNecessidade($userId)
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
                    ON (rc.adms_position_id IS NULL OR rc.adms_position_id = u.user_position_id)
                   AND (rc.adms_department_id IS NULL OR rc.adms_department_id = u.user_department_id)
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
                    ON (n.adms_position_id IS NULL OR n.adms_position_id = u.user_position_id)
                   AND (n.adms_department_id IS NULL OR n.adms_department_id = u.user_department_id)
                   AND (
                        n.adms_sst_risco_id IS NULL
                        OR EXISTS (
                            SELECT 1 FROM adms_sst_riscos_cargo rc2
                            WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                              AND (rc2.adms_position_id IS NULL OR rc2.adms_position_id = u.user_position_id)
                              AND (rc2.adms_department_id IS NULL OR rc2.adms_department_id = u.user_department_id)
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
