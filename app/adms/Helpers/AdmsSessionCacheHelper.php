<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\MenuPermissionUserRepository;

/**
 * Invalidação centralizada de caches de sessão usados no layout (menu/navbar).
 */
final class AdmsSessionCacheHelper
{
    public static function clearLayoutCaches(): void
    {
        MenuPermissionUserRepository::clearSessionCache();
        NavbarLayoutCacheHelper::clear();
        unset($_SESSION['adms_user_access_level_ids']);
    }
}
