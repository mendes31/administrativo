<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SacTicketMessagesRepository extends DbConnection
{
    public function getMessagesByTicketId(int $ticketId): array
    {
        $sql = "SELECT m.*, u.name as sender_name
                FROM sac_ticket_messages m
                LEFT JOIN adms_users u ON m.user_id = u.id
                WHERE m.ticket_id = :ticket_id
                ORDER BY m.created_at ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function createMessage(array $data): int|false
    {
        $sql = "INSERT INTO sac_ticket_messages (ticket_id, user_id, message, is_internal_note, sender_type, created_at)
                VALUES (:ticket_id, :user_id, :message, :is_internal_note, :sender_type, NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $data['ticket_id'], PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':message', $data['message'], PDO::PARAM_STR);
        $stmt->bindValue(':is_internal_note', $data['is_internal_note'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':sender_type', $data['sender_type'] ?? 'agent', PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return false;
        }

        $newId = (int)$this->getConnection()->lastInsertId();

        if ($newId > 0) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_ticket_messages',
                $newId,
                $usuarioId,
                'INSERT',
                [],
                $data
            );
        }

        return $newId;
    }

    public function getAttachmentsByTicketId(int $ticketId): array
    {
        $sql = "SELECT a.*
                FROM sac_ticket_attachments a
                WHERE a.ticket_id = :ticket_id
                ORDER BY a.created_at ASC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getAttachmentsByMessageId(int $messageId): array
    {
        $sql = "SELECT * FROM sac_ticket_attachments WHERE message_id = :message_id ORDER BY created_at ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':message_id', $messageId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function createAttachment(array $data): int|false
    {
        $sql = "INSERT INTO sac_ticket_attachments (ticket_id, message_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                VALUES (:ticket_id, :message_id, :file_name, :file_path, :file_size, :file_type, :uploaded_by, NOW())";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $data['ticket_id'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':message_id', $data['message_id'] ?? null, $data['message_id'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':file_name', $data['file_name'], PDO::PARAM_STR);
        $stmt->bindValue(':file_path', $data['file_path'], PDO::PARAM_STR);
        $stmt->bindValue(':file_size', $data['file_size'] ?? 0, PDO::PARAM_INT);
        $stmt->bindValue(':file_type', $data['file_type'] ?? $data['mime_type'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':uploaded_by', $data['uploaded_by'] ?? null);

        if (!$stmt->execute()) {
            return false;
        }

        return (int)$this->getConnection()->lastInsertId();
    }

    public function deleteAttachment(int $id): bool
    {
        $sql = "SELECT * FROM sac_ticket_attachments WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);

        $deleteSql = "DELETE FROM sac_ticket_attachments WHERE id = :id";
        $deleteStmt = $this->getConnection()->prepare($deleteSql);
        $deleteStmt->bindValue(':id', $id, PDO::PARAM_INT);

        $deleteStmt->execute();
        $deleted = $deleteStmt->rowCount() > 0;

        if ($deleted && is_array($oldData)) {
            $usuarioId = (int)($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'sac_ticket_attachments',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    public function getStatusLogByTicketId(int $ticketId): array
    {
        $sql = "SELECT l.*, u.name as changed_by_name
                FROM sac_ticket_status_log l
                LEFT JOIN adms_users u ON l.changed_by = u.id
                WHERE l.ticket_id = :ticket_id
                ORDER BY l.created_at DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':ticket_id', $ticketId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['message', 'sender_name', 'changed_by_name', 'file_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
}
