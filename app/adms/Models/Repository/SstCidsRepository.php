<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstCidCapituloHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstCidsRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*
                FROM adms_sst_cids t
                {$whereClause}
                ORDER BY t.frequente DESC, t.codigo ASC
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_cids t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT t.* FROM adms_sst_cids t WHERE t.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Busca para Select2 / autocomplete.
     *
     * @return list<array{id: int, text: string, codigo: string, descricao: string}>
     */
    public function searchForSelect(string $term, int $limit = 30, bool $frequentesOnly = false): array
    {
        $limit = max(5, min(50, $limit));
        $where = ['t.status = :status'];
        $params = [':status' => 'Ativo'];

        if ($frequentesOnly) {
            $where[] = 't.frequente = 1';
        }

        if ($term !== '') {
            $where[] = '(t.codigo LIKE :term OR t.descricao LIKE :term OR t.categoria LIKE :term)';
            $params[':term'] = '%' . $term . '%';
        }

        $sql = 'SELECT t.id, t.codigo, t.descricao, t.frequente
                FROM adms_sst_cids t
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY t.frequente DESC, t.codigo ASC
                LIMIT ' . $limit;

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): array {
            $codigo = (string) ($row['codigo'] ?? '');
            $descricao = (string) ($row['descricao'] ?? '');

            return [
                'id' => (int) $row['id'],
                'text' => $codigo . ' — ' . $descricao,
                'codigo' => $codigo,
                'descricao' => $descricao,
            ];
        }, $rows);
    }

    public function create(array $data): int|false
    {
        $cap = SstCidCapituloHelper::resolveFromCodigo((string) ($data['codigo'] ?? ''));
        $sql = 'INSERT INTO adms_sst_cids (codigo, descricao, capitulo_num, capitulo_nome, categoria, frequente, status, created_by, updated_by, created_at, updated_at)
                VALUES (:codigo, :descricao, :capitulo_num, :capitulo_nome, :categoria, :frequente, :status, :created_by, :updated_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $codigo = (string) ($data['codigo'] ?? '');
        $this->bindField($stmt, ':codigo', $codigo);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':capitulo_num', $data['capitulo_num'] ?? $cap['num'] ?? null);
        $this->bindField($stmt, ':capitulo_nome', $data['capitulo_nome'] ?? $cap['nome'] ?? null);
        $this->bindField($stmt, ':categoria', $data['categoria'] ?? SstCidCapituloHelper::categoriaFromCodigo($codigo));
        $this->bindField($stmt, ':frequente', !empty($data['frequente']) ? 1 : 0);
        $this->bindField($stmt, ':status', $data['status'] ?? 'Ativo');
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
                LogAlteracaoService::registrarAlteracao('adms_sst_cids', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $codigo = (string) ($data['codigo'] ?? $oldData['codigo'] ?? '');
        $cap = SstCidCapituloHelper::resolveFromCodigo($codigo);
        $sql = 'UPDATE adms_sst_cids SET codigo = :codigo, descricao = :descricao, capitulo_num = :capitulo_num,
                capitulo_nome = :capitulo_nome, categoria = :categoria, frequente = :frequente, status = :status,
                updated_by = :updated_by, updated_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindField($stmt, ':codigo', $codigo);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':capitulo_num', $data['capitulo_num'] ?? $cap['num'] ?? null);
        $this->bindField($stmt, ':capitulo_nome', $data['capitulo_nome'] ?? $cap['nome'] ?? null);
        $this->bindField($stmt, ':categoria', $data['categoria'] ?? SstCidCapituloHelper::categoriaFromCodigo($codigo));
        $this->bindField($stmt, ':frequente', !empty($data['frequente']) ? 1 : 0);
        $this->bindField($stmt, ':status', $data['status'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_cids', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_cids WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_cids', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.codigo LIKE :search OR t.descricao LIKE :search OR t.capitulo_nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['capitulo_num'])) {
            $where[] = 't.capitulo_num = :capitulo_num';
            $params[':capitulo_num'] = (int) $filters['capitulo_num'];
        }
        if (isset($filters['frequente']) && $filters['frequente'] !== '') {
            $where[] = 't.frequente = :frequente';
            $params[':frequente'] = (int) $filters['frequente'];
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
