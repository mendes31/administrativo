<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class MenuPermissionUserRepository extends DbConnection
{
    public const SESSION_CACHE_KEY = 'adms_menu_allowed_controllers';
    private const FILTERED_LAYOUT_MENU_KEY = 'adms_page_layout_menu_permission_v2';

    public static function clearSessionCache(): void
    {
        unset($_SESSION[self::SESSION_CACHE_KEY], $_SESSION[self::FILTERED_LAYOUT_MENU_KEY]);
    }

    /**
     * Controllers permitidos para o menu lateral (fonte: banco / ACL do usuário).
     * Super Administrador e Super usuário recebem todas as páginas ativas via fetchAllowedControllersFromDatabase.
     *
     * @param array<int, string> $fullMenu legado — não filtra mais o resultado (mantido por compatibilidade de assinatura)
     * @return array<int, string>
     */
    public function getFilteredMenuForLayout(array $fullMenu): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $globalVersion = self::getGlobalPermissionCacheVersion();
        $cached = $_SESSION[self::FILTERED_LAYOUT_MENU_KEY] ?? null;

        if (
            is_array($cached)
            && (int) ($cached['user_id'] ?? 0) === $userId
            && (string) ($cached['version'] ?? '') === $globalVersion
            && is_array($cached['controllers'] ?? null)
        ) {
            return $cached['controllers'];
        }

        $filtered = $this->getAllowedControllersForSessionUser();

        $_SESSION[self::FILTERED_LAYOUT_MENU_KEY] = [
            'user_id' => $userId,
            'version' => $globalVersion,
            'controllers' => $filtered,
        ];

        return $filtered;
    }

    public static function bumpGlobalPermissionCacheVersion(): void
    {
        $dir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }

    private static function getGlobalPermissionCacheVersion(): string
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system'
            . DIRECTORY_SEPARATOR . 'menu_permission_version.txt';
        if (!is_file($path)) {
            return '0';
        }

        return trim((string) @file_get_contents($path)) ?: '0';
    }

    public function menuPermission(array $menu): array|bool
    {
        if ($menu === []) {
            return [];
        }

        $allowed = $this->getAllowedControllersForSessionUser();
        if ($allowed === []) {
            return [];
        }

        $allowedSet = array_flip($allowed);
        $out = [];
        foreach ($menu as $controller) {
            if (isset($allowedSet[$controller])) {
                $out[] = $controller;
            }
        }

        return $out;
    }

    /**
     * Controllers permitidos para o usuário logado (com cache em sessão).
     *
     * @return list<string>
     */
    public function getAllowedControllersForUser(): array
    {
        return $this->getAllowedControllersForSessionUser();
    }

    /**
     * Pré-carrega permissões de menu na sessão (ex.: após login).
     */
    public function warmSessionCache(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        $_SESSION['user_id'] = $userId;
        $this->getAllowedControllersForSessionUser();
    }

    /**
     * @return list<string>
     */
    private function getAllowedControllersForSessionUser(): array
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return [];
        }

        $globalVersion = self::getGlobalPermissionCacheVersion();
        $cached = $_SESSION[self::SESSION_CACHE_KEY] ?? null;
        if (
            is_array($cached)
            && (int) ($cached['user_id'] ?? 0) === $userId
            && is_array($cached['controllers'] ?? null)
            && (string) ($cached['version'] ?? '') === $globalVersion
        ) {
            return $cached['controllers'];
        }

        $controllers = $this->fetchAllowedControllersFromDatabase($userId);
        $_SESSION[self::SESSION_CACHE_KEY] = [
            'user_id' => $userId,
            'controllers' => $controllers,
            'version' => $globalVersion,
        ];

        return $controllers;
    }

    /**
     * @return list<string>
     */
    private function fetchAllowedControllersFromDatabase(int $userId): array
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            $sql = "SELECT controller FROM adms_pages
                    WHERE page_status = 1
                      AND controller IS NOT NULL
                      AND controller <> ''";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
        } else {
            $sql = 'SELECT DISTINCT ap.controller
                    FROM adms_users_access_levels AS aual
                    INNER JOIN adms_access_levels_pages AS alp
                        ON alp.adms_access_level_id = aual.adms_access_level_id
                    INNER JOIN adms_pages AS ap ON ap.id = alp.adms_page_id
                    WHERE aual.adms_user_id = :user_id
                      AND alp.permission = 1
                      AND ap.page_status = 1
                      AND ap.controller IS NOT NULL
                      AND ap.controller <> \'\'';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result ? array_values(array_unique(array_column($result, 'controller'))) : [];
    }
}
