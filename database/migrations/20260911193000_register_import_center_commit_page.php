<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Botão "Registrar importação" após simulação (ACL alinhada ao mapeamento).
 */
final class RegisterImportCenterCommitPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterCommit' LIMIT 1");
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
            'name' => 'Registrar importação (após simulação)',
            'controller' => 'ImportCenterCommit',
            'controller_url' => 'import-center-commit',
            'directory' => 'imports',
            'obs' => 'Grava no banco um job que foi apenas simulado, reusando arquivo e mapeamento.',
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $gid,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        if ($this->hasTable('adms_access_levels_pages')) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterCommit' LIMIT 1");
            $map = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterMap' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                $this->execute(
                    "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$pid}, '{$now}', '{$now}'
                     FROM adms_access_levels al"
                );
                $mapId = (int) ($map['id'] ?? 0);
                if ($mapId > 0) {
                    $this->execute(
                        "UPDATE adms_access_levels_pages dest
                         INNER JOIN adms_access_levels_pages src
                             ON src.adms_access_level_id = dest.adms_access_level_id
                            AND src.adms_page_id = {$mapId}
                         SET dest.permission = src.permission, dest.updated_at = '{$now}'
                         WHERE dest.adms_page_id = {$pid}"
                    );
                }
            }
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ImportCenterCommit' LIMIT 1");
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
