<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstTreinamentoStatusService;
use PDO;

class SstTreinamentoVinculosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT v.*, u.name AS colaborador_nome, dep.name AS departamento_nome,
                       pos.name AS cargo_nome, tr.nome AS treinamento_nome, tr.codigo AS treinamento_codigo
                FROM adms_sst_treinamento_vinculos v
                INNER JOIN adms_users u ON u.id = v.adms_user_id
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                INNER JOIN adms_sst_treinamentos tr ON tr.id = v.adms_sst_treinamento_id
                {$whereClause}
                ORDER BY u.name, tr.nome
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
                FROM adms_sst_treinamento_vinculos v
                INNER JOIN adms_users u ON u.id = v.adms_user_id
                INNER JOIN adms_sst_treinamentos tr ON tr.id = v.adms_sst_treinamento_id
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
        $sql = "SELECT v.*, u.name AS colaborador_nome, dep.name AS departamento_nome,
                       pos.name AS cargo_nome, tr.nome AS treinamento_nome, tr.codigo AS treinamento_codigo,
                       tr.validade_meses AS treinamento_validade_meses, tr.modalidade AS treinamento_modalidade
                FROM adms_sst_treinamento_vinculos v
                INNER JOIN adms_users u ON u.id = v.adms_user_id
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                INNER JOIN adms_sst_treinamentos tr ON tr.id = v.adms_sst_treinamento_id
                WHERE v.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByUserAndTreinamento(int $userId, int $treinamentoId): ?array
    {
        $sql = 'SELECT v.* FROM adms_sst_treinamento_vinculos v
                WHERE v.adms_user_id = :uid AND v.adms_sst_treinamento_id = :tid LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tid', $treinamentoId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function calculateStatus(array $vinculo): string
    {
        return (new SstTreinamentoStatusService())->calculateStatus($vinculo);
    }

    public function create(array $data): int|false
    {
        if (empty($data['status'])) {
            $data['status'] = $this->calculateStatus($data);
        }
        $sql = 'INSERT INTO adms_sst_treinamento_vinculos (
                    adms_user_id, adms_sst_treinamento_id, status, motivo,
                    data_agendada, data_realizacao, data_validade, data_limite_primeiro,
                    nota, certificado, observacoes,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :adms_user_id, :adms_sst_treinamento_id, :status, :motivo,
                    :data_agendada, :data_realizacao, :data_validade, :data_limite_primeiro,
                    :nota, :certificado, :observacoes,
                    :created_by, :updated_by, NOW(), NOW()
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
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_vinculos', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        if (!isset($data['status'])) {
            $merged = array_merge($oldData ?? [], $data);
            $data['status'] = $this->calculateStatus($merged);
        }
        $sql = 'UPDATE adms_sst_treinamento_vinculos SET
                    adms_user_id = :adms_user_id,
                    adms_sst_treinamento_id = :adms_sst_treinamento_id,
                    status = :status,
                    motivo = :motivo,
                    data_agendada = :data_agendada,
                    data_realizacao = :data_realizacao,
                    data_validade = :data_validade,
                    data_limite_primeiro = :data_limite_primeiro,
                    nota = :nota,
                    certificado = :certificado,
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
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_vinculos', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_treinamento_vinculos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_treinamento_vinculos', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search OR tr.nome LIKE :search OR tr.codigo LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 'v.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['adms_sst_treinamento_id'])) {
            $where[] = 'v.adms_sst_treinamento_id = :adms_sst_treinamento_id';
            $params[':adms_sst_treinamento_id'] = (int) $filters['adms_sst_treinamento_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'v.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_treinamento_id', $data['adms_sst_treinamento_id'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? 'pendente');
        $this->bindField($stmt, ':motivo', $data['motivo'] ?? 'primeiro');
        $this->bindField($stmt, ':data_agendada', $data['data_agendada'] ?? null);
        $this->bindField($stmt, ':data_realizacao', $data['data_realizacao'] ?? null);
        $this->bindField($stmt, ':data_validade', $data['data_validade'] ?? null);
        $this->bindField($stmt, ':data_limite_primeiro', $data['data_limite_primeiro'] ?? null);
        $this->bindField($stmt, ':nota', $data['nota'] ?? null);
        $this->bindField($stmt, ':certificado', $data['certificado'] ?? null);
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
