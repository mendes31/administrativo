<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Exportação PDF do log de download de currículos (complemento UI/auditoria).
 */
final class RegisterRhCandidatoAnexoAccessLogsPdfExport extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'ExportRhCandidatoAnexoAccessLogsPdf';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListRhCandidatoAnexoAccessLogs' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        if (!$existing) {
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('Exportar log download currículos PDF') . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote('export-rh-candidato-anexo-access-logs-pdf') . ', '
                . $conn->quote('rh') . ', '
                . $conn->quote('Exportação PDF do log de download de currículos (mesmos filtros da listagem).') . ', '
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

        if ($existing) {
            $this->grantToExcelLevels((int) $existing['id']);
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

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ExportRhCandidatoAnexoAccessLogsPdf' LIMIT 1"
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

    private function grantToExcelLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ExportRhCandidatoAnexoAccessLogsExcel' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'ListLogAcessos' LIMIT 1"
            );
        }
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 1, alp.adms_access_level_id, {$pageId}, '{$now}', '{$now}'
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
        );
    }
}
