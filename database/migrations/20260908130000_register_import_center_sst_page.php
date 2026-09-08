<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Permissão única dos tipos SST na Central de Importações (ACL fechada).
 */
final class RegisterImportCenterSstPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterSst' LIMIT 1");
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');
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

        $this->table('adms_pages')->insert([
            'name' => 'Importar SST (Central)',
            'controller' => 'ImportCenterSst',
            'controller_url' => 'import-center-sst',
            'directory' => 'imports',
            'obs' => 'Permissão única dos tipos de importação do módulo Segurança e Medicina (catálogos e matrizes).',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        if ($this->hasTable('adms_access_levels_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterSst' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                $this->execute(
                    "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$pid}, '{$now}', '{$now}'
                     FROM adms_access_levels al"
                );
            }
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterSst' LIMIT 1");
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
