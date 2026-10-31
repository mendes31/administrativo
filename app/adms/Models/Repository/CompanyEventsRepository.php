<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class CompanyEventsRepository extends DbConnection implements CompanyEventsDashboardSupport
{
    /**
     * Registra leitura de um evento para o usuário (upsert).
     */
    public function upsertRead(int $eventId, int $userId): void
    {
        $sql = 'INSERT INTO adms_company_event_reads (event_id, user_id, read_at, created_at)
                VALUES (:e, :u, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    read_at = IF(read_at IS NULL, NOW(), read_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':u', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Condição SQL do sino e do contador de não lidos.
     * - Sem confirmação obrigatória: permanece até a visualização (leitura), como informativos e políticas.
     * - Com confirmação: permanece até confirmar ou recusar. Cancelar a presença devolve o aviso.
     */
    private function sqlConditionEventUnreadForUser(): string
    {
        return '(
            (
                (e.requires_rsvp IS NULL OR e.requires_rsvp = 0)
                AND NOT EXISTS (
                    SELECT 1
                    FROM adms_company_event_reads r
                    WHERE r.event_id = e.id
                      AND r.user_id = :u
                      AND r.read_at IS NOT NULL
                )
            )
            OR
            (
                e.requires_rsvp = 1
                AND NOT EXISTS (
                    SELECT 1
                    FROM adms_company_event_rsvps rsvp
                    WHERE rsvp.event_id = e.id
                      AND rsvp.user_id = :u
                      AND rsvp.status IN (\'confirmed\', \'declined\')
                )
            )
        )';
    }

    /**
     * O sino deixa de listar o evento quando o prazo de confirmação encerra (se houver)
     * ou quando a data/hora de fim do evento passa, mesmo sem visualização ou resposta.
     */
    private function sqlConditionEventStillOnBell(): string
    {
        return '(
            (e.rsvp_deadline IS NULL OR e.rsvp_deadline > NOW())
            AND e.ends_at > NOW()
        )';
    }

    /**
     * Marca uma lista de eventos como lidos para o usuário.
     *
     * @param int[] $eventIds
     */
    public function markManyAsRead(array $eventIds, int $userId): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $eventIds), static fn ($v) => $v > 0)));
        if ($userId <= 0 || $ids === []) {
            return;
        }
        foreach ($ids as $eventId) {
            $this->upsertRead($eventId, $userId);
        }
    }

    /**
     * Conta eventos não lidos do usuário que intersectam o mês informado.
     */
    public function countUnreadIntersectingMonth(int $year, int $month, int $userId): int
    {
        if ($userId <= 0 || \App\adms\Helpers\InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return 0;
        }
        $month = max(1, min(12, $month));
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $unread = $this->sqlConditionEventUnreadForUser();
        $sql = "SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$unread}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Conta eventos não lidos do usuário que intersectam o ano informado.
     */
    public function countUnreadIntersectingYear(int $year, int $userId): int
    {
        if ($userId <= 0 || \App\adms\Helpers\InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return 0;
        }
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-12-31 23:59:59', $year);

        $unread = $this->sqlConditionEventUnreadForUser();
        $sql = "SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$unread}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Eventos ativos, já publicados e ainda não lidos — base do sino (sem recorte de ano).
     */
    public function countUnreadPublished(int $userId): int
    {
        if ($userId <= 0 || \App\adms\Helpers\InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return 0;
        }

        $unread = $this->sqlConditionEventUnreadForUser();
        $onBell = $this->sqlConditionEventStillOnBell();
        $sql = "SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$onBell}
                  AND {$unread}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listUnreadPublished(int $userId, int $limit = 10): array
    {
        if ($userId <= 0 || \App\adms\Helpers\InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $unread = $this->sqlConditionEventUnreadForUser();
        $onBell = $this->sqlConditionEventStillOnBell();
        $sql = "SELECT e.id, e.title, e.location, e.starts_at, e.created_at
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND (e.publish_at IS NULL OR e.publish_at <= NOW())
                  AND (e.expire_at IS NULL OR e.expire_at > NOW())
                  AND {$onBell}
                  AND {$unread}
                ORDER BY COALESCE(e.publish_at, e.created_at) DESC, e.id DESC
                LIMIT {$limit}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':u' => $userId]);

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Liga eventos agendados cuja data de publicação chegou e que ainda não foram notificados.
     * Não reativa evento já notificado que o usuário tenha desligado depois.
     *
     * @return list<int>
     */
    public function activateScheduledPublications(): array
    {
        try {
            $sqlIds = 'SELECT id
                       FROM adms_company_events
                       WHERE ativo = 0
                         AND notified_at IS NULL
                         AND publish_at IS NOT NULL
                         AND publish_at <= NOW()
                         AND (expire_at IS NULL OR expire_at > NOW())';
            $rows = $this->getConnection()->query($sqlIds)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->getConnection()->prepare(
            "UPDATE adms_company_events
             SET ativo = 1, updated_at = NOW()
             WHERE id IN ({$placeholders}) AND notified_at IS NULL"
        );
        $stmt->execute($ids);

        return $ids;
    }

    public function clearPublicationNotified(int $eventId): void
    {
        if ($eventId <= 0) {
            return;
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE adms_company_events SET notified_at = NULL WHERE id = :id'
            );
            $stmt->execute([':id' => $eventId]);
        } catch (\Throwable $e) {
            error_log('CompanyEventsRepository::clearPublicationNotified: ' . $e->getMessage());
        }
    }

    /**
     * Eventos ativos que entraram na janela de publicação e ainda não dispararam push.
     *
     * @return list<int>
     */
    public function listIdsPendingPublicationNotification(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        try {
            $sql = "SELECT id
                    FROM adms_company_events
                    WHERE ativo = 1
                      AND notified_at IS NULL
                      AND (publish_at IS NULL OR publish_at <= NOW())
                      AND (expire_at IS NULL OR expire_at > NOW())
                    ORDER BY COALESCE(publish_at, created_at) ASC, id ASC
                    LIMIT {$limit}";
            $rows = $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function markPublicationNotified(int $eventId): void
    {
        if ($eventId <= 0) {
            return;
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE adms_company_events SET notified_at = NOW() WHERE id = :id AND notified_at IS NULL'
            );
            $stmt->execute([':id' => $eventId]);
        } catch (\Throwable $e) {
            error_log('CompanyEventsRepository::markPublicationNotified: ' . $e->getMessage());
        }
    }

    public function createEvent(array $data): int
    {
        $sql = 'INSERT INTO adms_company_events (
                    title, description, anexo, location, starts_at, ends_at, publish_at, expire_at,
                    rsvp_deadline, cancellation_deadline, requires_rsvp, allows_guests, max_guests_per_user,
                    created_by, department_id, ativo, created_at, updated_at
                ) VALUES (
                    :title, :description, :anexo, :location, :starts_at, :ends_at, :publish_at, :expire_at,
                    :rsvp_deadline, :cancellation_deadline, :requires_rsvp, :allows_guests, :max_guests,
                    :created_by, :department_id, :ativo, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':anexo' => $data['anexo'] ?? null,
            ':location' => $data['location'] ?? null,
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
            ':publish_at' => $data['publish_at'] ?? null,
            ':expire_at' => $data['expire_at'] ?? null,
            ':rsvp_deadline' => $data['rsvp_deadline'] ?? null,
            ':cancellation_deadline' => $data['cancellation_deadline'] ?? null,
            ':requires_rsvp' => !empty($data['requires_rsvp']) ? 1 : 0,
            ':allows_guests' => !empty($data['allows_guests']) ? 1 : 0,
            ':max_guests' => (int)($data['max_guests_per_user'] ?? 0),
            ':created_by' => (int)$data['created_by'],
            ':department_id' => isset($data['department_id']) ? (int)$data['department_id'] : null,
            ':ativo' => !empty($data['ativo']) ? 1 : 0,
        ]);
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if (is_array($newData)) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_company_events',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $newId;
    }

    public function updateEvent(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = 'UPDATE adms_company_events SET
                    title = :title, description = :description, anexo = :anexo, location = :location,
                    starts_at = :starts_at, ends_at = :ends_at, publish_at = :publish_at, expire_at = :expire_at,
                    rsvp_deadline = :rsvp_deadline, cancellation_deadline = :cancellation_deadline,
                    requires_rsvp = :requires_rsvp, allows_guests = :allows_guests, max_guests_per_user = :max_guests,
                    department_id = :department_id, ativo = :ativo, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':anexo' => $data['anexo'] ?? null,
            ':location' => $data['location'] ?? null,
            ':starts_at' => $data['starts_at'],
            ':ends_at' => $data['ends_at'],
            ':publish_at' => $data['publish_at'] ?? null,
            ':expire_at' => $data['expire_at'] ?? null,
            ':rsvp_deadline' => $data['rsvp_deadline'] ?? null,
            ':cancellation_deadline' => $data['cancellation_deadline'] ?? null,
            ':requires_rsvp' => !empty($data['requires_rsvp']) ? 1 : 0,
            ':allows_guests' => !empty($data['allows_guests']) ? 1 : 0,
            ':max_guests' => (int)($data['max_guests_per_user'] ?? 0),
            ':department_id' => isset($data['department_id']) ? (int)$data['department_id'] : null,
            ':ativo' => !empty($data['ativo']) ? 1 : 0,
        ]);
        if ($ok && is_array($oldData)) {
            $newData = $this->getById($id);
            if (is_array($newData)) {
                $usuarioId = $_SESSION['user_id'] ?? 1;
                LogAlteracaoService::registrarAlteracao(
                    'adms_company_events',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            }
        }

        return $ok;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_company_events WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function updateAnexo(int $eventId, ?string $anexoPath): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_events SET anexo = :anexo, updated_at = NOW() WHERE id = :id'
        );

        return $stmt->execute([':id' => $eventId, ':anexo' => $anexoPath]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getImagesForEvent(int $eventId): array
    {
        if ($eventId <= 0 || !$this->hasEventImagesTable()) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_images WHERE event_id = :e ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':e' => $eventId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countImagesForEvent(int $eventId): int
    {
        if ($eventId <= 0 || !$this->hasEventImagesTable()) {
            return 0;
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS c FROM adms_company_event_images WHERE event_id = :e'
        );
        $stmt->execute([':e' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['c'] ?? 0);
    }

    /**
     * @param array<int, string> $imagePaths
     */
    public function addImages(int $eventId, array $imagePaths): void
    {
        if ($eventId <= 0 || !$this->hasEventImagesTable() || $imagePaths === []) {
            return;
        }
        $startOrder = $this->countImagesForEvent($eventId);
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_images (event_id, image_path, sort_order, created_at)
             VALUES (:e, :p, :o, NOW())'
        );
        foreach (array_values($imagePaths) as $i => $path) {
            $path = trim($path);
            if ($path === '') {
                continue;
            }
            $stmt->execute([
                ':e' => $eventId,
                ':p' => $path,
                ':o' => $startOrder + $i,
            ]);
        }
    }

    /**
     * @param int[] $imageIds
     */
    public function deleteImagesByIds(int $eventId, array $imageIds): void
    {
        if ($eventId <= 0 || !$this->hasEventImagesTable() || $imageIds === []) {
            return;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $imageIds), static fn ($v) => $v > 0)));
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, image_path FROM adms_company_event_images WHERE event_id = ? AND id IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_merge([$eventId], $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            \App\adms\Helpers\CompanyEventUploadHelper::deleteStoredFile((string)($row['image_path'] ?? ''));
        }
        $del = $this->getConnection()->prepare(
            "DELETE FROM adms_company_event_images WHERE event_id = ? AND id IN ($placeholders)"
        );
        $del->execute(array_merge([$eventId], $ids));
    }

    public function deleteAllImagesForEvent(int $eventId): void
    {
        if ($eventId <= 0 || !$this->hasEventImagesTable()) {
            return;
        }
        foreach ($this->getImagesForEvent($eventId) as $img) {
            \App\adms\Helpers\CompanyEventUploadHelper::deleteStoredFile((string)($img['image_path'] ?? ''));
        }
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_company_event_images WHERE event_id = :e');
        $stmt->execute([':e' => $eventId]);
    }

    private function hasEventImagesTable(): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $row = $this->getConnection()->query("SHOW TABLES LIKE 'adms_company_event_images'")->fetch(PDO::FETCH_NUM);
            $cache = $row !== false;
        } catch (\Throwable) {
            $cache = false;
        }

        return $cache;
    }

    /**
     * Evento com criador e departamento (tela de visualização).
     */
    public function getByIdWithDisplayContext(int $id): ?array
    {
        $sql = 'SELECT e.*, u.name AS creator_name, d.name AS department_name
                FROM adms_company_events e
                INNER JOIN adms_users u ON u.id = e.created_by
                LEFT JOIN adms_departments d ON d.id = e.department_id
                WHERE e.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * Evento ativo e dentro da janela de publicação/expiração (colaborador com permissão só de visualizar).
     */
    public function isEventVisibleToCollaborators(array $event): bool
    {
        if (empty($event['ativo'])) {
            return false;
        }
        $now = time();
        if (!empty($event['publish_at']) && strtotime((string)$event['publish_at']) > $now) {
            return false;
        }
        if (!empty($event['expire_at']) && strtotime((string)$event['expire_at']) <= $now) {
            return false;
        }
        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGuestsForRsvpId(int $rsvpId): array
    {
        if ($rsvpId <= 0) {
            return [];
        }
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_guests WHERE rsvp_id = :r ORDER BY id ASC'
        );
        $stmt->execute([':r' => $rsvpId]);
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Eventos ativos/publicados visíveis ao colaborador no intervalo [start, end] (interseção de período).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDashboardVisibleEventsBetween(string $start, string $end): array
    {
        $sql = 'SELECT e.*, u.name AS creator_name
                FROM adms_company_events e
                INNER JOIN adms_users u ON u.id = e.created_by
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= :end)
                  AND (e.expire_at IS NULL OR e.expire_at >= :start)
                ORDER BY e.starts_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * Mês/ano padrão do modal no dashboard: mês atual se houver eventos;
     * senão o próximo mês (no ano ou anos seguintes) com eventos ativos.
     *
     * @return array{year: int, month: int}
     */
    public function resolveDashboardDisplayMonth(?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $currentMonth = (int) date('n');

        $countsCurrentYear = $this->getIntersectingMonthCountsForYear($year);

        if (($countsCurrentYear[$currentMonth] ?? 0) > 0) {
            return ['year' => $year, 'month' => $currentMonth];
        }

        for ($m = $currentMonth + 1; $m <= 12; $m++) {
            if (($countsCurrentYear[$m] ?? 0) > 0) {
                return ['year' => $year, 'month' => $m];
            }
        }

        for ($y = $year + 1; $y <= $year + 2; $y++) {
            $counts = $this->getIntersectingMonthCountsForYear($y);
            for ($m = 1; $m <= 12; $m++) {
                if (($counts[$m] ?? 0) > 0) {
                    return ['year' => $y, 'month' => $m];
                }
            }
        }

        return ['year' => $year, 'month' => $currentMonth];
    }

    /**
     * Contagem de eventos por mês (1–12) em uma única consulta — evita N× COUNT no dashboard.
     *
     * @return array<int, int> mês => total
     */
    public function getIntersectingMonthCountsForYear(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            return array_fill(1, 12, 0);
        }

        $selectParts = [];
        $params = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('%04d-%02d-01 00:00:00', $year, $m);
            $end = date('Y-m-t 23:59:59', strtotime($start));
            $selectParts[] = "SUM(CASE WHEN e.starts_at <= :end{$m} AND e.ends_at >= :start{$m}
                AND (e.publish_at IS NULL OR e.publish_at <= :end{$m})
                AND (e.expire_at IS NULL OR e.expire_at >= :start{$m}) THEN 1 ELSE 0 END) AS c{$m}";
            $params[":start{$m}"] = $start;
            $params[":end{$m}"] = $end;
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM adms_company_events e WHERE e.ativo = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $out = array_fill(1, 12, 0);
        for ($m = 1; $m <= 12; $m++) {
            $out[$m] = (int) ($row['c' . $m] ?? 0);
        }

        return $out;
    }

    /**
     * Conta eventos ativos/publicados que intersectam o mês informado.
     */
    public function countEventsIntersectingMonth(int $year, int $month): int
    {
        $month = max(1, min(12, $month));
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        $sql = 'SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= :end)
                  AND (e.expire_at IS NULL OR e.expire_at >= :start)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }

    /**
     * Eventos visíveis com período intersectando o ano civil informado (dashboard / modal “todos os meses”).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsIntersectingYear(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            return [];
        }
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-12-31 23:59:59', $year);

        return $this->getDashboardVisibleEventsBetween($start, $end);
    }

    /**
     * Eventos visíveis e com período intersectando o mês informado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsIntersectingMonth(int $year, int $month): array
    {
        $month = max(1, min(12, $month));
        $start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $end = date('Y-m-t 23:59:59', strtotime($start));

        return $this->getDashboardVisibleEventsBetween($start, $end);
    }

    /**
     * Conta eventos ativos/publicados que intersectam o ano informado.
     */
    public function countEventsIntersectingYear(int $year): int
    {
        $start = sprintf('%04d-01-01 00:00:00', $year);
        $end = sprintf('%04d-12-31 23:59:59', $year);

        $sql = 'SELECT COUNT(*) AS total
                FROM adms_company_events e
                WHERE e.ativo = 1
                  AND e.starts_at <= :end
                  AND e.ends_at >= :start
                  AND (e.publish_at IS NULL OR e.publish_at <= :end)
                  AND (e.expire_at IS NULL OR e.expire_at >= :start)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':start' => $start, ':end' => $end]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllForAdmin(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT e.*, u.name AS creator_name
                FROM adms_company_events e
                INNER JOIN adms_users u ON u.id = e.created_by
                ORDER BY e.starts_at DESC
                LIMIT :lim OFFSET :off';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function countAll(): int
    {
        $row = $this->getConnection()->query('SELECT COUNT(*) AS c FROM adms_company_events')->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function getOrCreateRsvp(int $eventId, int $userId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_rsvps WHERE event_id = :e AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':e' => $eventId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
        $ins = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_rsvps (event_id, user_id, status, created_at, updated_at)
             VALUES (:e, :u, "pending", NOW(), NOW())'
        );
        $ins->execute([':e' => $eventId, ':u' => $userId]);
        $id = (int)$this->getConnection()->lastInsertId();
        return $this->getRsvpById($id) ?? [];
    }

    public function getRsvpById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_company_event_rsvps WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function getRsvpForUser(int $eventId, int $userId): ?array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_company_event_rsvps WHERE event_id = :e AND user_id = :u LIMIT 1'
        );
        $stmt->execute([':e' => $eventId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * RSVPs do usuário para vários eventos (dashboard / listagens).
     *
     * @param list<int> $eventIds
     * @return array<int, array<string, mixed>> event_id => rsvp
     */
    public function getRsvpsMapForUserByEventIds(array $eventIds, int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $eventIds = array_values(array_unique(array_filter(array_map('intval', $eventIds), static fn(int $id): bool => $id > 0)));
        if ($eventIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = "SELECT * FROM adms_company_event_rsvps WHERE user_id = ? AND event_id IN ({$placeholders})";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_merge([$userId], $eventIds));

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $normalized = $this->normalizeRow($row);
            $eid = (int) ($normalized['event_id'] ?? 0);
            if ($eid > 0) {
                $map[$eid] = $normalized;
            }
        }

        return $map;
    }

    /**
     * Aplica auto-decline de RSVP após prazo (mesma regra de autoDeclineRsvpIfDeadlinePassed),
     * usando linhas de evento já carregadas e mapa de RSVPs em lote.
     *
     * @param list<array<string, mixed>> $events Linhas com id, ativo, requires_rsvp, rsvp_deadline
     * @param array<int, array<string, mixed>> $rsvpMap event_id => rsvp (será atualizado)
     * @return array<int, array<string, mixed>> mapa atualizado
     */
    public function applyAutoDeclineRsvpsAfterDeadlineForUser(array $events, int $userId, array $rsvpMap): array
    {
        if ($userId <= 0 || $events === []) {
            return $rsvpMap;
        }

        $now = date('Y-m-d H:i:s');
        $updateStmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = :st, responded_at = COALESCE(responded_at, NOW()), updated_at = NOW() WHERE id = :id'
        );
        $insertStmt = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_rsvps (event_id, user_id, status, responded_at, created_at, updated_at)
             VALUES (:e, :u, :st, NOW(), NOW(), NOW())'
        );

        foreach ($events as $event) {
            $eventId = (int) ($event['id'] ?? 0);
            if ($eventId <= 0 || empty($event['ativo'])) {
                continue;
            }
            if (empty($event['requires_rsvp']) || empty($event['rsvp_deadline'])) {
                continue;
            }
            if ($now <= (string) $event['rsvp_deadline']) {
                continue;
            }

            $rsvp = $rsvpMap[$eventId] ?? null;
            if ($rsvp) {
                if (($rsvp['status'] ?? '') === 'pending') {
                    $updateStmt->execute([':st' => 'declined', ':id' => (int) $rsvp['id']]);
                    $rsvp['status'] = 'declined';
                    $rsvpMap[$eventId] = $rsvp;
                }
                continue;
            }

            try {
                $insertStmt->execute([':e' => $eventId, ':u' => $userId, ':st' => 'declined']);
                $newId = (int) $this->getConnection()->lastInsertId();
                $created = $newId > 0 ? $this->getRsvpById($newId) : null;
                if ($created) {
                    $rsvpMap[$eventId] = $created;
                }
            } catch (\Throwable) {
                $again = $this->getRsvpForUser($eventId, $userId);
                if ($again && (($again['status'] ?? '') === 'pending')) {
                    $updateStmt->execute([':st' => 'declined', ':id' => (int) $again['id']]);
                    $again['status'] = 'declined';
                }
                if ($again) {
                    $rsvpMap[$eventId] = $again;
                }
            }
        }

        return $rsvpMap;
    }

    /**
     * Carrega RSVPs do usuário e aplica auto-decline quando o prazo expirou (dashboard).
     *
     * @param list<array<string, mixed>> $events
     * @return array<int, array<string, mixed>|null> event_id => rsvp ou null
     */
    public function buildDashboardRsvpMapForUser(array $events, int $userId): array
    {
        if ($userId <= 0 || $events === []) {
            return [];
        }

        $eventIds = array_map(static fn(array $e): int => (int) ($e['id'] ?? 0), $events);
        $rsvpMap = $this->getRsvpsMapForUserByEventIds($eventIds, $userId);
        $rsvpMap = $this->applyAutoDeclineRsvpsAfterDeadlineForUser($events, $userId, $rsvpMap);

        $out = [];
        foreach ($events as $event) {
            $eventId = (int) ($event['id'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }
            $out[$eventId] = $rsvpMap[$eventId] ?? null;
        }

        return $out;
    }

    /**
     * Após o prazo de RSVP: quem ficou em "pending" ou sem registro passa a "declined"
     * (ausência de resposta tratada como recusa para relatórios e contagem de não lidos).
     */
    public function autoDeclineRsvpIfDeadlinePassed(int $eventId, int $userId): void
    {
        if ($userId <= 0 || $eventId <= 0) {
            return;
        }
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            return;
        }
        if (empty($event['requires_rsvp']) || empty($event['rsvp_deadline'])) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        if ($now <= $event['rsvp_deadline']) {
            return;
        }

        $rsvp = $this->getRsvpForUser($eventId, $userId);
        if ($rsvp) {
            if (($rsvp['status'] ?? '') === 'pending') {
                $stmt = $this->getConnection()->prepare(
                    'UPDATE adms_company_event_rsvps SET status = :st, responded_at = COALESCE(responded_at, NOW()), updated_at = NOW() WHERE id = :id'
                );
                $stmt->execute([':st' => 'declined', ':id' => (int)$rsvp['id']]);
            }
            return;
        }

        $ins = $this->getConnection()->prepare(
            'INSERT INTO adms_company_event_rsvps (event_id, user_id, status, responded_at, created_at, updated_at)
             VALUES (:e, :u, :st, NOW(), NOW(), NOW())'
        );
        try {
            $ins->execute([':e' => $eventId, ':u' => $userId, ':st' => 'declined']);
        } catch (\Throwable $e) {
            $again = $this->getRsvpForUser($eventId, $userId);
            if ($again && (($again['status'] ?? '') === 'pending')) {
                $this->getConnection()->prepare(
                    'UPDATE adms_company_event_rsvps SET status = :st, responded_at = COALESCE(responded_at, NOW()), updated_at = NOW() WHERE id = :id'
                )->execute([':st' => 'declined', ':id' => (int)$again['id']]);
            }
        }
    }

    /**
     * Após o prazo de RSVP: cria linhas "declined" para colaboradores elegíveis sem registro
     * e atualiza "pending" → "declined", para o relatório listar todos (incl. quem nunca acessou o sistema).
     * Público: usuários ativos do departamento do evento, ou todos se departamento não informado.
     * O criador do evento é excluído (mesma ideia da contagem de não lidos).
     */
    public function syncAutoDeclineForAllEligibleUsersAfterDeadline(int $eventId): void
    {
        if ($eventId <= 0) {
            return;
        }
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            return;
        }
        if (empty($event['requires_rsvp']) || empty($event['rsvp_deadline'])) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        if ($now <= $event['rsvp_deadline']) {
            return;
        }

        $creatorId = (int)($event['created_by'] ?? 0);
        $deptId = isset($event['department_id']) ? (int)$event['department_id'] : 0;
        $deptFilter = $deptId > 0
            ? ' AND u.user_department_id = :dept'
            : '';

        $eidInt = (int)$eventId;
        $paramsIns = [':eid' => $eidInt, ':creator' => $creatorId];
        if ($deptId > 0) {
            $paramsIns[':dept'] = $deptId;
        }

        $sqlInsert = 'INSERT INTO adms_company_event_rsvps (event_id, user_id, status, responded_at, created_at, updated_at)
            SELECT :eid, u.id, \'declined\', NOW(), NOW(), NOW()
            FROM adms_users u
            WHERE u.status = \'Ativo\'
              AND u.id <> :creator
              ' . $deptFilter . '
              AND NOT EXISTS (
                SELECT 1 FROM adms_company_event_rsvps r
                WHERE r.event_id = ' . $eidInt . ' AND r.user_id = u.id
              )';
        $this->getConnection()->prepare($sqlInsert)->execute($paramsIns);

        $paramsUpd = [':eid' => $eventId, ':creator' => $creatorId];
        if ($deptId > 0) {
            $paramsUpd[':dept'] = $deptId;
        }
        $sqlUpdate = 'UPDATE adms_company_event_rsvps r
            INNER JOIN adms_users u ON u.id = r.user_id AND u.status = \'Ativo\'
            SET r.status = \'declined\',
                r.responded_at = COALESCE(r.responded_at, NOW()),
                r.updated_at = NOW()
            WHERE r.event_id = :eid
              AND r.user_id <> :creator
              AND r.status = \'pending\'
              ' . ($deptId > 0 ? ' AND u.user_department_id = :dept' : '');
        $this->getConnection()->prepare($sqlUpdate)->execute($paramsUpd);
    }

    /**
     * @param array<int, array{full_name: string, relationship?: string, age?: int, notes?: string}> $guests
     */
    public function saveRsvpWithGuests(
        int $eventId,
        int $userId,
        string $status,
        array $guests,
        bool $allowsGuests,
        int $maxGuests
    ): bool {
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            return false;
        }
        $now = date('Y-m-d H:i:s');

        $rsvp = $this->getOrCreateRsvp($eventId, $userId);
        $rsvpId = (int)($rsvp['id'] ?? 0);
        if ($rsvpId <= 0) {
            return false;
        }

        $newStatus = $status === 'declined' ? 'declined' : 'confirmed';
        if (!empty($event['rsvp_deadline']) && $now > $event['rsvp_deadline']) {
            return false;
        }

        $upd = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = :st, responded_at = NOW(), updated_at = NOW(), cancelled_at = NULL
             WHERE id = :id'
        );
        $upd->execute([':st' => $newStatus, ':id' => $rsvpId]);

        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => $rsvpId]);

        if ($allowsGuests && $newStatus === 'confirmed' && $maxGuests > 0) {
            $guests = array_slice($guests, 0, $maxGuests);
            $insG = $this->getConnection()->prepare(
                'INSERT INTO adms_company_event_guests (rsvp_id, full_name, relationship, age, notes, created_at)
                 VALUES (:r, :fn, :rel, :age, :notes, NOW())'
            );
            foreach ($guests as $g) {
                $name = trim((string)($g['full_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $insG->execute([
                    ':r' => $rsvpId,
                    ':fn' => mb_substr($name, 0, 200),
                    ':rel' => isset($g['relationship']) ? mb_substr((string)$g['relationship'], 0, 100) : null,
                    ':age' => isset($g['age']) ? (int)$g['age'] : null,
                    ':notes' => isset($g['notes']) ? mb_substr((string)$g['notes'], 0, 500) : null,
                ]);
            }
        }

        return true;
    }

    /**
     * RSVP para outro usuário: ignora prazo de confirmação/recusa (uso por criador/gestão).
     *
     * @param array<int, array{full_name: string, relationship?: string, age?: int, notes?: string}> $guests
     */
    public function adminSaveRsvpWithGuestsBypassDeadlines(
        int $eventId,
        int $targetUserId,
        string $status,
        array $guests,
        bool $allowsGuests,
        int $maxGuests
    ): bool {
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo']) || empty($event['requires_rsvp'])) {
            return false;
        }
        $newStatus = $status === 'declined' ? 'declined' : 'confirmed';
        $rsvp = $this->getOrCreateRsvp($eventId, $targetUserId);
        $rsvpId = (int)($rsvp['id'] ?? 0);
        if ($rsvpId <= 0) {
            return false;
        }

        $upd = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = :st, responded_at = NOW(), updated_at = NOW(), cancelled_at = NULL
             WHERE id = :id'
        );
        $upd->execute([':st' => $newStatus, ':id' => $rsvpId]);

        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => $rsvpId]);

        if ($allowsGuests && $newStatus === 'confirmed' && $maxGuests > 0) {
            $guests = array_slice($guests, 0, $maxGuests);
            $insG = $this->getConnection()->prepare(
                'INSERT INTO adms_company_event_guests (rsvp_id, full_name, relationship, age, notes, created_at)
                 VALUES (:r, :fn, :rel, :age, :notes, NOW())'
            );
            foreach ($guests as $g) {
                $name = trim((string)($g['full_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $insG->execute([
                    ':r' => $rsvpId,
                    ':fn' => mb_substr($name, 0, 200),
                    ':rel' => isset($g['relationship']) ? mb_substr((string)$g['relationship'], 0, 100) : null,
                    ':age' => isset($g['age']) ? (int)$g['age'] : null,
                    ':notes' => isset($g['notes']) ? mb_substr((string)$g['notes'], 0, 500) : null,
                ]);
            }
        }

        return true;
    }

    /** Cancela presença de outro usuário ignorando prazo (após confirmação). */
    public function adminCancelRsvpBypassDeadlines(int $eventId, int $targetUserId): bool
    {
        $event = $this->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            return false;
        }
        $rsvp = $this->getRsvpForUser($eventId, $targetUserId);
        if (!$rsvp || ($rsvp['status'] ?? '') !== 'confirmed') {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = "cancelled", cancelled_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':id' => (int)$rsvp['id']]);
        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => (int)$rsvp['id']]);
        return $stmt->rowCount() > 0;
    }

    public function cancelRsvp(int $eventId, int $userId): bool
    {
        $event = $this->getById($eventId);
        if (!$event) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($event['cancellation_deadline']) && $now > $event['cancellation_deadline']) {
            return false;
        }
        $rsvp = $this->getRsvpForUser($eventId, $userId);
        if (!$rsvp || ($rsvp['status'] ?? '') !== 'confirmed') {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_company_event_rsvps SET status = "cancelled", cancelled_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':id' => (int)$rsvp['id']]);
        $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => (int)$rsvp['id']]);
        return $stmt->rowCount() > 0;
    }

    public function deleteEvent(int $id): bool
    {
        $oldData = $this->getById($id);
        if (is_array($oldData)) {
            \App\adms\Helpers\CompanyEventUploadHelper::deleteStoredFile($oldData['anexo'] ?? null);
            $this->deleteAllImagesForEvent($id);
        }
        $rsvpIds = $this->getConnection()->prepare('SELECT id FROM adms_company_event_rsvps WHERE event_id = :e');
        $rsvpIds->execute([':e' => $id]);
        $ids = $rsvpIds->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($ids as $rid) {
            $this->getConnection()->prepare('DELETE FROM adms_company_event_guests WHERE rsvp_id = :r')->execute([':r' => (int)$rid]);
        }
        $this->getConnection()->prepare('DELETE FROM adms_company_event_rsvps WHERE event_id = :e')->execute([':e' => $id]);
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_company_events WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && is_array($oldData)) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            LogAlteracaoService::registrarAlteracao(
                'adms_company_events',
                $id,
                $usuarioId,
                'DELETE',
                $oldData,
                []
            );
        }

        return $deleted;
    }

    /**
     * Público do relatório: ativos (com resposta ou pendência) e inativos que confirmaram, recusaram ou cancelaram.
     *
     * @return array{ativos: array<int, array<string, mixed>>, inativos: array<int, array<string, mixed>>}
     */
    public function getReportListsForEvent(int $eventId): array
    {
        $empty = ['ativos' => [], 'inativos' => []];
        if ($eventId <= 0) {
            return $empty;
        }

        $event = $this->getById($eventId);
        if (!$event) {
            return $empty;
        }

        $deptId = (int) ($event['department_id'] ?? 0);
        $exclude = \App\adms\Helpers\InstitutionalSystemUserHelper::sqlExcludeUsersAlias('u');
        $deptSql = $deptId > 0 ? ' AND u.user_department_id = :dept' : '';
        $select = 'SELECT u.id AS user_id, u.name AS user_name, u.email AS user_email, u.status AS user_status,
                          d.name AS department_name,
                          r.id AS rsvp_id, r.status, r.responded_at, r.cancelled_at';

        $sqlAtivos = "{$select}
                FROM adms_users u
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                LEFT JOIN adms_company_event_rsvps r ON r.user_id = u.id AND r.event_id = :e
                WHERE u.status = 'Ativo'
                  AND {$exclude}
                  {$deptSql}
                ORDER BY u.name ASC";

        $sqlInativos = "{$select}
                FROM adms_users u
                INNER JOIN adms_company_event_rsvps r ON r.user_id = u.id AND r.event_id = :e
                LEFT JOIN adms_departments d ON d.id = u.user_department_id
                WHERE u.status <> 'Ativo'
                  AND r.status IN ('confirmed', 'declined', 'cancelled')
                  AND {$exclude}
                  {$deptSql}
                ORDER BY u.name ASC";

        return [
            'ativos' => $this->attachReportGuests($this->fetchReportRows($sqlAtivos, $eventId, $deptId)),
            'inativos' => $this->attachReportGuests($this->fetchReportRows($sqlInativos, $eventId, $deptId)),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getReportRowsForEvent(int $eventId): array
    {
        return $this->getReportListsForEvent($eventId)['ativos'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchReportRows(string $sql, int $eventId, int $deptId): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':e', $eventId, PDO::PARAM_INT);
        if ($deptId > 0) {
            $stmt->bindValue(':dept', $deptId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function attachReportGuests(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $rsvpId = (int) ($row['rsvp_id'] ?? 0);
            if ($rsvpId > 0) {
                $ids[] = $rsvpId;
            }
        }

        $byRsvp = [];
        if ($ids !== []) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->getConnection()->prepare(
                "SELECT * FROM adms_company_event_guests WHERE rsvp_id IN ($placeholders) ORDER BY id ASC"
            );
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $guest) {
                $byRsvp[(int) ($guest['rsvp_id'] ?? 0)][] = $guest;
            }
        }

        foreach ($rows as &$row) {
            $row['guests'] = $byRsvp[(int) ($row['rsvp_id'] ?? 0)] ?? [];
        }
        unset($row);

        return $this->normalizeRows($rows);
    }

    /**
     * Contadores do relatório de confirmação/recusa.
     * Público: colaboradores ativos do departamento do evento, ou todos se não houver departamento.
     * O usuário manager fica de fora.
     *
     * @return array{total:int, confirmados:int, recusados:int, pendentes:int, cancelados:int}
     */
    public function getReportKpisForEvent(int $eventId): array
    {
        $empty = [
            'total' => 0,
            'confirmados' => 0,
            'recusados' => 0,
            'pendentes' => 0,
            'cancelados' => 0,
        ];
        if ($eventId <= 0) {
            return $empty;
        }

        $event = $this->getById($eventId);
        if (!$event) {
            return $empty;
        }

        $deptId = (int) ($event['department_id'] ?? 0);
        $exclude = \App\adms\Helpers\InstitutionalSystemUserHelper::sqlExcludeUsersAlias('u');
        $deptSql = $deptId > 0 ? ' AND u.user_department_id = :dept' : '';
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmados,
                    SUM(CASE WHEN r.status = 'declined' THEN 1 ELSE 0 END) AS recusados,
                    SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelados,
                    SUM(CASE WHEN r.id IS NULL OR r.status IS NULL OR r.status = '' OR r.status = 'pending' THEN 1 ELSE 0 END) AS pendentes
                FROM adms_users u
                LEFT JOIN adms_company_event_rsvps r
                    ON r.user_id = u.id AND r.event_id = :eid
                WHERE u.status = 'Ativo'
                  AND {$exclude}
                  {$deptSql}";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eid', $eventId, PDO::PARAM_INT);
        if ($deptId > 0) {
            $stmt->bindValue(':dept', $deptId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'confirmados' => (int) ($row['confirmados'] ?? 0),
            'recusados' => (int) ($row['recusados'] ?? 0),
            'pendentes' => (int) ($row['pendentes'] ?? 0),
            'cancelados' => (int) ($row['cancelados'] ?? 0),
        ];
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
        foreach (['title', 'description', 'location', 'creator_name', 'user_name', 'department_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        if (isset($row['guests']) && is_array($row['guests'])) {
            foreach ($row['guests'] as &$guest) {
                if (is_array($guest)) {
                    foreach (['full_name', 'relationship', 'notes'] as $field) {
                        if (array_key_exists($field, $guest) && is_string($guest[$field])) {
                            $guest[$field] = TextEncodingHelper::decodeEntities($guest[$field]);
                        }
                    }
                }
            }
            unset($guest);
        }
        return $row;
    }
}
