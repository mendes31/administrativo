<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Invalida cache de menu após registro do card DashboardCardEmployeePortal
 * (a migration anterior pode ter rodado sem bump em alguns ambientes).
 */
final class BumpPermissionCacheEmployeePortalCard extends AbstractMigration
{
    public function up(): void
    {
        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    public function down(): void
    {
        // no-op
    }
}
