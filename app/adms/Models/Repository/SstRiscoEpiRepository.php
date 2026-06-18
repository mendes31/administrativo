<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\SstPendenciasService;
use PDO;

class SstRiscoEpiRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*, r.nome AS risco_nome, ep.nome AS epi_nome
                FROM adms_sst_risco_epi t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id
                {$whereClause}
                ORDER BY r.nome, ep.nome
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
                FROM adms_sst_risco_epi t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id
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
        $sql = "SELECT t.*, r.nome AS risco_nome, ep.nome AS epi_nome
                FROM adms_sst_risco_epi t
                INNER JOIN adms_sst_riscos r ON r.id = t.adms_sst_risco_id
                INNER JOIN adms_sst_epis ep ON ep.id = t.adms_sst_epi_id
                WHERE t.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<int> */
    public function getEpiIdsByRisco(int $riscoId): array
    {
        if ($riscoId <= 0 || !$this->hasTable('adms_sst_risco_epi')) {
            return [];
        }
        $sql = 'SELECT DISTINCT adms_sst_epi_id FROM adms_sst_risco_epi WHERE adms_sst_risco_id = :rid ORDER BY adms_sst_epi_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':rid', $riscoId, PDO::PARAM_INT);
        $stmt->execute();
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $ids[] = (int) ($row['adms_sst_epi_id'] ?? 0);
        }

        return array_values(array_filter($ids, fn (int $id) => $id > 0));
    }

    /** @param list<int> $epiIds */
    public function syncEpisForRisco(int $riscoId, array $epiIds): void
    {
        if ($riscoId <= 0 || !$this->hasTable('adms_sst_risco_epi')) {
            return;
        }

        $epiIds = array_values(array_unique(array_filter(array_map('intval', $epiIds), fn (int $id) => $id > 0)));
        $current = $this->getEpiIdsByRisco($riscoId);

        foreach (array_diff($current, $epiIds) as $epiId) {
            $stmt = $this->getConnection()->prepare(
                'DELETE FROM adms_sst_risco_epi WHERE adms_sst_risco_id = :rid AND adms_sst_epi_id = :eid'
            );
            $stmt->bindValue(':rid', $riscoId, PDO::PARAM_INT);
            $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
            $stmt->execute();
        }

        foreach (array_diff($epiIds, $current) as $epiId) {
            $this->create([
                'adms_sst_risco_id' => $riscoId,
                'adms_sst_epi_id' => $epiId,
                'obrigatorio' => true,
                'observacoes' => null,
            ]);
        }

        SstPendenciasService::invalidateDashboardCache();
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_risco_epi
                (adms_sst_risco_id, adms_sst_epi_id, obrigatorio, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:adms_sst_risco_id, :adms_sst_epi_id, :obrigatorio, :observacoes, :created_by, :updated_by, NOW(), NOW())';
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
                LogAlteracaoService::registrarAlteracao('adms_sst_risco_epi', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = 'UPDATE adms_sst_risco_epi
                SET adms_sst_risco_id = :adms_sst_risco_id,
                    adms_sst_epi_id = :adms_sst_epi_id,
                    obrigatorio = :obrigatorio,
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
                LogAlteracaoService::registrarAlteracao('adms_sst_risco_epi', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_risco_epi WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_risco_epi', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(r.nome LIKE :search OR ep.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_sst_risco_id'])) {
            $where[] = 't.adms_sst_risco_id = :risco_id';
            $params[':risco_id'] = (int) $filters['adms_sst_risco_id'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':adms_sst_risco_id', $data['adms_sst_risco_id'] ?? null);
        $this->bindField($stmt, ':adms_sst_epi_id', $data['adms_sst_epi_id'] ?? null);
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

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }
}
