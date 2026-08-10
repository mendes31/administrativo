<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Reaplica ACL de TiAcessosCreate → TiAcessosUpdate para níveis que
 * liberam acesso mas ainda não têm a página de edição (ex.: Create
 * concedida depois da migration 20260810150000).
 */
final class SyncTiAcessosUpdateAclFromCreate extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosUpdate' LIMIT 1"
        );
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosCreate' LIMIT 1"
        );
        if (!$page || !$ref) {
            return;
        }

        $pageId = (int) $page['id'];
        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT alp.permission, alp.adms_access_level_id, {$pageId}, " . $conn->quote($now) . ', ' . $conn->quote($now) . "
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND NOT EXISTS (
                   SELECT 1 FROM adms_access_levels_pages x
                   WHERE x.adms_access_level_id = alp.adms_access_level_id
                     AND x.adms_page_id = {$pageId}
               )"
        );

        $cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt',
            (string) time()
        );
    }

    public function down(): void
    {
        // Não remove ACL já concedida.
    }
}
