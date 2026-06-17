<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstAfastamentosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, u.name AS colaborador_nome
                FROM adms_sst_afastamentos t LEFT JOIN adms_users u ON u.id = t.adms_user_id
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_afastamentos t LEFT JOIN adms_users u ON u.id = t.adms_user_id {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name AS colaborador_nome FROM adms_sst_afastamentos t LEFT JOIN adms_users u ON u.id = t.adms_user_id WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByUserId(int $userId, int $limit = 50): array
    {
        if (!in_array('adms_user_id', ['adms_user_id', 'adms_sst_cid_id', 'adms_sst_medico_id', 'tipo', 'data_inicio', 'data_fim', 'dias_afastamento', 'data_retorno', 'status', 'observacoes'], true)) {
            return [];
        }
        $sql = "SELECT t.*, u.name AS colaborador_nome FROM adms_sst_afastamentos t LEFT JOIN adms_users u ON u.id = t.adms_user_id WHERE t.adms_user_id = :uid ORDER BY t.id DESC LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_afastamentos (adms_user_id, adms_sst_cid_id, adms_sst_medico_id, tipo, data_inicio, data_fim, dias_afastamento, data_retorno, status, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_user_id, :adms_sst_cid_id, :adms_sst_medico_id, :tipo, :data_inicio, :data_fim, :dias_afastamento, :data_retorno, :status, :observacoes, :created_by, :updated_by, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_cid_id', $data['adms_sst_cid_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_medico_id', $data['adms_sst_medico_id'] ?? null);
        $this->bindField($stmt, ':tipo', $data['tipo'] ?? null);
        $this->bindField($stmt, ':data_inicio', $data['data_inicio'] ?? null);
        $this->bindField($stmt, ':data_fim', $data['data_fim'] ?? null);
        $this->bindField($stmt, ':dias_afastamento', $data['dias_afastamento'] ?? null);
        $this->bindField($stmt, ':data_retorno', $data['data_retorno'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? null);
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
                LogAlteracaoService::registrarAlteracao('adms_sst_afastamentos', $newId, $uid, 'INSERT', [], $newData);
            }
        }
        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_afastamentos SET adms_user_id = :adms_user_id, adms_sst_cid_id = :adms_sst_cid_id, adms_sst_medico_id = :adms_sst_medico_id, tipo = :tipo, data_inicio = :data_inicio, data_fim = :data_fim, dias_afastamento = :dias_afastamento, data_retorno = :data_retorno, status = :status, observacoes = :observacoes, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_cid_id', $data['adms_sst_cid_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_medico_id', $data['adms_sst_medico_id'] ?? null);
        $this->bindField($stmt, ':tipo', $data['tipo'] ?? null);
        $this->bindField($stmt, ':data_inicio', $data['data_inicio'] ?? null);
        $this->bindField($stmt, ':data_fim', $data['data_fim'] ?? null);
        $this->bindField($stmt, ':dias_afastamento', $data['dias_afastamento'] ?? null);
        $this->bindField($stmt, ':data_retorno', $data['data_retorno'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? null);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_afastamentos', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }
        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = "DELETE FROM adms_sst_afastamentos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_afastamentos', $id, $uid, 'DELETE', $oldData, []);
        }
        return $deleted;
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search OR t.id LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 't.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereClause, $params];
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