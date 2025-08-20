<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class NotificationsRepository extends DbConnection
{
    /**
     * Listar notificações do colaborador
     * @param int $usuarioId
     * @param int $limit
     * @return array
     */
    public function getUserNotifications(int $usuarioId, int $limit = 20): array
    {
        $sql = 'SELECT id, titulo, mensagem, url, lida, created_at
                FROM adms_notifications
                WHERE usuario_id = :usuario_id
                ORDER BY lida ASC, created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Criar notificação para o colaborador
     * @param array $data
     * @return bool
     */
    public function createNotification(array $data): bool
    {
        $sql = 'INSERT INTO adms_notifications (usuario_id, titulo, mensagem, url, lida, created_at)
                VALUES (:usuario_id, :titulo, :mensagem, :url, 0, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':mensagem', $data['mensagem'], PDO::PARAM_STR);
        $stmt->bindValue(':url', $data['url'], PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Marcar notificação como lida
     * @param int $id
     * @return bool
     */
    public function markAsRead(int $id): bool
    {
        $sql = 'UPDATE adms_notifications SET lida = 1 WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
} 