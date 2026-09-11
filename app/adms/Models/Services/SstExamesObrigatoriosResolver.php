<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Helpers\SstRiscoCargoMatch;
use PDO;

/**
 * Resolve exames complementares por matriz: Cargo → Riscos → Exames + regras diretas.
 */
class SstExamesObrigatoriosResolver extends DbConnection
{
    /**
     * Exames obrigatórios (geram pacote ASO, pendências e encaminhamento).
     *
     * @return list<array<string, mixed>>
     */
    public function resolveForUser(int $userId, ?string $categoriaAso = null): array
    {
        return $this->resolveByObrigatoriedade($userId, $categoriaAso, true);
    }

    /**
     * Exames vinculados como recomendados (opcionais na matriz).
     *
     * @return list<array<string, mixed>>
     */
    public function resolveRecomendadosForUser(int $userId, ?string $categoriaAso = null): array
    {
        return $this->resolveByObrigatoriedade($userId, $categoriaAso, false);
    }

    /**
     * @return array{obrigatorios: list<array<string, mixed>>, recomendados: list<array<string, mixed>>}
     */
    public function resolvePacoteCompleto(int $userId, string $categoriaAso): array
    {
        if (!SstCategoriaAsoHelper::isValid($categoriaAso)) {
            return ['obrigatorios' => [], 'recomendados' => []];
        }

        return [
            'obrigatorios' => $this->resolveForUser($userId, $categoriaAso),
            'recomendados' => $this->resolveRecomendadosForUser($userId, $categoriaAso),
        ];
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
    private function resolveByObrigatoriedade(int $userId, ?string $categoriaAso, bool $obrigatorio): array
    {
        if ($userId <= 0) {
            return [];
        }

        $categoriaParam = ($categoriaAso !== null && $categoriaAso !== '') ? $categoriaAso : null;
        $rows = array_merge(
            $this->fetchFromRiscoExame($userId, $categoriaParam, $obrigatorio),
            $this->fetchFromExameNecessidade($userId, $categoriaParam, $obrigatorio)
        );

        return $this->deduplicateRows($rows, $obrigatorio);
    }

    /** @return list<array<string, mixed>> */
    private function fetchFromRiscoExame(int $userId, ?string $categoriaAso, bool $obrigatorio): array
    {
        if (!$this->hasTable('adms_sst_risco_exame')) {
            return [];
        }

        $categoriaSql = '';
        $params = [':uid' => $userId, ':obr' => $obrigatorio ? 1 : 0];
        if ($categoriaAso !== null) {
            $categoriaSql = ' AND (re.categoria_aso IS NULL OR re.categoria_aso = :categoria_aso)';
            $params[':categoria_aso'] = $categoriaAso;
        }

        $sql = "SELECT DISTINCT
                    ex.id AS adms_sst_exame_id,
                    ex.nome AS exame_nome,
                    re.categoria_aso,
                    COALESCE(re.periodicidade_meses, ex.periodicidade_meses) AS periodicidade_meses,
                    re.obrigatorio,
                    'risco_exame' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo rc
                    ON " . SstRiscoCargoMatch::sqlUsuario('rc', 'u') . "
                INNER JOIN adms_sst_risco_exame re
                    ON re.adms_sst_risco_id = rc.adms_sst_risco_id AND re.obrigatorio = :obr
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
    private function fetchFromExameNecessidade(int $userId, ?string $categoriaAso, bool $obrigatorio): array
    {
        $hasCategoriaCol = $this->tableHasColumn('adms_sst_exame_necessidade', 'categoria_aso');
        $categoriaSql = '';
        $params = [':uid' => $userId, ':obr' => $obrigatorio ? 1 : 0];
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
                    n.obrigatorio,
                    'necessidade' AS origem
                FROM adms_users u
                INNER JOIN adms_sst_exame_necessidade n
                    ON " . SstRiscoCargoMatch::sqlUsuario('n', 'u') . "
                   AND (
                        n.adms_sst_risco_id IS NULL
                        OR EXISTS (
                            SELECT 1 FROM adms_sst_riscos_cargo rc2
                            WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                              AND " . SstRiscoCargoMatch::sqlUsuario('rc2', 'u') . "
                        )
                   )
                INNER JOIN adms_sst_exames ex ON ex.id = n.adms_sst_exame_id AND ex.status = 'Ativo'
                WHERE u.id = :uid
                  AND n.obrigatorio = :obr
                  {$categoriaSql}";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param list<array<string, mixed>> $rows */
    private function deduplicateRows(array $rows, bool $obrigatorio): array
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
                    'obrigatorio' => $obrigatorio,
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
