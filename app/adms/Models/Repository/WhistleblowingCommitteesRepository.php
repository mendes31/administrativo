<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class WhistleblowingCommitteesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getAllCommittees(bool $activeOnly = false): array
    {
        $sql = 'SELECT c.*,
                    (SELECT COUNT(*) FROM adms_whistleblowing_committee_members m WHERE m.committee_id = c.id) AS members_count,
                    (SELECT COUNT(*) FROM adms_whistleblowing_committee_categories cat WHERE cat.committee_id = c.id) AS categories_count
                FROM adms_whistleblowing_committees c';
        if ($activeOnly) {
            $sql .= ' WHERE c.is_active = 1';
        }
        $sql .= ' ORDER BY c.name ASC';

        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getCommitteeById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_whistleblowing_committees WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<int>
     */
    public function getMemberIds(int $committeeId): array
    {
        $sql = 'SELECT user_id FROM adms_whistleblowing_committee_members WHERE committee_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $committeeId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows ?: []);
    }

    /**
     * @return list<string>
     */
    public function getCategories(int $committeeId): array
    {
        $sql = 'SELECT category FROM adms_whistleblowing_committee_categories WHERE committee_id = :id ORDER BY category';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $committeeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function findCommitteeIdByCategory(string $category): ?int
    {
        $sql = 'SELECT cc.committee_id
                FROM adms_whistleblowing_committee_categories cc
                INNER JOIN adms_whistleblowing_committees c ON c.id = cc.committee_id
                WHERE cc.category = :category AND c.is_active = 1
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':category', $category);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function getFirstMemberUserId(int $committeeId): ?int
    {
        $sql = 'SELECT user_id FROM adms_whistleblowing_committee_members WHERE committee_id = :id ORDER BY id ASC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $committeeId, PDO::PARAM_INT);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * @param list<int> $memberIds
     * @param list<string> $categories
     */
    public function createCommittee(string $name, ?string $description, array $memberIds, array $categories): ?int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_whistleblowing_committees (name, description, is_active, created_at, updated_at)
                VALUES (:name, :description, 1, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        if (!$ok) {
            return null;
        }

        $id = (int) $this->getConnection()->lastInsertId();
        $this->syncMembers($id, $memberIds);
        $this->syncCategories($id, $categories);

        return $id;
    }

    /**
     * @param list<int> $memberIds
     * @param list<string> $categories
     */
    public function updateCommittee(int $id, string $name, ?string $description, bool $isActive, array $memberIds, array $categories): bool
    {
        $sql = 'UPDATE adms_whistleblowing_committees
                SET name = :name, description = :description, is_active = :is_active, updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':is_active' => $isActive ? 1 : 0,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        if (!$ok) {
            return false;
        }

        $this->syncMembers($id, $memberIds);
        $this->syncCategories($id, $categories);

        return true;
    }

    /**
     * @param list<int> $memberIds
     */
    private function syncMembers(int $committeeId, array $memberIds): void
    {
        $this->getConnection()->prepare('DELETE FROM adms_whistleblowing_committee_members WHERE committee_id = :id')
            ->execute([':id' => $committeeId]);

        $sql = 'INSERT INTO adms_whistleblowing_committee_members (committee_id, user_id, created_at) VALUES (:cid, :uid, :now)';
        $stmt = $this->getConnection()->prepare($sql);
        $now = date('Y-m-d H:i:s');

        foreach (array_unique(array_filter($memberIds)) as $uid) {
            $stmt->execute([':cid' => $committeeId, ':uid' => (int) $uid, ':now' => $now]);
        }
    }

    /**
     * @param list<string> $categories
     */
    private function syncCategories(int $committeeId, array $categories): void
    {
        $this->getConnection()->prepare('DELETE FROM adms_whistleblowing_committee_categories WHERE committee_id = :id')
            ->execute([':id' => $committeeId]);

        $sql = 'INSERT INTO adms_whistleblowing_committee_categories (committee_id, category, created_at) VALUES (:cid, :cat, :now)';
        $stmt = $this->getConnection()->prepare($sql);
        $now = date('Y-m-d H:i:s');

        foreach (array_unique(array_filter($categories)) as $cat) {
            $stmt->execute([':cid' => $committeeId, ':cat' => trim($cat), ':now' => $now]);
        }
    }
}
