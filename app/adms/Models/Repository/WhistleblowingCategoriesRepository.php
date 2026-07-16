<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class WhistleblowingCategoriesRepository extends DbConnection
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(): array
    {
        $sql = 'SELECT c.*,
                       (SELECT COUNT(*) FROM adms_whistleblowing_reports r WHERE r.category = c.name) AS reports_count
                FROM adms_whistleblowing_categories c
                ORDER BY c.sort_order ASC, c.name ASC';
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<string>
     */
    public function getActiveNames(): array
    {
        try {
            $sql = 'SELECT name FROM adms_whistleblowing_categories
                    WHERE is_active = 1
                    ORDER BY sort_order ASC, name ASC';
            $stmt = $this->getConnection()->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            return array_values(array_filter(array_map('strval', $rows)));
        } catch (\Throwable) {
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_whistleblowing_categories WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM adms_whistleblowing_categories WHERE name = :name';
        $params = [':name' => trim($name)];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function countReportsByName(string $name): int
    {
        $sql = 'SELECT COUNT(*) FROM adms_whistleblowing_reports WHERE category = :name';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function create(string $name, int $sortOrder = 0, ?string $description = null): ?int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO adms_whistleblowing_categories (name, description, sort_order, is_active, created_at, updated_at)
                VALUES (:name, :description, :sort_order, 1, :created_at, :updated_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':name' => trim($name),
            ':description' => $description !== null && trim($description) !== '' ? trim($description) : null,
            ':sort_order' => max(0, $sortOrder),
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $ok ? (int) $this->getConnection()->lastInsertId() : null;
    }

    public function update(int $id, string $name, int $sortOrder, bool $isActive, ?string $description = null, ?int $slaFirstResponseHours = null): bool
    {
        $sql = 'UPDATE adms_whistleblowing_categories
                SET name = :name, description = :description, sort_order = :sort_order,
                    is_active = :is_active, sla_first_response_hours = :sla_first_response_hours,
                    updated_at = :updated_at
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':name' => trim($name),
            ':description' => $description !== null && trim($description) !== '' ? trim($description) : null,
            ':sort_order' => max(0, $sortOrder),
            ':is_active' => $isActive ? 1 : 0,
            ':sla_first_response_hours' => ($slaFirstResponseHours !== null && $slaFirstResponseHours > 0)
                ? min(720, $slaFirstResponseHours)
                : null,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function renameReportsCategory(string $oldName, string $newName): void
    {
        if ($oldName === $newName) {
            return;
        }
        $sql = 'UPDATE adms_whistleblowing_reports SET category = :new_name, updated_at = :now WHERE category = :old_name';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':new_name' => $newName,
            ':old_name' => $oldName,
            ':now' => date('Y-m-d H:i:s'),
        ]);

        $sqlCc = 'UPDATE adms_whistleblowing_committee_categories SET category = :new_name WHERE category = :old_name';
        $stmtCc = $this->getConnection()->prepare($sqlCc);
        $stmtCc->execute([':new_name' => $newName, ':old_name' => $oldName]);
    }
}
