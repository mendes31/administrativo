<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * ListDocumentPositions foi cadastrada com public_page = 1 no seed,
 * o que concedia permission = 1 a todos os níveis.
 *
 * Corrige: página privada, não padrão, permissões zeradas (liberação manual).
 */
final class RevokeListDocumentPositionsOpenPermissions extends AbstractMigration
{
    private const CONTROLLER = 'ListDocumentPositions';

    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $controller = self::CONTROLLER;

        $this->execute(
            "UPDATE adms_pages
             SET public_page = 0,
                 default_page = 0,
                 updated_at = '{$now}'
             WHERE controller = '{$controller}'"
        );

        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute(
                "UPDATE adms_access_levels_pages alp
                 INNER JOIN adms_pages p ON p.id = alp.adms_page_id
                 SET alp.permission = 0,
                     alp.updated_at = '{$now}'
                 WHERE p.controller = '{$controller}'"
            );
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        // Não restaura public_page = 1 (evita reabrir para todos os níveis).
    }
}
