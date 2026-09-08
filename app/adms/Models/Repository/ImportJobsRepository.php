<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class ImportJobsRepository extends DbConnection
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO adms_import_jobs (
                    profile_key, operation, empty_policy, dry_run, status,
                    original_filename, stored_path, delimiter, headers_json,
                    mapping_json, key_field, created_by, created_at, updated_at
                ) VALUES (
                    :profile_key, :operation, :empty_policy, :dry_run, :status,
                    :original_filename, :stored_path, :delimiter, :headers_json,
                    :mapping_json, :key_field, :created_by, :created_at, :updated_at
                )';
        $now = date('Y-m-d H:i:s');
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':profile_key' => $data['profile_key'],
            ':operation' => $data['operation'],
            ':empty_policy' => $data['empty_policy'] ?? 'skip',
            ':dry_run' => !empty($data['dry_run']) ? 1 : 0,
            ':status' => $data['status'] ?? 'uploaded',
            ':original_filename' => $data['original_filename'] ?? null,
            ':stored_path' => $data['stored_path'] ?? null,
            ':delimiter' => $data['delimiter'] ?? ';',
            ':headers_json' => $data['headers_json'] ?? null,
            ':mapping_json' => $data['mapping_json'] ?? null,
            ':key_field' => $data['key_field'] ?? null,
            ':created_by' => $data['created_by'] ?? null,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return (int) $this->getConnection()->lastInsertId();
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT j.*, u.name AS created_by_nome
                FROM adms_import_jobs j
                LEFT JOIN adms_users u ON u.id = j.created_by
                WHERE j.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function listRecent(int $limit = 50, ?int $createdBy = null): array
    {
        $sql = 'SELECT j.*, u.name AS created_by_nome
                FROM adms_import_jobs j
                LEFT JOIN adms_users u ON u.id = j.created_by';
        if ($createdBy !== null && $createdBy > 0) {
            $sql .= ' WHERE j.created_by = :uid';
        }
        $sql .= ' ORDER BY j.id DESC LIMIT :lim';
        $stmt = $this->getConnection()->prepare($sql);
        if ($createdBy !== null && $createdBy > 0) {
            $stmt->bindValue(':uid', $createdBy, PDO::PARAM_INT);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $col => $value) {
            $fields[] = "{$col} = :{$col}";
            $params[":{$col}"] = $value;
        }
        $fields[] = 'updated_at = :updated_at';
        $params[':updated_at'] = date('Y-m-d H:i:s');
        $sql = 'UPDATE adms_import_jobs SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);

        return $stmt->execute($params);
    }
}
