<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class NotificationsRepository extends DbConnection
{
    /**
     * Cria uma notificação para um usuário.
     *
     * @param array $data [ user_id, type, title, message?, link_url?, entity_type?, entity_id? ]
     * @return int|false ID da notificação ou false
     */
    public function create(array $data)
    {
        $sql = 'INSERT INTO adms_notifications
                    (user_id, type, title, message, link_url, entity_type, entity_id, created_at)
                VALUES
                    (:user_id, :type, :title, :message, :link_url, :entity_type, :entity_id, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int)$data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':type', $data['type'] ?? 'info', PDO::PARAM_STR);
        $stmt->bindValue(':title', $data['title'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':message', $data['message'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':link_url', $data['link_url'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':entity_type', $data['entity_type'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', isset($data['entity_id']) ? (int)$data['entity_id'] : null, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Conta notificações não lidas do usuário.
     */
    public function countUnread(int $userId): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM adms_notifications
                WHERE user_id = :user_id AND read_at IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Conta notificações não lidas por prefixo do tipo (ex.: "timeline_").
     */
    public function countUnreadByTypePrefix(int $userId, string $typePrefix): int
    {
        $prefix = trim($typePrefix);
        if ($prefix === '') {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS total
                FROM adms_notifications
                WHERE user_id = :user_id
                  AND read_at IS NULL
                  AND type LIKE :type_prefix';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':type_prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Lista notificações do usuário (não lidas primeiro, depois por data).
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function listForUser(int $userId, int $limit = 20): array
    {
        $sql = 'SELECT id, type, title, message, link_url, read_at, created_at
                FROM adms_notifications
                WHERE user_id = :user_id
                ORDER BY read_at IS NULL DESC, created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista apenas não lidas para o dropdown do sino.
     */
    public function listUnreadForUser(int $userId, int $limit = 15): array
    {
        $sql = 'SELECT id, type, title, message, link_url, created_at
                FROM adms_notifications
                WHERE user_id = :user_id AND read_at IS NULL
                ORDER BY created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Atualiza o link_url de uma notificação (ex.: para acrescentar mark_notification).
     */
    public function updateLinkUrl(int $id, string $linkUrl): bool
    {
        $sql = 'UPDATE adms_notifications SET link_url = :link_url WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':link_url', $linkUrl, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(int $id, int $userId): bool
    {
        $sql = 'UPDATE adms_notifications SET read_at = NOW()
                WHERE id = :id AND user_id = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     */
    public function markAllAsRead(int $userId): bool
    {
        $sql = 'UPDATE adms_notifications SET read_at = NOW()
                WHERE user_id = :user_id AND read_at IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
