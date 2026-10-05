<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Permissão do tipo RustDesk na Central de Importações (ACL fechada).
 * Quem já tinha TiRustdeskCreate ou ImportCenter recebe a página ligada.
 */
final class RegisterImportCenterTiPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterTi' LIMIT 1");
        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Administração - Importações' LIMIT 1");
        if (!$groupRow) {
            $ref = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ImportCenter' LIMIT 1");
            $gid = (int) ($ref['adms_groups_page_id'] ?? 0);
        } else {
            $gid = (int) $groupRow['id'];
        }
        if ($gid <= 0) {
            return;
        }

        if (!$exists) {
            $this->table('adms_pages')->insert([
                'name' => 'Importar RustDesk (Central)',
                'controller' => 'ImportCenterTi',
                'controller_url' => 'import-center-ti',
                'directory' => 'imports',
                'obs' => 'Permissão do tipo TI — RustDesk na Central de Importações.',
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterTi' LIMIT 1");
        if (!$page || !$this->hasTable('adms_access_levels_pages')) {
            MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
            return;
        }
        $pageId = (int) $page['id'];

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );

        $refIds = [];
        foreach (['TiRustdeskCreate', 'ImportCenter'] as $controller) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1"
            );
            if ($ref) {
                $refIds[] = (int) $ref['id'];
            }
        }
        if ($refIds !== []) {
            $in = implode(',', $refIds);
            $this->execute(
                "UPDATE adms_access_levels_pages dest
                 INNER JOIN adms_access_levels_pages src
                    ON src.adms_access_level_id = dest.adms_access_level_id
                   AND src.permission = 1
                   AND src.adms_page_id IN ({$in})
                 SET dest.permission = 1, dest.updated_at = '{$now}'
                 WHERE dest.adms_page_id = {$pageId}"
            );
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterTi' LIMIT 1");
        if (!$row) {
            return;
        }
        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
