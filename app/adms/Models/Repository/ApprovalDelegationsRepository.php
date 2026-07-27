<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Delegações temporárias de aprovação (ausência do gestor).
 */
class ApprovalDelegationsRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, bool $includeExpired = false): array
    {
        if ($userId <= 0 || !$this->tableExists()) {
            return [];
        }

        $sql = 'SELECT d.*,
                       a.name AS delegator_name,
                       b.name AS delegate_name
                FROM adms_approval_delegations d
                INNER JOIN adms_users a ON a.id = d.delegator_user_id
                INNER JOIN adms_users b ON b.id = d.delegate_user_id
                WHERE (d.delegator_user_id = :uid OR d.delegate_user_id = :uid2)';
        if (!$includeExpired) {
            $sql .= ' AND d.ends_at >= NOW()';
        }
        $sql .= ' ORDER BY d.starts_at DESC, d.id DESC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllActive(int $page = 1, int $limit = 50): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $offset = max(0, ($page - 1) * $limit);
        $sql = 'SELECT d.*,
                       a.name AS delegator_name,
                       b.name AS delegate_name
                FROM adms_approval_delegations d
                INNER JOIN adms_users a ON a.id = d.delegator_user_id
                INNER JOIN adms_users b ON b.id = d.delegate_user_id
                WHERE d.ends_at >= NOW()
                ORDER BY d.starts_at DESC, d.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActiveDelegateFor(int $delegatorUserId, ?\DateTimeInterface $at = null): ?array
    {
        if ($delegatorUserId <= 0 || !$this->tableExists()) {
            return null;
        }

        $atSql = $at ? $at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $sql = 'SELECT *
                FROM adms_approval_delegations
                WHERE delegator_user_id = :delegator
                  AND starts_at <= :at1
                  AND ends_at >= :at2
                ORDER BY id DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':delegator', $delegatorUserId, PDO::PARAM_INT);
        $stmt->bindValue(':at1', $atSql);
        $stmt->bindValue(':at2', $atSql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<int>
     */
    public function listDelegatorIdsForDelegate(int $delegateUserId, ?\DateTimeInterface $at = null): array
    {
        if ($delegateUserId <= 0 || !$this->tableExists()) {
            return [];
        }

        $atSql = $at ? $at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $sql = 'SELECT DISTINCT delegator_user_id
                FROM adms_approval_delegations
                WHERE delegate_user_id = :delegate
                  AND starts_at <= :at1
                  AND ends_at >= :at2';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':delegate', $delegateUserId, PDO::PARAM_INT);
        $stmt->bindValue(':at1', $atSql);
        $stmt->bindValue(':at2', $atSql);
        $stmt->execute();

        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'delegator_user_id'));
    }

    public function create(array $data): int
    {
        if (!$this->tableExists()) {
            return 0;
        }

        $sql = 'INSERT INTO adms_approval_delegations
                    (delegator_user_id, delegate_user_id, starts_at, ends_at, notes, created_by, created_at, updated_at)
                VALUES
                    (:delegator, :delegate, :starts, :ends, :notes, :created_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':delegator' => (int) $data['delegator_user_id'],
            ':delegate' => (int) $data['delegate_user_id'],
            ':starts' => $data['starts_at'],
            ':ends' => $data['ends_at'],
            ':notes' => $data['notes'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->getConnection()->lastInsertId();
    }

    public function delete(int $id): bool
    {
        if (!$this->tableExists() || $id <= 0) {
            return false;
        }

        $stmt = $this->getConnection()->prepare('DELETE FROM adms_approval_delegations WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getById(int $id): ?array
    {
        if (!$this->tableExists() || $id <= 0) {
            return null;
        }

        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_approval_delegations WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $this->getConnection()->query('SELECT 1 FROM adms_approval_delegations LIMIT 1');
            $exists = true;
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }
}
