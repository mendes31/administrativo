<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstRiscoCargoMatch;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstPendenciasService;
use PDO;

class SstRiscoCargoRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, p.name AS cargo_nome, d.name AS departamento_nome, r.nome AS risco_nome
                FROM adms_sst_riscos_cargo t
            LEFT JOIN adms_positions p ON p.id = t.adms_position_id
            LEFT JOIN adms_departments d ON d.id = t.adms_department_id LEFT JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_riscos_cargo t
            LEFT JOIN adms_positions p ON p.id = t.adms_position_id
            LEFT JOIN adms_departments d ON d.id = t.adms_department_id LEFT JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, p.name AS cargo_nome, d.name AS departamento_nome, r.nome AS risco_nome FROM adms_sst_riscos_cargo t
            LEFT JOIN adms_positions p ON p.id = t.adms_position_id
            LEFT JOIN adms_departments d ON d.id = t.adms_department_id LEFT JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByUserId(int $userId, int $limit = 50): array
    {
        $sql = "SELECT t.*, p.name AS cargo_nome, d.name AS departamento_nome, r.nome AS risco_nome, r.tipo AS risco_tipo
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo t ON " . SstRiscoCargoMatch::sqlUsuario('t', 'u') . "
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                LEFT JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                WHERE u.id = :uid
                ORDER BY r.nome
                LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $data = $this->normalizeScope($data);
        if (!SstRiscoCargoMatch::hasScope($data['adms_position_id'], $data['adms_department_id'])) {
            return false;
        }
        $sql = "INSERT INTO adms_sst_riscos_cargo (adms_position_id, adms_department_id, adms_sst_risco_id, nivel, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_position_id, :adms_department_id, :adms_sst_risco_id, :nivel, :observacoes, :created_by, :updated_by, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindField($stmt, ':adms_position_id', $data['adms_position_id'] ?? null);
        $this->bindField($stmt, ':adms_department_id', $data['adms_department_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_risco_id', $data['adms_sst_risco_id'] ?? null);
        $this->bindField($stmt, ':nivel', $data['nivel'] ?? null);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
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
                LogAlteracaoService::registrarAlteracao('adms_sst_riscos_cargo', $newId, $uid, 'INSERT', [], $newData);
            }
        }
        if ($newId > 0) {
            SstPendenciasService::invalidateDashboardCache();
        }
        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->normalizeScope($data);
        if (!SstRiscoCargoMatch::hasScope($data['adms_position_id'], $data['adms_department_id'])) {
            return false;
        }
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_riscos_cargo SET adms_position_id = :adms_position_id, adms_department_id = :adms_department_id, adms_sst_risco_id = :adms_sst_risco_id, nivel = :nivel, observacoes = :observacoes, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindField($stmt, ':adms_position_id', $data['adms_position_id'] ?? null);
        $this->bindField($stmt, ':adms_department_id', $data['adms_department_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_risco_id', $data['adms_sst_risco_id'] ?? null);
        $this->bindField($stmt, ':nivel', $data['nivel'] ?? null);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_riscos_cargo', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }
        if ($ok) {
            SstPendenciasService::invalidateDashboardCache();
        }
        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = "DELETE FROM adms_sst_riscos_cargo WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_riscos_cargo', $id, $uid, 'DELETE', $oldData, []);
        }
        if ($deleted) {
            SstPendenciasService::invalidateDashboardCache();
        }
        return $deleted;
    }

    /** @return list<array<string, mixed>> */
    public function getAllByRisco(int $riscoId): array
    {
        if ($riscoId <= 0) {
            return [];
        }

        return $this->getAll(1, 500, ['adms_sst_risco_id' => $riscoId]);
    }

    /**
     * Cargos com colaborador ativo no departamento (para vínculo só de setor).
     *
     * @return list<array{id: int, name: string}>
     */
    public function getCargosAtivosNoDepartamento(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $sql = "SELECT DISTINCT p.id, p.name
                FROM adms_users u
                INNER JOIN adms_positions p ON p.id = u.user_position_id
                WHERE u.user_department_id = :dep
                  AND u.status = 'Ativo'
                  AND u.data_desligamento IS NULL
                ORDER BY p.name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dep', $departmentId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR d.name LIKE :search OR r.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 't.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['adms_sst_risco_id'])) {
            $where[] = 't.adms_sst_risco_id = :risco_id';
            $params[':risco_id'] = (int) $filters['adms_sst_risco_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereClause, $params];
    }

    /** @param array<string, mixed> $data */
    private function normalizeScope(array $data): array
    {
        $data['adms_position_id'] = SstRiscoCargoMatch::normalizeId($data['adms_position_id'] ?? null);
        $data['adms_department_id'] = SstRiscoCargoMatch::normalizeId($data['adms_department_id'] ?? null);

        return $data;
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