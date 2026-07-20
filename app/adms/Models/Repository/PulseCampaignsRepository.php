<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PulseCampaignsRepository extends DbConnection
{
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_pulse_campaigns
                (name, campaign_type, status, starts_at, ends_at, anonymous, description, created_by)
                VALUES
                (:name, :campaign_type, :status, :starts_at, :ends_at, :anonymous, :description, :created_by)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':campaign_type', $data['campaign_type']);
        $stmt->bindValue(':status', $data['status'] ?? 'draft');
        $stmt->bindValue(':starts_at', $data['starts_at'] ?? null);
        $stmt->bindValue(':ends_at', $data['ends_at'] ?? null);
        $stmt->bindValue(':anonymous', !empty($data['anonymous']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':created_by', (int) $data['created_by'], PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE adms_pulse_campaigns SET
                    name = :name,
                    status = :status,
                    starts_at = :starts_at,
                    ends_at = :ends_at,
                    anonymous = :anonymous,
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':starts_at', $data['starts_at'] ?? null);
        $stmt->bindValue(':ends_at', $data['ends_at'] ?? null);
        $stmt->bindValue(':anonymous', !empty($data['anonymous']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':description', $data['description'] ?? null);

        return $stmt->execute();
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT c.*, u.name AS created_by_name
                FROM adms_pulse_campaigns c
                LEFT JOIN adms_users u ON u.id = c.created_by
                WHERE c.id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAll(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'c.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['campaign_type'])) {
            $where[] = 'c.campaign_type = :campaign_type';
            $params[':campaign_type'] = $filters['campaign_type'];
        }
        $sql = 'SELECT c.*, u.name AS created_by_name
                FROM adms_pulse_campaigns c
                LEFT JOIN adms_users u ON u.id = c.created_by
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['campaign_type'])) {
            $where[] = 'campaign_type = :campaign_type';
            $params[':campaign_type'] = $filters['campaign_type'];
        }
        $sql = 'SELECT COUNT(*) FROM adms_pulse_campaigns WHERE ' . implode(' AND ', $where);
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}
