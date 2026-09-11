<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstRiscoCargoMatch;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstTreinamentoNecessidadeRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, p.name AS cargo_nome, d.name AS departamento_nome, tr.nome AS treinamento_nome
                FROM adms_sst_treinamento_necessidade t
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_sst_treinamentos tr ON tr.id = t.adms_sst_treinamento_id
                {$whereClause}
                ORDER BY t.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_treinamento_necessidade t
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_sst_treinamentos tr ON tr.id = t.adms_sst_treinamento_id
                {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, p.name AS cargo_nome, d.name AS departamento_nome, tr.nome AS treinamento_nome
                FROM adms_sst_treinamento_necessidade t
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_sst_treinamentos tr ON tr.id = t.adms_sst_treinamento_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_treinamento_necessidade (
                    adms_position_id, adms_department_id, adms_sst_risco_id, adms_sst_treinamento_id,
                    validade_meses, obrigatorio, observacoes, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :adms_position_id, :adms_department_id, :adms_sst_risco_id, :adms_sst_treinamento_id,
                    :validade_meses, :obrigatorio, :observacoes, :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_necessidade', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = 'UPDATE adms_sst_treinamento_necessidade SET
                    adms_position_id = :adms_position_id,
                    adms_department_id = :adms_department_id,
                    adms_sst_risco_id = :adms_sst_risco_id,
                    adms_sst_treinamento_id = :adms_sst_treinamento_id,
                    validade_meses = :validade_meses,
                    obrigatorio = :obrigatorio,
                    observacoes = :observacoes,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_necessidade', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_treinamento_necessidade WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_necessidade', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** Regras de matriz cargo (sem risco): position + dept opcional. */
    public function getMatrizByPosition(int $positionId, ?int $departmentId = null): array
    {
        if ($positionId <= 0) {
            return [];
        }
        $sql = 'SELECT t.*, tr.nome AS treinamento_nome, tr.validade_meses AS treinamento_validade_meses
                FROM adms_sst_treinamento_necessidade t
                INNER JOIN adms_sst_treinamentos tr ON tr.id = t.adms_sst_treinamento_id
                WHERE t.adms_position_id = :pid AND t.adms_sst_risco_id IS NULL';
        $params = [':pid' => $positionId];
        if ($departmentId !== null && $departmentId > 0) {
            $sql .= ' AND t.adms_department_id = :dep';
            $params[':dep'] = $departmentId;
        } else {
            $sql .= ' AND t.adms_department_id IS NULL';
        }
        $sql .= ' ORDER BY tr.nome';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return list<array<string, mixed>> */
    public function getResumoMatrizPorCargo(): array
    {
        if (!$this->hasTable('adms_sst_treinamento_necessidade')) {
            return [];
        }

        $hasRisco = $this->hasTable('adms_sst_risco_treinamento') && $this->hasTable('adms_sst_riscos_cargo');
        $hasGhe = $this->hasTable('adms_sst_ghe_treinamentos') && $this->hasTable('adms_sst_ghe_colaboradores');

        $riscoSub = $hasRisco ? "
            UNION
            SELECT p2.id AS position_id, rt.adms_sst_treinamento_id AS tid, 'risco' AS origem
            FROM adms_positions p2
            INNER JOIN adms_sst_riscos_cargo rc
                ON " . SstRiscoCargoMatch::sqlMatrizParaCargo('rc', 'p2.id', null) . "
            INNER JOIN adms_sst_riscos r ON r.id = rc.adms_sst_risco_id AND r.status = 'Ativo'
            INNER JOIN adms_sst_risco_treinamento rt
                ON rt.adms_sst_risco_id = rc.adms_sst_risco_id AND rt.obrigatorio = 1
            INNER JOIN adms_sst_treinamentos tr ON tr.id = rt.adms_sst_treinamento_id AND tr.status = 'Ativo'
        " : '';

        $gheSub = $hasGhe ? "
            UNION
            SELECT u.user_position_id AS position_id, gt.adms_sst_treinamento_id AS tid, 'ghe' AS origem
            FROM adms_users u
            INNER JOIN adms_sst_ghe_colaboradores gc ON gc.adms_user_id = u.id AND gc.data_fim IS NULL
            INNER JOIN adms_sst_ghe g ON g.id = gc.adms_sst_ghe_id AND g.status = 'Ativo'
            INNER JOIN adms_sst_ghe_treinamentos gt ON gt.adms_sst_ghe_id = g.id AND gt.obrigatorio = 1
            INNER JOIN adms_sst_treinamentos tr ON tr.id = gt.adms_sst_treinamento_id AND tr.status = 'Ativo'
            WHERE u.user_position_id IS NOT NULL
              AND u.status = 'Ativo'
              AND u.data_desligamento IS NULL
        " : '';

        $sql = "SELECT p.id, p.name AS cargo_nome,
                       COALESCE(e.total_efetivo, 0) AS total_efetivo,
                       COALESCE(e.total_diretos, 0) AS total_diretos,
                       COALESCE(e.total_via_risco, 0) AS total_via_risco,
                       COALESCE(e.total_via_ghe, 0) AS total_via_ghe
                FROM adms_positions p
                LEFT JOIN (
                    SELECT position_id,
                           COUNT(DISTINCT tid) AS total_efetivo,
                           COUNT(DISTINCT CASE WHEN origem = 'direto' THEN tid END) AS total_diretos,
                           COUNT(DISTINCT CASE WHEN origem = 'risco' THEN tid END) AS total_via_risco,
                           COUNT(DISTINCT CASE WHEN origem = 'ghe' THEN tid END) AS total_via_ghe
                    FROM (
                        SELECT n.adms_position_id AS position_id, n.adms_sst_treinamento_id AS tid, 'direto' AS origem
                        FROM adms_sst_treinamento_necessidade n
                        WHERE n.adms_position_id IS NOT NULL
                          AND n.adms_sst_risco_id IS NULL
                          AND n.obrigatorio = 1
                        {$riscoSub}
                        {$gheSub}
                    ) efetivos
                    GROUP BY position_id
                ) e ON e.position_id = p.id
                ORDER BY COALESCE(e.total_efetivo, 0) DESC, p.name";

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
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

    /** @return array{0: string, 1: array<string, mixed>} */
    public function syncMatrizForPosition(int $positionId, ?int $departmentId, array $treinamentoIds): void
    {
        if ($positionId <= 0) {
            return;
        }
        $treinamentoIds = array_values(array_unique(array_filter(array_map('intval', $treinamentoIds), static fn(int $id): bool => $id > 0)));
        $dep = ($departmentId !== null && $departmentId > 0) ? $departmentId : null;

        $atuais = $this->getMatrizByPosition($positionId, $dep);
        $atuaisPorTreinamento = [];
        foreach ($atuais as $row) {
            $atuaisPorTreinamento[(int) ($row['adms_sst_treinamento_id'] ?? 0)] = $row;
        }

        foreach ($atuaisPorTreinamento as $treinamentoId => $row) {
            if (!in_array($treinamentoId, $treinamentoIds, true)) {
                $this->delete((int) ($row['id'] ?? 0));
            }
        }

        $treinamentosRepo = new SstTreinamentosRepository();
        foreach ($treinamentoIds as $treinamentoId) {
            if (isset($atuaisPorTreinamento[$treinamentoId])) {
                continue;
            }
            $treinamento = $treinamentosRepo->getById($treinamentoId);
            if (!$treinamento || ($treinamento['status'] ?? '') !== 'Ativo') {
                continue;
            }
            $validade = $treinamento['validade_meses'] ?? null;
            if ($validade !== null) {
                $validade = (int) $validade;
                if ($validade === 0) {
                    $validade = null;
                }
            }
            $this->create([
                'adms_position_id' => $positionId,
                'adms_department_id' => $dep,
                'adms_sst_risco_id' => null,
                'adms_sst_treinamento_id' => $treinamentoId,
                'validade_meses' => $validade,
                'obrigatorio' => true,
                'observacoes' => 'Matriz cargo × treinamento',
            ]);
        }

        \App\adms\Models\Services\SstPendenciasService::invalidateDashboardCache();
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR d.name LIKE :search OR tr.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':adms_position_id', $data['adms_position_id'] ?? null);
        $this->bindField($stmt, ':adms_department_id', $data['adms_department_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_risco_id', $data['adms_sst_risco_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_treinamento_id', $data['adms_sst_treinamento_id'] ?? null);
        $this->bindField($stmt, ':validade_meses', $data['validade_meses'] ?? null);
        $this->bindField($stmt, ':obrigatorio', $data['obrigatorio'] ?? true);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
    }

    private function bindField(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);

            return;
        }
        if (is_bool($value)) {
            $stmt->bindValue($param, $value ? 1 : 0, PDO::PARAM_INT);

            return;
        }
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);

            return;
        }
        $stmt->bindValue($param, (string) $value, PDO::PARAM_STR);
    }
}
