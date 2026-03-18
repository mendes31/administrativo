<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para gerenciar grupos/equipes responsáveis por solicitações (Reserva de Salas)
 *
 * Tabelas:
 * - adms_room_request_groups
 * - adms_room_request_group_users
 */
class RoomRequestGroupsRepository extends DbConnection
{
    public function getAll(bool $onlyActive = false): array
    {
        $sql = "SELECT g.*,
                       (SELECT COUNT(*) FROM adms_room_request_group_users gu WHERE gu.group_id = g.id) AS members_count
                FROM adms_room_request_groups g";

        if ($onlyActive) {
            $sql .= " WHERE g.is_active = 1";
        }

        $sql .= " ORDER BY g.name ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM adms_room_request_groups WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getByName(string $name): ?array
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM adms_room_request_groups WHERE name = :name");
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->getConnection()->prepare(
            "INSERT INTO adms_room_request_groups (name, description, is_active, created_at, updated_at)
             VALUES (:name, :description, :is_active, NOW(), NOW())"
        );
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->getConnection()->prepare(
            "UPDATE adms_room_request_groups
             SET name = :name,
                 description = :description,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':is_active', !empty($data['is_active']) ? 1 : 0, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        // Não deletar se algum tipo estiver vinculado
        $check = $this->getConnection()->prepare("SELECT COUNT(*) AS total FROM adms_room_request_types WHERE default_responsible_group_id = :id");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->execute();
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if ((int)($row['total'] ?? 0) > 0) {
            return false;
        }

        // Limpar membros e excluir grupo
        $delMembers = $this->getConnection()->prepare("DELETE FROM adms_room_request_group_users WHERE group_id = :id");
        $delMembers->bindValue(':id', $id, PDO::PARAM_INT);
        $delMembers->execute();

        $del = $this->getConnection()->prepare("DELETE FROM adms_room_request_groups WHERE id = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        return $del->execute();
    }

    public function getMemberUserIds(int $groupId): array
    {
        $stmt = $this->getConnection()->prepare(
            "SELECT user_id FROM adms_room_request_group_users WHERE group_id = :group_id"
        );
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function replaceMembers(int $groupId, array $userIds): void
    {
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            $del = $conn->prepare("DELETE FROM adms_room_request_group_users WHERE group_id = :group_id");
            $del->bindValue(':group_id', $groupId, PDO::PARAM_INT);
            $del->execute();

            $ins = $conn->prepare(
                "INSERT INTO adms_room_request_group_users (group_id, user_id, created_at)
                 VALUES (:group_id, :user_id, NOW())"
            );
            foreach ($userIds as $uid) {
                $uid = (int)$uid;
                if ($uid <= 0) {
                    continue;
                }
                $ins->bindValue(':group_id', $groupId, PDO::PARAM_INT);
                $ins->bindValue(':user_id', $uid, PDO::PARAM_INT);
                $ins->execute();
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}

