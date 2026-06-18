<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstRiscoExameRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, r.nome AS risco_nome, ex.nome AS exame_nome
                FROM adms_sst_risco_exame t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_exames ex ON ex.id = t.adms_sst_exame_id
                {$whereClause}
                ORDER BY r.nome, ex.nome
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
        $sql = "SELECT COUNT(*) AS total
                FROM adms_sst_risco_exame t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_exames ex ON ex.id = t.adms_sst_exame_id
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
        $sql = "SELECT t.*, r.nome AS risco_nome, ex.nome AS exame_nome
                FROM adms_sst_risco_exame t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_exames ex ON ex.id = t.adms_sst_exame_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_risco_exame
                (adms_sst_risco_id, adms_sst_exame_id, categoria_aso, periodicidade_meses, obrigatorio, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_sst_risco_id, :adms_sst_exame_id, :categoria_aso, :periodicidade_meses, :obrigatorio, :observacoes, :created_by, :updated_by, NOW(), NOW())";
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
                LogAlteracaoService::registrarAlteracao('adms_sst_risco_exame', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_risco_exame
                SET adms_sst_risco_id = :adms_sst_risco_id,
                    adms_sst_exame_id = :adms_sst_exame_id,
                    categoria_aso = :categoria_aso,
                    periodicidade_meses = :periodicidade_meses,
                    obrigatorio = :obrigatorio,
                    observacoes = :observacoes,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_risco_exame', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_risco_exame WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_risco_exame', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(r.nome LIKE :search OR ex.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_sst_risco_id'])) {
            $where[] = 't.adms_sst_risco_id = :risco_id';
            $params[':risco_id'] = (int) $filters['adms_sst_risco_id'];
        }
        if (!empty($filters['categoria_aso'])) {
            $where[] = '(t.categoria_aso IS NULL OR t.categoria_aso = :categoria_aso)';
            $params[':categoria_aso'] = $filters['categoria_aso'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':adms_sst_risco_id', $data['adms_sst_risco_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_exame_id', $data['adms_sst_exame_id'] ?? null);
        $this->bindField($stmt, ':categoria_aso', $data['categoria_aso'] ?? null);
        $this->bindField($stmt, ':periodicidade_meses', $data['periodicidade_meses'] ?? null);
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
