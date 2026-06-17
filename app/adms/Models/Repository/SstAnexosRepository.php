<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstAnexosRepository extends DbConnection
{
    public function getByEntity(string $entityType, int $entityId): array
    {
        $sql = 'SELECT a.*, u.name AS uploaded_by_name
                FROM adms_sst_anexos a
                LEFT JOIN adms_users u ON u.id = a.uploaded_by
                WHERE a.entity_type = :entity_type AND a.entity_id = :entity_id
                ORDER BY a.id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_anexos
                (entity_type, entity_id, file_name, file_path, mime_type, file_size, uploaded_by, created_at)
                VALUES (:entity_type, :entity_id, :file_name, :file_path, :mime_type, :file_size, :uploaded_by, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':entity_type', $data['entity_type'], PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', (int) $data['entity_id'], PDO::PARAM_INT);
        $stmt->bindValue(':file_name', $data['file_name'], PDO::PARAM_STR);
        $stmt->bindValue(':file_path', $data['file_path'], PDO::PARAM_STR);
        $stmt->bindValue(':mime_type', $data['mime_type'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':file_size', $data['file_size'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':uploaded_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_anexos', $newId, $uid, 'INSERT', [], $newData);
            }
        }
        return $newId;
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_sst_anexos WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_anexos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_anexos', $id, $uid, 'DELETE', $oldData, []);
        }
        return $deleted;
    }
}