<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 3: identificação voluntária do denunciante + páginas de exportação.
 * Distinto de WhistleblowingReportCategories (classificações) e WhistleblowingRetentionRuns (log de cron).
 */
final class WhistleblowingPhase3Features extends AbstractMigration
{
    public function up(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $table = $this->table('adms_whistleblowing_reports');
            if (!$table->hasColumn('is_reporter_identified')) {
                $table->addColumn('is_reporter_identified', 'boolean', [
                    'default' => 0,
                    'null' => false,
                    'after' => 'content_encrypted',
                    'comment' => '1 = denunciante optou por se identificar voluntariamente',
                ]);
            }
            if (!$table->hasColumn('reporter_contact_encrypted')) {
                $table->addColumn('reporter_contact_encrypted', 'text', [
                    'null' => true,
                    'after' => 'is_reporter_identified',
                    'comment' => 'JSON cifrado: name, email, phone (opcional)',
                ]);
            }
            $table->update();
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupRow = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$groupRow) {
            $groupRow = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE '%Denúncia%' OR name LIKE '%Whistle%' LIMIT 1"
            );
        }
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];
        $now = date('Y-m-d H:i:s');
        $q = fn (string $value): string => $this->getAdapter()->getConnection()->quote($value);

        $pages = [
            [
                'name' => 'Exportar auditoria denúncia',
                'controller' => 'WhistleblowingExportAccessLog',
                'controller_url' => 'whistleblowing-export-access-log',
            ],
            [
                'name' => 'Exportar dashboard denúncias',
                'controller' => 'WhistleblowingExportDashboard',
                'controller_url' => 'whistleblowing-export-dashboard',
            ],
        ];

        foreach ($pages as $page) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $q($page['controller']) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }

            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $q($page['name']) . ', '
                . $q($page['controller']) . ', '
                . $q($page['controller_url']) . ", "
                . $q('whistleblowing') . ", "
                . $q('') . ', 0, 0, 1, 1, '
                . $gid . ', '
                . $q($now) . ', '
                . $q($now)
                . ')'
            );
        }

        $this->grantExportPagesToWhistleblowingLevels();
    }

    private function grantExportPagesToWhistleblowingLevels(): void
    {
        if (!$this->hasTable('adms_access_levels') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $controllers = ['WhistleblowingExportAccessLog', 'WhistleblowingExportDashboard'];
        $now = date('Y-m-d H:i:s');

        foreach (['Canal de Denúncias — Operador', 'Canal de Denúncias — Administrador'] as $levelName) {
            $level = $this->fetchRow(
                'SELECT id FROM adms_access_levels WHERE name = ' . $this->getAdapter()->getConnection()->quote($levelName) . ' LIMIT 1'
            );
            if (!$level) {
                continue;
            }
            $levelId = (int) $level['id'];
            foreach ($controllers as $controller) {
                $page = $this->fetchRow(
                    'SELECT id FROM adms_pages WHERE controller = ' . $this->getAdapter()->getConnection()->quote($controller) . ' LIMIT 1'
                );
                if (!$page) {
                    continue;
                }
                $pageId = (int) $page['id'];
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     VALUES (1, {$levelId}, {$pageId}, '{$now}', '{$now}')
                     ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
                );
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('adms_whistleblowing_reports')) {
            $table = $this->table('adms_whistleblowing_reports');
            if ($table->hasColumn('reporter_contact_encrypted')) {
                $table->removeColumn('reporter_contact_encrypted');
            }
            if ($table->hasColumn('is_reporter_identified')) {
                $table->removeColumn('is_reporter_identified');
            }
            $table->update();
        }

        $this->execute(
            "DELETE FROM adms_pages WHERE controller IN ('WhistleblowingExportAccessLog', 'WhistleblowingExportDashboard')"
        );
    }
}
