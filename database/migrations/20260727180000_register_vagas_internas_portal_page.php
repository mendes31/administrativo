<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Portal autenticado de vagas internas (listagem + candidatura).
 * Concede aos mesmos níveis que EmployeePortal.
 */
final class RegisterVagasInternasPortalPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'VagasInternas';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );

        $refPortal = $this->fetchRow(
            "SELECT id, adms_groups_page_id FROM adms_pages WHERE controller = 'EmployeePortal' LIMIT 1"
        );
        $groupId = (int) ($refPortal['adms_groups_page_id'] ?? 0);
        if ($groupId <= 0) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
            );
            $groupId = (int) ($group['adms_groups_page_id'] ?? 30);
        }

        $now = date('Y-m-d H:i:s');
        if (!$existing) {
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('Vagas internas (colaborador)') . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote('vagas-internas') . ', '
                . $conn->quote('portal') . ', '
                . $conn->quote('Listagem e candidatura autenticada para vagas com divulgação interna/ambas.') . ', '
                . '0, 0, 1, 1, '
                . $groupId . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
        }

        if ($existing && $refPortal && $this->hasTable('adms_access_levels_pages')) {
            $newId = (int) $existing['id'];
            $refId = (int) $refPortal['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT 1, alp.adms_access_level_id, {$newId}, '{$now}', '{$now}'
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refId}
                   AND alp.permission = 1
                 ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
            );
        }

        if (class_exists(\App\adms\Models\Repository\MenuPermissionUserRepository::class)) {
            \App\adms\Models\Repository\MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $conn = $this->getAdapter()->getConnection();
        $page = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote('VagasInternas') . ' LIMIT 1'
        );
        if (!$page) {
            return;
        }
        $pageId = (int) $page['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
        }
        $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
    }
}
