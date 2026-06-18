<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstRiscosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*
                FROM adms_sst_riscos t
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
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn (array $row) => $this->hydrateRow($row), $rows);
    }

    public function getTotal(array $filters = []): int
    {
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_riscos t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.* FROM adms_sst_riscos t WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrateRow($row) : null;
    }

    public function existsCodigo(string $codigo, ?int $excludeId = null): bool
    {
        if ($codigo === '' || !$this->hasColumn('codigo')) {
            return false;
        }

        $sql = 'SELECT id FROM adms_sst_riscos WHERE codigo = :codigo';
        $params = [':codigo' => strtoupper($codigo)];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_riscos (
                    codigo, nome, descricao, grupo_risco, tipo,
                    necessita_monitoramento_medico, necessita_epi,
                    status, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :codigo, :nome, :descricao, :grupo_risco, :tipo,
                    :necessita_monitoramento_medico, :necessita_epi,
                    :status, :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindCatalogFields($stmt, $data);
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
                LogAlteracaoService::registrarAlteracao('adms_sst_riscos', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = 'UPDATE adms_sst_riscos SET
                    codigo = :codigo,
                    nome = :nome,
                    descricao = :descricao,
                    grupo_risco = :grupo_risco,
                    tipo = :tipo,
                    necessita_monitoramento_medico = :necessita_monitoramento_medico,
                    necessita_epi = :necessita_epi,
                    status = :status,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindCatalogFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_riscos', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_riscos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_riscos', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** @return array<string, mixed> */
    private function hydrateRow(array $row): array
    {
        if (empty($row['grupo_risco']) && !empty($row['tipo'])) {
            $row['grupo_risco'] = $row['tipo'];
        }
        $row['necessita_monitoramento_medico'] = !empty($row['necessita_monitoramento_medico']);
        $row['necessita_epi'] = !empty($row['necessita_epi']);

        return $row;
    }

    /** @param array<string, mixed> $data */
    private function bindCatalogFields(\PDOStatement $stmt, array $data): void
    {
        $grupo = $data['grupo_risco'] ?? $data['tipo'] ?? null;
        $this->bindField($stmt, ':codigo', $data['codigo'] ?? null);
        $this->bindField($stmt, ':nome', $data['nome'] ?? null);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':grupo_risco', $grupo);
        $this->bindField($stmt, ':tipo', $grupo);
        $this->bindField($stmt, ':necessita_monitoramento_medico', !empty($data['necessita_monitoramento_medico']));
        $this->bindField($stmt, ':necessita_epi', !empty($data['necessita_epi']));
        $this->bindField($stmt, ':status', $data['status'] ?? 'Ativo');
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.nome LIKE :search OR t.codigo LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['grupo_risco']) && $this->hasColumn('grupo_risco')) {
            $where[] = 't.grupo_risco = :grupo_risco';
            $params[':grupo_risco'] = $filters['grupo_risco'];
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function hasColumn(string $column): bool
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            if (!$this->hasTable('adms_sst_riscos')) {
                return false;
            }
            $stmt = $this->getConnection()->query('SHOW COLUMNS FROM adms_sst_riscos');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $col) {
                $cache[$col['Field']] = true;
            }
        }

        return !empty($cache[$column]);
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
