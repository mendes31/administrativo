<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEpiEntregasRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, u.name AS colaborador_nome, ep.nome AS epi_nome
                FROM adms_sst_epi_entregas t LEFT JOIN adms_users u ON u.id = t.adms_user_id LEFT JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id
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
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_epi_entregas t LEFT JOIN adms_users u ON u.id = t.adms_user_id LEFT JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT t.*, u.name AS colaborador_nome, ep.nome AS epi_nome FROM adms_sst_epi_entregas t LEFT JOIN adms_users u ON u.id = t.adms_user_id LEFT JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByUserId(int $userId, int $limit = 50): array
    {
        if (!in_array('adms_user_id', ['adms_user_id', 'adms_sst_epi_id', 'tipo_movimento', 'quantidade', 'data_movimento', 'data_prevista_troca', 'termo_assinado', 'observacoes'], true)) {
            return [];
        }
        $sql = "SELECT t.*, u.name AS colaborador_nome, ep.nome AS epi_nome FROM adms_sst_epi_entregas t LEFT JOIN adms_users u ON u.id = t.adms_user_id LEFT JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id WHERE t.adms_user_id = :uid ORDER BY t.data_movimento DESC, t.id DESC LIMIT :lim";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $hasFichaCol = $this->hasFichaColumn();
        $cols = 'adms_user_id, adms_sst_epi_id';
        $vals = ':adms_user_id, :adms_sst_epi_id';
        if ($hasFichaCol) {
            $cols .= ', adms_sst_epi_ficha_id';
            $vals .= ', :adms_sst_epi_ficha_id';
        }
        $cols .= ', tipo_movimento, quantidade';
        $vals .= ', :tipo_movimento, :quantidade';
        if ($this->hasColumn('tamanho')) {
            $cols .= ', tamanho';
            $vals .= ', :tamanho';
        }
        $cols .= ', data_movimento, data_prevista_troca, termo_assinado, observacoes, created_by, updated_by, created_at, updated_at';
        $vals .= ', :data_movimento, :data_prevista_troca, :termo_assinado, :observacoes, :created_by, :updated_by, NOW(), NOW()';
        $sql = "INSERT INTO adms_sst_epi_entregas ({$cols}) VALUES ({$vals})";
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_epi_id', $data['adms_sst_epi_id'] ?? null);
        if ($hasFichaCol) {
            $this->bindField($stmt, ':adms_sst_epi_ficha_id', $data['adms_sst_epi_ficha_id'] ?? null);
        }
        $this->bindField($stmt, ':tipo_movimento', $data['tipo_movimento'] ?? null);
        $this->bindField($stmt, ':quantidade', $data['quantidade'] ?? null);
        if ($this->hasColumn('tamanho')) {
            $tam = \App\adms\Helpers\SstEpiTamanhoHelper::normalize((string) ($data['tamanho'] ?? ''));
            $this->bindField($stmt, ':tamanho', $tam !== '' ? $tam : null);
        }
        $this->bindField($stmt, ':data_movimento', $data['data_movimento'] ?? null);
        $this->bindField($stmt, ':data_prevista_troca', $data['data_prevista_troca'] ?? null);
        $this->bindField($stmt, ':termo_assinado', $data['termo_assinado'] ?? null);
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
                LogAlteracaoService::registrarAlteracao('adms_sst_epi_entregas', $newId, $uid, 'INSERT', [], $newData);
            }
        }
        return $newId;
    }

    /** @param array<string, mixed> $ficha @param array<string, mixed> $item */
    public function createFromFichaItem(array $ficha, array $item, int $fichaId, int $actorUserId): int|false
    {
        return $this->create([
            'adms_user_id' => (int) ($ficha['adms_user_id'] ?? 0),
            'adms_sst_epi_id' => (int) ($item['adms_sst_epi_id'] ?? 0),
            'adms_sst_epi_ficha_id' => $fichaId,
            'tipo_movimento' => 'Entrega',
            'quantidade' => (int) ($item['quantidade'] ?? 1),
            'tamanho' => $item['tamanho'] ?? null,
            'data_movimento' => (string) ($ficha['data_entrega'] ?? date('Y-m-d')),
            'data_prevista_troca' => $item['data_prevista_troca'] ?? null,
            'termo_assinado' => true,
            'observacoes' => $item['observacoes'] ?? null,
        ]);
    }

    private function hasFichaColumn(): bool
    {
        return $this->hasColumn('adms_sst_epi_ficha_id');
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $stmt = $this->getConnection()->query(
                'SHOW COLUMNS FROM adms_sst_epi_entregas LIKE ' . $this->getConnection()->quote($column)
            );
            $cache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = "UPDATE adms_sst_epi_entregas SET adms_user_id = :adms_user_id, adms_sst_epi_id = :adms_sst_epi_id, tipo_movimento = :tipo_movimento, quantidade = :quantidade, data_movimento = :data_movimento, data_prevista_troca = :data_prevista_troca, termo_assinado = :termo_assinado, observacoes = :observacoes, updated_by = :updated_by, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindField($stmt, ':adms_user_id', $data['adms_user_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_epi_id', $data['adms_sst_epi_id'] ?? null);
        $this->bindField($stmt, ':tipo_movimento', $data['tipo_movimento'] ?? null);
        $this->bindField($stmt, ':quantidade', $data['quantidade'] ?? null);
        $this->bindField($stmt, ':data_movimento', $data['data_movimento'] ?? null);
        $this->bindField($stmt, ':data_prevista_troca', $data['data_prevista_troca'] ?? null);
        $this->bindField($stmt, ':termo_assinado', $data['termo_assinado'] ?? null);
        $this->bindField($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_epi_entregas', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }
        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = "DELETE FROM adms_sst_epi_entregas WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_epi_entregas', $id, $uid, 'DELETE', $oldData, []);
        }
        return $deleted;
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search OR t.id LIKE :search OR ep.nome LIKE :search)';
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