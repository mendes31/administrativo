<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Exportação PDF/Excel do relatório de permissões por nível de acesso.
 */
final class RegisterExportAccessLevelsPermissionsPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $groupId = 3;

        $ref = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListAccessLevels' LIMIT 1");
        if ($ref && !empty($ref['adms_groups_page_id'])) {
            $groupId = (int) $ref['adms_groups_page_id'];
        }

        $this->ensurePage(
            'Exportar Permissões Níveis (PDF)',
            'ExportAccessLevelsPermissionsPdf',
            'export-access-levels-permissions-pdf',
            'accessLevels',
            'Relatório PDF com páginas autorizadas por nível de acesso.',
            $groupId,
            $now
        );

        $this->ensurePage(
            'Exportar Permissões Níveis (Excel)',
            'ExportAccessLevelsPermissionsExcel',
            'export-access-levels-permissions-excel',
            'accessLevels',
            'Relatório Excel com páginas autorizadas por nível de acesso.',
            $groupId,
            $now
        );

        $this->syncPermissionsFromListAccessLevels();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $this->execute(
            "DELETE FROM adms_pages WHERE controller IN (
                'ExportAccessLevelsPermissionsPdf',
                'ExportAccessLevelsPermissionsExcel'
            )"
        );
    }

    private function ensurePage(
        string $name,
        string $controller,
        string $controllerUrl,
        string $directory,
        string $obs,
        int $groupId,
        string $now
    ): void {
        $exists = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
        );
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => $name,
            'controller' => $controller,
            'controller_url' => $controllerUrl,
            'directory' => $directory,
            'obs' => $obs,
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $groupId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
    }

    private function syncPermissionsFromListAccessLevels(): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'ListAccessLevels' LIMIT 1");
        if (!$ref) {
            return;
        }

        $refPageId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');

        foreach (['ExportAccessLevelsPermissionsPdf', 'ExportAccessLevelsPermissionsExcel'] as $controller) {
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . addslashes($controller) . "' LIMIT 1"
            );
            if (!$page) {
                continue;
            }

            $pageId = (int) $page['id'];

            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT alp.permission, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refPageId}
                 ON DUPLICATE KEY UPDATE
                    permission = VALUES(permission),
                    updated_at = VALUES(updated_at)"
            );
        }
    }
}
