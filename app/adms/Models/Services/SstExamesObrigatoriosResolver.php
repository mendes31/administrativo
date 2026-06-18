<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCategoriaAsoHelper;
use PDO;

/**
 * Resolve exames complementares obrigatórios: Cargo → Riscos → Exames + regras diretas.
 */
class SstExamesObrigatoriosResolver extends DbConnection
{
    /**
     * @return list<array{
     *   adms_sst_exame_id: int,
     *   exame_nome: string,
     *   categoria_aso: string|null,
     *   periodicidade_meses: int|null,
     *   origem: string
     * }>
     */
    public function resolveForUser(int $userId, ?string $categoriaAso = null): array
    {
        if ($userId <= 0) {
            return [];
        }

        $categoriaParam = ($categoriaAso !== null && $categoriaAso !== '') ? $categoriaAso : null;
        $rows = array_merge(
            $this->fetchFromRiscoExame($userId, $categoriaParam),
            $this->fetchFromExameNecessidade($userId, $categoriaParam)
        );

        return $this->deduplicateRows($rows);
    }

    /**
     * Pacote completo para um evento ASO (categoria): exames complementares esperados.
     *
     * @return list<array<string, mixed>>
     */
    public function resolvePacoteParaCategoria(int $userId, string $categoriaAso): array
    {
        if (!SstCategoriaAsoHelper::isValid($categoriaAso)) {
            return [];
        }

        return $this->resolveForUser($userId, $categoriaAso);
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromRiscoExame(int $userId, ?string $categoriaAso): array
    {
        if (!$this->hasTable('adms_sst_risco_exame')) {
            return [];
        }

        $categoriaSql = '';
        $params = [':uid' => $userId];
        if ($categoriaAso !== null) {
            $categoriaSql = ' AND (re.categoria_aso IS NULL OR re.categoria_aso = :categoria_aso)';
            $params[':categoria_aso'] = $categoriaAso;
        }

        $sql = "SELECT DISTINCT
                    ex.id AS adms_sst_exame_id,
                    ex.nome AS exame_nome,
                    re.categoria_aso,
                    COALESCE(re.periodicidade_meses, ex.periodicidade_meses) AS periodicidade_meses,
                    'risco_exame' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo rc
                    ON (rc.adms_position_id IS NULL OR rc.adms_position_id = u.user_position_id)
                   AND (rc.adms_department_id IS NULL OR rc.adms_department_id = u.user_department_id)
                INNER JOIN adms_sst_risco_exame re
                    ON re.adms_sst_risco_id = rc.adms_sst_risco_id AND re.obrigatorio = 1
                INNER JOIN adms_sst_exames ex ON ex.id = re.adms_sst_exame_id AND ex.status = 'Ativo'
                WHERE u.id = :uid
                  {$categoriaSql}";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromExameNecessidade(int $userId, ?string $categoriaAso): array
    {
        $hasCategoriaCol = $this->tableHasColumn('adms_sst_exame_necessidade', 'categoria_aso');
        $categoriaSql = '';
        $params = [':uid' => $userId];
        if ($categoriaAso !== null && $hasCategoriaCol) {
            $categoriaSql = ' AND (n.categoria_aso IS NULL OR n.categoria_aso = :categoria_aso)';
            $params[':categoria_aso'] = $categoriaAso;
        }

        $categoriaSelect = $hasCategoriaCol ? 'n.categoria_aso' : 'NULL AS categoria_aso';

        $sql = "SELECT DISTINCT
                    ex.id AS adms_sst_exame_id,
                    ex.nome AS exame_nome,
                    {$categoriaSelect},
                    COALESCE(n.periodicidade_meses, ex.periodicidade_meses) AS periodicidade_meses,
                    'necessidade' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_exame_necessidade n
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
                INNER JOIN adms_sst_exames ex ON ex.id = n.adms_sst_exame_id AND ex.status = 'Ativo'
                WHERE u.id = :uid
                  AND n.obrigatorio = 1
                  {$categoriaSql}";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param list<array<string, mixed>> $rows */
    private function deduplicateRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $exameId = (int) ($row['adms_sst_exame_id'] ?? 0);
            if ($exameId <= 0) {
                continue;
            }
            $cat = $row['categoria_aso'] ?? null;
            $key = $exameId . '|' . (string) ($cat ?? '*');
            if (!isset($out[$key])) {
                $out[$key] = [
                    'adms_sst_exame_id' => $exameId,
                    'exame_nome' => (string) ($row['exame_nome'] ?? ''),
                    'categoria_aso' => $cat !== null && $cat !== '' ? (string) $cat : null,
                    'periodicidade_meses' => isset($row['periodicidade_meses']) ? (int) $row['periodicidade_meses'] : null,
                    'origem' => (string) ($row['origem'] ?? ''),
                ];
                continue;
            }
            if (empty($out[$key]['periodicidade_meses']) && !empty($row['periodicidade_meses'])) {
                $out[$key]['periodicidade_meses'] = (int) $row['periodicidade_meses'];
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

    private function tableHasColumn(string $table, string $column): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->bindValue(':c', $column);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
