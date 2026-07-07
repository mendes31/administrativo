<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\WhistleblowingEncryptionService;
use PDO;

class WhistleblowingMessagesRepository extends DbConnection
{
    private WhistleblowingEncryptionService $encryption;

    public function __construct()
    {
        $this->encryption = new WhistleblowingEncryptionService();
    }

    public function createMessage(int $reportId, string $message, string $senderType, ?int $userId = null, bool $isInternal = false): ?int
    {
        $encrypted = $this->encryption->encrypt($message);

        $sql = 'INSERT INTO adms_whistleblowing_messages
                (report_id, sender_type, message_encrypted, is_internal_note, user_id, created_at)
                VALUES (:report_id, :sender_type, :message_encrypted, :is_internal_note, :user_id, :created_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':report_id' => $reportId,
            ':sender_type' => $senderType,
            ':message_encrypted' => $encrypted,
            ':is_internal_note' => $isInternal ? 1 : 0,
            ':user_id' => $userId,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return $ok ? (int) $this->getConnection()->lastInsertId() : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMessagesByReportId(int $reportId, bool $includeInternal = true): array
    {
        $sql = 'SELECT m.*, u.name AS user_name
                FROM adms_whistleblowing_messages m
                LEFT JOIN adms_users u ON m.user_id = u.id
                WHERE m.report_id = :report_id';
        if (!$includeInternal) {
            $sql .= ' AND m.is_internal_note = 0';
        }
        $sql .= ' ORDER BY m.created_at ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn (array $row) => $this->hydrateMessage($row), $rows);
    }

    /**
     * Mensagens visíveis ao denunciante (sem notas internas).
     *
     * @return list<array<string, mixed>>
     */
    public function getPublicMessagesByReportId(int $reportId): array
    {
        return $this->getMessagesByReportId($reportId, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function hydrateMessage(array $row): array
    {
        try {
            $row['message'] = $this->encryption->decrypt((string) ($row['message_encrypted'] ?? ''));
        } catch (\Throwable) {
            $row['message'] = '';
        }
        unset($row['message_encrypted']);

        return $row;
    }

    public function createAttachment(int $reportId, ?int $messageId, string $storedName, string $originalName, string $mimeType, int $sizeBytes, string $uploadedBy): ?int
    {
        $encryptedName = $this->encryption->encrypt($originalName);

        $sql = 'INSERT INTO adms_whistleblowing_attachments
                (report_id, message_id, stored_name, original_name_encrypted, mime_type, size_bytes, uploaded_by, created_at)
                VALUES (:report_id, :message_id, :stored_name, :original_name_encrypted, :mime_type, :size_bytes, :uploaded_by, :created_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':report_id' => $reportId,
            ':message_id' => $messageId,
            ':stored_name' => $storedName,
            ':original_name_encrypted' => $encryptedName,
            ':mime_type' => $mimeType,
            ':size_bytes' => $sizeBytes,
            ':uploaded_by' => $uploadedBy,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return $ok ? (int) $this->getConnection()->lastInsertId() : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAttachmentsByReportId(int $reportId, bool $denuncianteOnly = false): array
    {
        $sql = 'SELECT * FROM adms_whistleblowing_attachments WHERE report_id = :report_id';
        if ($denuncianteOnly) {
            $sql .= " AND uploaded_by = 'denunciante'";
        }
        $sql .= ' ORDER BY created_at ASC';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$row) {
            try {
                $row['original_name'] = $this->encryption->decrypt((string) ($row['original_name_encrypted'] ?? ''));
            } catch (\Throwable) {
                $row['original_name'] = 'arquivo';
            }
            unset($row['original_name_encrypted']);
        }
        unset($row);

        return $rows;
    }

    public function getAttachmentById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_whistleblowing_attachments WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        try {
            $row['original_name'] = $this->encryption->decrypt((string) ($row['original_name_encrypted'] ?? ''));
        } catch (\Throwable) {
            $row['original_name'] = 'arquivo';
        }
        unset($row['original_name_encrypted']);

        return $row;
    }
}
