<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

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
