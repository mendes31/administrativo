<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstProgramasRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, u.name AS responsavel_nome, m.nome AS medico_nome,
                       p.name AS cargo_nome, d.name AS departamento_nome
                FROM adms_sst_programas t
                LEFT JOIN adms_users u ON u.id = t.responsavel_adms_user_id
                LEFT JOIN adms_sst_medicos m ON m.id = t.adms_sst_medico_id
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                {$whereClause}
                ORDER BY t.vigencia_inicio DESC, t.id DESC
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_programas t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name AS responsavel_nome, m.nome AS medico_nome,
                       p.name AS cargo_nome, d.name AS departamento_nome
                FROM adms_sst_programas t
                LEFT JOIN adms_users u ON u.id = t.responsavel_adms_user_id
                LEFT JOIN adms_sst_medicos m ON m.id = t.adms_sst_medico_id
                LEFT JOIN adms_positions p ON p.id = t.adms_position_id
                LEFT JOIN adms_departments d ON d.id = t.adms_department_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getVigentesOuAVencer(int $diasAlerta = 60): array
    {
        $sql = "SELECT t.*, u.name AS responsavel_nome
                FROM adms_sst_programas t
                LEFT JOIN adms_users u ON u.id = t.responsavel_adms_user_id
                WHERE t.status = 'Vigente'
                  AND (
                    t.vigencia_fim IS NULL
                    OR t.vigencia_fim <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                  )
                ORDER BY t.vigencia_fim IS NULL, t.vigencia_fim ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dias', $diasAlerta, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countSemVigentePorTipo(string $tipo): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_programas
                WHERE tipo = :tipo AND status = 'Vigente'
                  AND vigencia_inicio <= CURDATE()
                  AND (vigencia_fim IS NULL OR vigencia_fim >= CURDATE())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_programas
                (tipo, titulo, descricao, versao, vigencia_inicio, vigencia_fim, responsavel_adms_user_id,
                 adms_sst_medico_id, adms_position_id, adms_department_id, status, observacoes,
                 created_by, updated_by, created_at, updated_at)
                VALUES (:tipo, :titulo, :descricao, :versao, :vigencia_inicio, :vigencia_fim, :responsavel_adms_user_id,
                        :adms_sst_medico_id, :adms_position_id, :adms_department_id, :status, :observacoes,
                        :created_by, :updated_by, NOW(), NOW())";
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
                LogAlteracaoService::registrarAlteracao('adms_sst_programas', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_programas SET
                    tipo = :tipo, titulo = :titulo, descricao = :descricao, versao = :versao,
                    vigencia_inicio = :vigencia_inicio, vigencia_fim = :vigencia_fim,
                    responsavel_adms_user_id = :responsavel_adms_user_id, adms_sst_medico_id = :adms_sst_medico_id,
                    adms_position_id = :adms_position_id, adms_department_id = :adms_department_id,
                    status = :status, observacoes = :observacoes, updated_by = :updated_by, updated_at = NOW()
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
                LogAlteracaoService::registrarAlteracao('adms_sst_programas', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_programas WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;

        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_programas', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(t.titulo LIKE :search OR t.descricao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['tipo'])) {
            $where[] = 't.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (($filters['vigencia'] ?? '') === 'vencendo') {
            $where[] = "t.status = 'Vigente' AND t.vigencia_fim IS NOT NULL AND t.vigencia_fim <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
        } elseif (($filters['vigencia'] ?? '') === 'vencido') {
            $where[] = 't.vigencia_fim IS NOT NULL AND t.vigencia_fim < CURDATE()';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'PGR'));
        $stmt->bindValue(':titulo', (string) ($data['titulo'] ?? ''));
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':versao', $data['versao'] ?? null);
        $stmt->bindValue(':vigencia_inicio', (string) ($data['vigencia_inicio'] ?? date('Y-m-d')));
        $this->bindField($stmt, ':vigencia_fim', $data['vigencia_fim'] ?? null);
        $this->bindField($stmt, ':responsavel_adms_user_id', $data['responsavel_adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_medico_id', $data['adms_sst_medico_id'] ?? null);
        $this->bindField($stmt, ':adms_position_id', $data['adms_position_id'] ?? null);
        $this->bindField($stmt, ':adms_department_id', $data['adms_department_id'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Rascunho'));
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
    }

    private function bindField(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
            return;
        }
        $stmt->bindValue($param, (string) $value);
    }
}
