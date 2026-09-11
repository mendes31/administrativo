<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstRiscoCargoMatch;
use PDO;

/**
 * Resolve EPIs obrigatórios: Cargo → Riscos → EPIs + regras diretas (necessidade).
 */
class SstEpisObrigatoriosResolver extends DbConnection
{
    /**
     * @return list<array{
     *   adms_sst_epi_id: int,
     *   epi_nome: string,
     *   origem: string
     * }>
     */
    public function resolveForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return $this->deduplicateRows(array_merge(
            $this->fetchFromRiscoEpi($userId),
            $this->fetchFromEpiNecessidade($userId)
        ));
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromRiscoEpi(int $userId): array
    {
        if (!$this->hasTable('adms_sst_risco_epi')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    ep.id AS adms_sst_epi_id,
                    ep.nome AS epi_nome,
                    'risco_epi' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo rc
                    ON " . SstRiscoCargoMatch::sqlUsuario('rc', 'u') . "
                INNER JOIN adms_sst_risco_epi re
                    ON re.adms_sst_risco_id = rc.adms_sst_risco_id AND re.obrigatorio = 1
                INNER JOIN adms_sst_epis ep ON ep.id = re.adms_sst_epi_id AND ep.status = 'Ativo'
                WHERE u.id = :uid";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromEpiNecessidade(int $userId): array
    {
        if (!$this->hasTable('adms_sst_epi_necessidade')) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    ep.id AS adms_sst_epi_id,
                    ep.nome AS epi_nome,
                    'necessidade' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_epi_necessidade n
                    ON " . SstRiscoCargoMatch::sqlUsuario('n', 'u') . "
                   AND (
                        n.adms_sst_risco_id IS NULL
                        OR EXISTS (
                            SELECT 1 FROM adms_sst_riscos_cargo rc2
                            WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                              AND " . SstRiscoCargoMatch::sqlUsuario('rc2', 'u') . "
                        )
                   )
                INNER JOIN adms_sst_epis ep ON ep.id = n.adms_sst_epi_id AND ep.status = 'Ativo'
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
            $epiId = (int) ($row['adms_sst_epi_id'] ?? 0);
            if ($epiId <= 0 || isset($out[$epiId])) {
                continue;
            }
            $out[$epiId] = [
                'adms_sst_epi_id' => $epiId,
                'epi_nome' => (string) ($row['epi_nome'] ?? ''),
                'origem' => (string) ($row['origem'] ?? ''),
            ];
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
