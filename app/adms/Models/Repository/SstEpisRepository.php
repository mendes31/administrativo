<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEpisRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*
                FROM adms_sst_epis t
                {$whereClause}
                ORDER BY t.nome ASC, t.id DESC
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epis t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.* FROM adms_sst_epis t WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByUserId(int $userId, int $limit = 50): array
    {
        return [];
    }

    public function create(array $data): int|false
    {
        $cols = ['nome', 'descricao', 'estoque_atual', 'estoque_minimo', 'periodicidade_troca_dias', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $vals = [':nome', ':descricao', '0', ':estoque_minimo', ':periodicidade_troca_dias', ':status', ':created_by', ':updated_by', 'NOW()', 'NOW()'];
        if ($this->hasColumn('categoria')) {
            array_splice($cols, 2, 0, ['categoria']);
            array_splice($vals, 2, 0, [':categoria']);
        }

        $sql = 'INSERT INTO adms_sst_epis (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindField($stmt, ':nome', $data['nome'] ?? null);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        if ($this->hasColumn('categoria')) {
            $this->bindField($stmt, ':categoria', $data['categoria'] ?? null);
        }
        $this->bindField($stmt, ':estoque_minimo', $data['estoque_minimo'] ?? null);
        $this->bindField($stmt, ':periodicidade_troca_dias', $data['periodicidade_troca_dias'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? null);
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
                LogAlteracaoService::registrarAlteracao('adms_sst_epis', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sets = [
            'nome = :nome',
            'descricao = :descricao',
            'estoque_minimo = :estoque_minimo',
            'periodicidade_troca_dias = :periodicidade_troca_dias',
            'status = :status',
            'updated_by = :updated_by',
            'updated_at = NOW()',
        ];
        if ($this->hasColumn('categoria')) {
            $sets[] = 'categoria = :categoria';
        }

        $sql = 'UPDATE adms_sst_epis SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindField($stmt, ':nome', $data['nome'] ?? null);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        if ($this->hasColumn('categoria')) {
            $this->bindField($stmt, ':categoria', $data['categoria'] ?? null);
        }
        $this->bindField($stmt, ':estoque_minimo', $data['estoque_minimo'] ?? null);
        $this->bindField($stmt, ':periodicidade_troca_dias', $data['periodicidade_troca_dias'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_epis', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_epis WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_epis', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    public function updateEstoqueAtual(int $id, int $saldo): bool
    {
        $sql = 'UPDATE adms_sst_epis SET estoque_atual = :saldo, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':saldo', max(0, $saldo), PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.nome LIKE :search OR t.descricao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['categoria']) && $this->hasColumn('categoria')) {
            $where[] = 't.categoria = :categoria';
            $params[':categoria'] = $filters['categoria'];
        }
        if (!empty($filters['adms_user_id'])) {
            $where[] = 't.adms_user_id = :adms_user_id';
            $params[':adms_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['estoque_baixo'])) {
            $where[] = 't.estoque_minimo > 0 AND t.estoque_atual <= t.estoque_minimo';
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $stmt = $this->getConnection()->query('SHOW COLUMNS FROM adms_sst_epis LIKE ' . $this->getConnection()->quote($column));
            $cache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            $cache[$column] = false;
        }

        return $cache[$column];
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
