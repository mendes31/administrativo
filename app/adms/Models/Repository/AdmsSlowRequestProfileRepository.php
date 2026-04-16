<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class AdmsSlowRequestProfileRepository extends DbConnection
{
    public function logSlowRequest(array $data): void
    {
        $sql = 'INSERT INTO adms_slow_request_profiles
                (request_method, request_uri, route_label, user_id, duration_ms, memory_mb, created_at)
                VALUES
                (:request_method, :request_uri, :route_label, :user_id, :duration_ms, :memory_mb, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':request_method', (string)($data['request_method'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':request_uri', (string)($data['request_uri'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':route_label', (string)($data['route_label'] ?? ''), PDO::PARAM_STR);
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $stmt->bindValue(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':duration_ms', (int)($data['duration_ms'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':memory_mb', (float)($data['memory_mb'] ?? 0), PDO::PARAM_STR);
        $stmt->execute();
    }

    public function cleanupOldProfiles(int $retentionDays): void
    {
        $safeDays = $retentionDays > 0 ? $retentionDays : 7;
        $sql = 'DELETE FROM adms_slow_request_profiles
                WHERE created_at < (NOW() - INTERVAL :retention_days DAY)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':retention_days', $safeDays, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function listLatest(int $limit = 80): array
    {
        $safeLimit = $limit > 0 ? min($limit, 200) : 80;
        $sql = "SELECT id, request_method, request_uri, route_label, user_id, duration_ms, memory_mb, created_at
                FROM adms_slow_request_profiles
                ORDER BY id DESC
                LIMIT {$safeLimit}";
        $stmt = $this->getConnection()->query($sql);
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function listForExport(int $limit = 20000, ?string $from = null, ?string $to = null): array
    {
        $safeLimit = $limit > 0 ? min($limit, 50000) : 20000;
        $where = [];
        $params = [];

        if (!empty($from)) {
            $where[] = 'created_at >= :from';
            $params[':from'] = $from . ' 00:00:00';
        }
        if (!empty($to)) {
            $where[] = 'created_at <= :to';
            $params[':to'] = $to . ' 23:59:59';
        }

        $sql = "SELECT id, request_method, request_uri, route_label, user_id, duration_ms, memory_mb, created_at
                FROM adms_slow_request_profiles";

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id DESC LIMIT ' . $safeLimit;

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }
}
