<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Invalida cache de permissões após incluir TimelineFeaturePost (sessões antigas).
 */
final class BumpMenuCacheTimelineFeature extends AbstractMigration
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
