<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\AdmsMcpApiConfigRepository;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Cache leve (sessão + TTL) para dados do navbar — evita 6+ queries em cada troca de página.
 */
final class NavbarLayoutCacheHelper
{
    private const SESSION_KEY = 'adms_navbar_layout_cache';
    private const TTL_SECONDS = 90;

    public static function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @return array{
     *   user_info: array<string, mixed>|null,
     *   navbar_notif_count_informativos: int,
     *   navbar_notif_list_informativos: list<array<string, mixed>>,
     *   navbar_notif_count_policies: int,
     *   navbar_notif_list_policies: list<array<string, mixed>>,
     *   navbar_internal_count: int,
     *   navbar_internal_list: list<array<string, mixed>>,
     *   navbar_total_count: int,
     *   mcp_chat_available: bool
     * }
     */
    public static function get(int $userId, array $menuPermission): array
    {
        if ($userId <= 0) {
            return self::emptyPayload();
        }

        $cached = $_SESSION[self::SESSION_KEY] ?? null;
        if (
            is_array($cached)
            && (int) ($cached['user_id'] ?? 0) === $userId
            && (int) ($cached['expires_at'] ?? 0) > time()
            && is_array($cached['payload'] ?? null)
        ) {
            return $cached['payload'];
        }

        $payload = self::build($userId, $menuPermission);
        $_SESSION[self::SESSION_KEY] = [
            'user_id' => $userId,
            'expires_at' => time() + self::TTL_SECONDS,
            'payload' => $payload,
        ];

        return $payload;
    }

    /**
     * @param array<int, string> $menuPermission
     * @return array<string, mixed>
     */
    private static function build(int $userId, array $menuPermission): array
    {
        try {
            $userInfo = (new UsersRepository())->getUser($userId);
            $infoRepo = new InformativosRepository();
            $policiesRepo = new PoliciesRepository();
            $notifRepo = new NotificationsRepository();

            $navbarNotifCountInformativos = $infoRepo->countNaoLidos($userId);
            $navbarNotifListInformativos = $navbarNotifCountInformativos > 0
                ? $infoRepo->getListNaoLidos($userId, 10)
                : [];

            $navbarNotifCountPolicies = $policiesRepo->countNaoLidos($userId);
            $navbarNotifListPolicies = $navbarNotifCountPolicies > 0
                ? $policiesRepo->getListNaoLidos($userId, 10)
                : [];

            $navbarInternalCount = $notifRepo->countUnread($userId);
            $navbarInternalList = $navbarInternalCount > 0
                ? $notifRepo->listUnreadForUser($userId, 10)
                : [];

            $mcpConfig = (new AdmsMcpApiConfigRepository())->getConfig();
            $mcpEnabled = !empty($mcpConfig) && !empty($mcpConfig['is_active']) && !empty($mcpConfig['base_url']);
            $userCanMcpChat = UserAccessHelper::hasFullSystemAccess()
                || in_array('McpChat', $menuPermission, true);

            return [
                'user_info' => is_array($userInfo) ? $userInfo : null,
                'navbar_notif_count_informativos' => $navbarNotifCountInformativos,
                'navbar_notif_list_informativos' => $navbarNotifListInformativos,
                'navbar_notif_count_policies' => $navbarNotifCountPolicies,
                'navbar_notif_list_policies' => $navbarNotifListPolicies,
                'navbar_internal_count' => $navbarInternalCount,
                'navbar_internal_list' => $navbarInternalList,
                'navbar_total_count' => $navbarNotifCountInformativos + $navbarNotifCountPolicies + $navbarInternalCount,
                'mcp_chat_available' => $mcpEnabled && $userCanMcpChat,
            ];
        } catch (\Throwable) {
            return self::emptyPayload();
        }
    }

    private static function emptyPayload(): array
    {
        return [
            'user_info' => null,
            'navbar_notif_count_informativos' => 0,
            'navbar_notif_list_informativos' => [],
            'navbar_notif_count_policies' => 0,
            'navbar_notif_list_policies' => [],
            'navbar_internal_count' => 0,
            'navbar_internal_list' => [],
            'navbar_total_count' => 0,
            'mcp_chat_available' => false,
        ];
    }
}
