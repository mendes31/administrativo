<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Invalida cache de permissões após deploy do menu SST equipamentos (sessões antigas).
 */
final class BumpMenuCacheSstEquipamentos extends AbstractMigration
{
    public function up(): void
    {
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
