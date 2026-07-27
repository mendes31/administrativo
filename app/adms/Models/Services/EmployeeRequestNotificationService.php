<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\AppEnvironmentHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\EmployeeRequestsRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Models\Repository\UsersRepository;
use PDO;

/**
 * Notificações in-app (+ e-mail + push automático) do workflow de solicitações do colaborador.
 */
final class EmployeeRequestNotificationService extends DbConnection
{
    public const ENTITY_TYPE = 'employee_request';

    public const TYPE_PENDING = 'employee_request_pending_approval';

    public const TYPE_APPROVED = 'employee_request_approved';

    public const TYPE_REJECTED = 'employee_request_rejected';

    public const TYPE_ESCALATED = 'employee_request_escalated';

    /**
     * Nova solicitação ou entrada em etapa aguardando ação.
     */
    public static function notifyPendingForRequest(int $requestId, ?int $excludeUserId = null): void
    {
        self::runSafely(function () use ($requestId, $excludeUserId): void {
            $request = self::loadRequest($requestId);
            if ($request === null) {
                return;
            }

            $status = (string) ($request['status'] ?? '');
            if ($status === 'pending_manager_approval') {
                $approverId = (int) ($request['current_approver_user_id'] ?? 0);
                if ($approverId > 0) {
                    self::notifyUser(
                        $approverId,
                        $request,
                        self::TYPE_PENDING,
                        'Solicitação aguardando sua aprovação',
                        self::buildPendingMessage($request),
                        self::buildViewLink($requestId),
                        $excludeUserId
                    );
                }

                return;
            }

            if ($status === 'pending_hr_approval') {
                foreach (self::listHrApproverUserIds() as $hrUserId) {
                    self::notifyUser(
                        $hrUserId,
                        $request,
                        self::TYPE_PENDING,
                        'Solicitação aguardando aprovação do RH',
                        self::buildPendingMessage($request),
                        self::buildPendingApprovalsLink(),
                        $excludeUserId
                    );
                }
            }
        });
    }

    /**
     * Solicitação aprovada em todas as etapas.
     */
    public static function notifyFinalApproved(int $requestId, ?int $excludeUserId = null): void
    {
        self::runSafely(function () use ($requestId, $excludeUserId): void {
            $request = self::loadRequest($requestId);
            if ($request === null) {
                return;
            }

            $employeeId = (int) ($request['employee_id'] ?? 0);
            self::notifyUser(
                $employeeId,
                $request,
                self::TYPE_APPROVED,
                'Sua solicitação foi aprovada',
                self::buildSummaryMessage($request),
                self::buildViewLink($requestId),
                $excludeUserId
            );
        });
    }

    /**
     * Solicitação rejeitada em qualquer etapa.
     */
    public static function notifyRejected(int $requestId, ?int $excludeUserId = null): void
    {
        self::runSafely(function () use ($requestId, $excludeUserId): void {
            $request = self::loadRequest($requestId);
            if ($request === null) {
                return;
            }

            $employeeId = (int) ($request['employee_id'] ?? 0);
            $message = self::buildSummaryMessage($request);
            $reason = trim((string) ($request['rejection_reason'] ?? ''));
            if ($reason !== '') {
                $message .= ' Motivo: ' . $reason;
            }

            self::notifyUser(
                $employeeId,
                $request,
                self::TYPE_REJECTED,
                'Sua solicitação foi rejeitada',
                $message,
                self::buildViewLink($requestId),
                $excludeUserId
            );
        });
    }

    /**
     * Escalação dentro da mesma etapa (novo aprovador na hierarquia).
     */
    public static function notifyEscalated(int $requestId, int $newApproverId, ?int $excludeUserId = null): void
    {
        if ($newApproverId <= 0) {
            return;
        }

        self::runSafely(function () use ($requestId, $newApproverId, $excludeUserId): void {
            $request = self::loadRequest($requestId);
            if ($request === null) {
                return;
            }

            self::notifyUser(
                $newApproverId,
                $request,
                self::TYPE_ESCALATED,
                'Solicitação escalada para você',
                self::buildPendingMessage($request) . ' (escalação por SLA)',
                self::buildViewLink($requestId),
                $excludeUserId
            );
        });
    }

    public static function markNotificationsRead(int $userId, int $requestId): void
    {
        if ($userId <= 0 || $requestId <= 0) {
            return;
        }

        try {
            (new NotificationsRepository())->markAsReadByEntity($userId, self::ENTITY_TYPE, $requestId);
        } catch (\Throwable) {
        }
    }

    /**
     * @return list<int>
     */
    public static function listHrApproverUserIds(): array
    {
        try {
            $service = new self();
            $sql = 'SELECT DISTINCT ual.adms_user_id AS user_id
                    FROM adms_users_access_levels ual
                    INNER JOIN adms_access_levels_pages alp
                        ON alp.adms_access_level_id = ual.adms_access_level_id
                    INNER JOIN adms_pages ap ON ap.id = alp.adms_page_id
                    INNER JOIN adms_users u ON u.id = ual.adms_user_id
                    WHERE ap.controller = :controller
                      AND alp.permission = 1
                      AND ap.page_status = 1
                      AND (u.status IS NULL OR u.status = \'\' OR LOWER(u.status) = \'ativo\')
                      AND (u.data_desligamento IS NULL OR u.data_desligamento = \'\')';

            $stmt = $service->getConnection()->prepare($sql);
            $stmt->bindValue(':controller', EmployeeRequestPermissionService::CONTROLLER_APPROVE_HR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $ids = [];
            foreach ($rows as $row) {
                $id = (int) ($row['user_id'] ?? 0);
                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            return array_values(array_unique($ids));
        } catch (\Throwable $e) {
            error_log('EmployeeRequestNotificationService::listHrApproverUserIds error: ' . $e->getMessage());

            return [];
        }
    }

    private static function notifyUser(
        int $userId,
        array $request,
        string $type,
        string $title,
        string $message,
        string $link,
        ?int $excludeUserId
    ): void {
        if ($userId <= 0 || ($excludeUserId !== null && $excludeUserId === $userId)) {
            return;
        }

        $requestId = (int) ($request['id'] ?? 0);
        if ($requestId <= 0) {
            return;
        }

        $notifRepo = new NotificationsRepository();
        if ($notifRepo->existsForUserEntity($userId, self::ENTITY_TYPE, $requestId, $type)) {
            return;
        }

        $notifRepo->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => AppEnvironmentHelper::formatInAppTitle($title),
            'message' => AppEnvironmentHelper::formatInAppMessage($message),
            'link_url' => $link,
            'entity_type' => self::ENTITY_TYPE,
            'entity_id' => $requestId,
        ]);

        self::sendEmailIfPossible($userId, $title, $message, $link, $request);
    }

    private static function sendEmailIfPossible(int $userId, string $subject, string $message, string $link, array $request): void
    {
        $user = (new UsersRepository())->getUser($userId);
        if (!is_array($user)) {
            return;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $name = (string) ($user['name'] ?? $email);
        $bodyHtml = '<p>' . htmlspecialchars($message) . '</p>'
            . '<p><a href="' . htmlspecialchars($link) . '">Abrir solicitação</a></p>';
        $altBody = $message . "\n\n" . $link;

        $replyToEmail = null;
        $replyToName = null;
        $employeeEmail = trim((string) ($request['employee_email'] ?? ''));
        if ($employeeEmail !== '' && filter_var($employeeEmail, FILTER_VALIDATE_EMAIL)) {
            $replyToEmail = $employeeEmail;
            $replyToName = (string) ($request['employee_name'] ?? $employeeEmail);
        }

        try {
            SendEmailService::sendEmail($email, $name, $subject, $bodyHtml, $altBody, $replyToEmail, $replyToName);
        } catch (\Throwable) {
        }
    }

    private static function loadRequest(int $requestId): ?array
    {
        if ($requestId <= 0) {
            return null;
        }

        return (new EmployeeRequestsRepository())->getById($requestId);
    }

    private static function buildSummaryMessage(array $request): string
    {
        $typeLabel = self::resolveTypeLabel($request);
        $title = trim((string) ($request['title'] ?? ''));
        $employee = trim((string) ($request['employee_name'] ?? ''));

        $parts = [];
        if ($typeLabel !== '') {
            $parts[] = $typeLabel;
        }
        if ($title !== '') {
            $parts[] = $title;
        }
        if ($employee !== '') {
            $parts[] = 'Solicitante: ' . $employee;
        }

        return $parts !== [] ? implode(' — ', $parts) : 'Solicitação do colaborador';
    }

    private static function buildPendingMessage(array $request): string
    {
        return self::buildSummaryMessage($request);
    }

    private static function resolveTypeLabel(array $request): string
    {
        $code = trim((string) ($request['request_type'] ?? ''));
        if ($code === '') {
            return '';
        }

        $type = (new RequestTypesRepository())->getByCode($code);

        return trim((string) ($type['name'] ?? $code));
    }

    private static function buildViewLink(int $requestId): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/view-employee-request/' . $requestId;
    }

    private static function buildPendingApprovalsLink(): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/pending-approvals';
    }

    /**
     * @param callable(): void $callback
     */
    private static function runSafely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            error_log('EmployeeRequestNotificationService error: ' . $e->getMessage());
        }
    }
}
