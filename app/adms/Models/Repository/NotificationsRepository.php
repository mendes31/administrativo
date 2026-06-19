<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Models\Services\PushNotificationService;
use PDO;

class NotificationsRepository extends DbConnection
{
    /** Menções (@) e menções em projeto: ordenação e destaque na UI. */
    public const PRIORITY_MENTION = 100;

    public const PRIORITY_DEFAULT = 0;

    /**
     * Cria uma notificação para um usuário.
     *
     * @param array $data [ user_id, type, title, message?, link_url?, entity_type?, entity_id?, priority?, skip_push? ]
     * @return int|false ID da notificação ou false
     */
    public function create(array $data)
    {
        $priority = $this->resolvePriority($data);

        $sql = 'INSERT INTO adms_notifications
                    (user_id, type, title, message, link_url, entity_type, entity_id, priority, created_at)
                VALUES
                    (:user_id, :type, :title, :message, :link_url, :entity_type, :entity_id, :priority, NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', (int)$data['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':type', $data['type'] ?? 'info', PDO::PARAM_STR);
        $stmt->bindValue(':title', $data['title'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':message', $data['message'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':link_url', $data['link_url'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':entity_type', $data['entity_type'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', isset($data['entity_id']) ? (int)$data['entity_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':priority', $priority, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawNotificationRow($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0) > 0
                    ? (int) $_SESSION['user_id']
                    : (int) ($data['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_notifications',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }

            $this->dispatchWebPushForNotification((int) $data['user_id'], $data, $newId);
        }

        return $newId;
    }

    /**
     * Envia Web Push após gravar notificação in-app (falha no push não cancela o INSERT).
     *
     * @param array<string, mixed> $data
     */
    private function dispatchWebPushForNotification(int $userId, array $data, int $notificationId): void
    {
        if ($userId <= 0 || !empty($data['skip_push'])) {
            return;
        }

        try {
            if (!(new AdmsPushConfigRepository())->isEnabled()) {
                return;
            }

            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $title = 'Portal Tiaraju';
            }

            $body = trim((string) ($data['message'] ?? ''));
            if ($body === '') {
                $body = $title;
            }
            if (mb_strlen($body) > 200) {
                $body = mb_substr($body, 0, 197) . '...';
            }

            $url = $this->resolvePushUrl($data['link_url'] ?? null);

            (new PushNotificationService())->sendToUser($userId, $title, $body, $url);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Web Push após notificação in-app falhou (notificação gravada).', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePushUrl(?string $linkUrl): string
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $fallback = $base !== '' ? $base . '/notificacoes' : '/notificacoes';

        $link = trim((string) $linkUrl);
        if ($link === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        if (str_starts_with($link, '/')) {
            $parsed = parse_url($base);
            if (is_array($parsed) && !empty($parsed['scheme']) && !empty($parsed['host'])) {
                $origin = $parsed['scheme'] . '://' . $parsed['host'];
                if (!empty($parsed['port'])) {
                    $origin .= ':' . $parsed['port'];
                }

                return $origin . $link;
            }

            return $fallback;
        }

        return $base . '/' . ltrim($link, '/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolvePriority(array $data): int
    {
        if (array_key_exists('priority', $data)) {
            $p = (int)$data['priority'];

            return max(0, min(255, $p));
        }

        $type = (string)($data['type'] ?? 'info');

        return match ($type) {
            'timeline_mention', 'comentario_mencao' => self::PRIORITY_MENTION,
            default => self::PRIORITY_DEFAULT,
        };
    }

    /**
     * Verifica se o usuário já recebeu notificação de publicação para a entidade.
     */
    /**
     * Remove notificações vinculadas a uma entidade (ex.: reenvio de push em massa).
     */
    public function deleteByEntity(string $entityType, int $entityId): int
    {
        if ($entityId <= 0 || trim($entityType) === '') {
            return 0;
        }

        $sql = 'DELETE FROM adms_notifications
                WHERE entity_type = :entity_type AND entity_id = :entity_id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    public function existsForUserEntity(int $userId, string $entityType, int $entityId, ?string $type = null): bool
    {
        if ($userId <= 0 || $entityId <= 0 || trim($entityType) === '') {
            return false;
        }

        $sql = 'SELECT 1 FROM adms_notifications
                WHERE user_id = :user_id
                  AND entity_type = :entity_type
                  AND entity_id = :entity_id';
        if ($type !== null && trim($type) !== '') {
            $sql .= ' AND type = :type';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        if ($type !== null && trim($type) !== '') {
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
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
        $sql = 'SELECT id, type, title, message, link_url, read_at, created_at, priority
                FROM adms_notifications
                WHERE user_id = :user_id
                ORDER BY read_at IS NULL DESC, priority DESC, created_at DESC
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
        $sql = 'SELECT id, type, title, message, link_url, created_at, priority
                FROM adms_notifications
                WHERE user_id = :user_id AND read_at IS NULL
                ORDER BY priority DESC, created_at DESC
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
        $oldRow = $this->getRawNotificationRow($id);
        $sql = 'UPDATE adms_notifications SET link_url = :link_url WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':link_url', $linkUrl, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawNotificationRow($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 0) > 0 ? (int) $_SESSION['user_id'] : (int) ($oldRow['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_notifications',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
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
     * Marca como lidas todas as notificações não lidas do usuário para uma entidade.
     */
    public function markAsReadByEntity(int $userId, string $entityType, int $entityId): int
    {
        if ($userId <= 0 || $entityId <= 0 || trim($entityType) === '') {
            return 0;
        }
        $sql = 'UPDATE adms_notifications SET read_at = NOW()
                WHERE user_id = :user_id
                  AND entity_type = :entity_type
                  AND entity_id = :entity_id
                  AND read_at IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_STR);
        $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->rowCount();
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

    /**
     * Primeira notificação interna de publicação (tipo payroll_document) por documento de folha.
     *
     * @param list<int> $documentIds
     * @return array<int, string> entity_id => created_at (MySQL datetime)
     */
    public function findEarliestPayrollPublicationNotificationByDocumentIds(array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), fn (int $i) => $i > 0)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids);
        $sql = "SELECT entity_id, MIN(created_at) AS ts FROM adms_notifications
                WHERE entity_type = 'employee_payroll_document'
                  AND type = 'payroll_document'
                  AND entity_id IN ({$in})
                GROUP BY entity_id";
        try {
            $stmt = $this->getConnection()->query($sql);
        } catch (\Throwable) {
            return [];
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $eid = (int)($r['entity_id'] ?? 0);
            if ($eid > 0 && !empty($r['ts'])) {
                $map[$eid] = (string)$r['ts'];
            }
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawNotificationRow(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_notifications WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
