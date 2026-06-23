<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstGheRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT g.*, d.name AS departamento_nome,
                       (SELECT COUNT(*) FROM adms_sst_ghe_colaboradores gc
                        WHERE gc.adms_sst_ghe_id = g.id AND gc.data_fim IS NULL) AS total_colaboradores
                FROM adms_sst_ghe g
                LEFT JOIN adms_departments d ON d.id = g.adms_department_id
                {$whereClause}
                ORDER BY g.nome ASC, g.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_ghe g {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT g.*, d.name AS departamento_nome
                FROM adms_sst_ghe g
                LEFT JOIN adms_departments d ON d.id = g.adms_department_id
                WHERE g.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** GHE ativo do colaborador (data_fim nula). */
    public function getAtivoByUserId(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        $sql = "SELECT g.*, gc.data_inicio, gc.id AS vinculo_colaborador_id
                FROM adms_sst_ghe_colaboradores gc
                INNER JOIN adms_sst_ghe g ON g.id = gc.adms_sst_ghe_id AND g.status = 'Ativo'
                WHERE gc.adms_user_id = :uid AND gc.data_fim IS NULL
                ORDER BY gc.data_inicio DESC, gc.id DESC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'INSERT INTO adms_sst_ghe (
                    codigo, nome, descricao, ambiente_local, adms_department_id, status,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :codigo, :nome, :descricao, :ambiente_local, :adms_department_id, :status,
                    :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':codigo', $data['codigo'] ?? null);
        $stmt->bindValue(':nome', (string) ($data['nome'] ?? ''));
        $stmt->bindValue(':descricao', $data['descricao'] ?? null);
        $stmt->bindValue(':ambiente_local', $data['ambiente_local'] ?? null);
        $stmt->bindValue(':adms_department_id', !empty($data['adms_department_id']) ? (int) $data['adms_department_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_ghe', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $sql = 'UPDATE adms_sst_ghe SET
                    codigo = :codigo, nome = :nome, descricao = :descricao,
                    ambiente_local = :ambiente_local, adms_department_id = :adms_department_id,
                    status = :status, updated_by = :updated_by, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':codigo', $data['codigo'] ?? null);
        $stmt->bindValue(':nome', (string) ($data['nome'] ?? ''));
        $stmt->bindValue(':descricao', $data['descricao'] ?? null);
        $stmt->bindValue(':ambiente_local', $data['ambiente_local'] ?? null);
        $stmt->bindValue(':adms_department_id', !empty($data['adms_department_id']) ? (int) $data['adms_department_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_ghe', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        if (!$oldData) {
            return false;
        }
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_ghe WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok) {
            LogAlteracaoService::registrarAlteracao('adms_sst_ghe', $id, $uid, 'DELETE', $oldData, []);
        }

        return $ok;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(g.nome LIKE :search OR g.codigo LIKE :search OR g.ambiente_local LIKE :search)';
            $params[':search'] = '%' . trim((string) $filters['search']) . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 'g.status = :status';
            $params[':status'] = (string) $filters['status'];
        }
        if (!empty($filters['adms_department_id'])) {
            $where[] = 'g.adms_department_id = :dep';
            $params[':dep'] = (int) $filters['adms_department_id'];
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}
