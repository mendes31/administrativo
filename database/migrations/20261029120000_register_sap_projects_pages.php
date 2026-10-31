<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Páginas ACL: Dashboard e Relatórios SAP de Projetos (Gerenciamento de Projetos SAP B1)
 * e o endpoint JSON/XLSX. Grupo e ACL copiados de ListProjects.
 */
final class RegisterSapProjectsPages extends AbstractMigration
{
    private const PAGES = [
        [
            'controller' => 'SapProjectsDashboard',
            'url' => 'sap-projects-dashboard',
            'name' => 'Dashboard SAP de Projetos',
            'obs' => 'Totais dos projetos do Gerenciamento de Projetos SAP B1 (OPMG/PMG4), consulta ao vivo.',
        ],
        [
            'controller' => 'SapProjectsReports',
            'url' => 'sap-projects-reports',
            'name' => 'Relatórios SAP de Projetos',
            'obs' => 'Relatórios de projetos, documentos, itens e períodos do SAP B1 com exportação Excel/CSV.',
        ],
        [
            'controller' => 'SapProjectsData',
            'url' => 'sap-projects-data',
            'name' => 'API Projetos SAP',
            'obs' => 'Endpoint JSON/XLSX do Dashboard e Relatórios SAP de Projetos.',
        ],
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $ref = $this->fetchRow(
            "SELECT id, adms_groups_page_id AS gid FROM adms_pages WHERE controller = 'ListProjects' LIMIT 1"
        );
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];
        $gid = (int) $ref['gid'];

        foreach (self::PAGES as $page) {
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
                    . $conn->quote($page['url']) . ', '
                    . $conn->quote('projects') . ', '
                    . $conn->quote($page['obs']) . ', '
                    . '0, 0, 1, 1, '
                    . $gid . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now)
                    . ')'
                );
            }

            $row = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
            );
            if ($row && $this->hasTable('adms_access_levels_pages')) {
                $pageId = (int) $row['id'];
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT alp.permission, alp.adms_access_level_id, {$pageId}, "
                    . $conn->quote($now) . ', ' . $conn->quote($now) . "
                     FROM adms_access_levels_pages alp
                     WHERE alp.adms_page_id = {$refId}
                       AND NOT EXISTS (
                           SELECT 1 FROM adms_access_levels_pages x
                           WHERE x.adms_access_level_id = alp.adms_access_level_id
                             AND x.adms_page_id = {$pageId}
                       )"
                );
            }
        }

        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $conn = $this->getAdapter()->getConnection();
        foreach (self::PAGES as $page) {
            $row = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($page['controller']) . ' LIMIT 1'
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        $this->bumpMenuCache();
    }

    private function bumpMenuCache(): void
    {
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
}
