<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * UI/exportação do log de download de currículos (Fase 0 Expand).
 * Permissão própria para DPO/auditoria — concedida a quem já tem ListLogAcessos.
 */
final class RegisterRhCandidatoAnexoAccessLogsPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhCandidatos' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        $pages = [
            [
                'name' => 'Log download currículos',
                'controller' => 'ListRhCandidatoAnexoAccessLogs',
                'controller_url' => 'list-rh-candidato-anexo-access-logs',
                'obs' => 'Auditoria LGPD: listagem de downloads de anexos/currículos de candidatos.',
            ],
            [
                'name' => 'Exportar log download currículos Excel',
                'controller' => 'ExportRhCandidatoAnexoAccessLogsExcel',
                'controller_url' => 'export-rh-candidato-anexo-access-logs-excel',
                'obs' => 'Exportação Excel do log de download de currículos (mesmos filtros da listagem).',
            ],
        ];

        foreach ($pages as $page) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
            );
            if (!$existing) {
                $this->execute(
                    'INSERT INTO adms_pages
                        (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                         adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                     VALUES ('
                    . $conn->quote($page['name']) . ', '
                    . $conn->quote($page['controller']) . ', '
                    . $conn->quote($page['controller_url']) . ', '
                    . $conn->quote('rh') . ', '
                    . $conn->quote($page['obs']) . ', '
                    . '0, 0, 1, 1, '
                    . $groupId . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now)
                    . ')'
                );
                $existing = $this->fetchRow(
                    'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
                );
            }
            if ($existing) {
                $this->grantPageToListLogAcessosLevels((int) $existing['id']);
            }
        }
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        foreach (['ListRhCandidatoAnexoAccessLogs', 'ExportRhCandidatoAnexoAccessLogsExcel'] as $controller) {
            $page = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = " . $this->getAdapter()->getConnection()->quote($controller) . " LIMIT 1"
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pageId);
            }
            $this->execute('DELETE FROM adms_pages WHERE id = ' . $pageId);
        }
    }

    private function grantPageToListLogAcessosLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ListLogAcessos' LIMIT 1"
        );
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
