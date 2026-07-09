<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 3: identificação voluntária do denunciante + páginas de exportação.
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

        $groupId = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name LIKE '%Denúncia%' OR name LIKE '%Whistle%' LIMIT 1"
        );
        $gid = $groupId ? (int) $groupId['id'] : 1;
        $now = date('Y-m-d H:i:s');

        $pages = [
            [
                'name' => 'Exportar auditoria denúncia',
                'controller' => 'WhistleblowingExportAccessLog',
                'controller_url' => 'whistleblowing-export-access-log',
                'directory' => 'whistleblowing',
                'public_page' => 0,
            ],
            [
                'name' => 'Exportar dashboard denúncias',
                'controller' => 'WhistleblowingExportDashboard',
                'controller_url' => 'whistleblowing-export-dashboard',
                'directory' => 'whistleblowing',
                'public_page' => 0,
            ],
        ];

        foreach ($pages as $page) {
            $exists = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '" . $page['controller'] . "' LIMIT 1"
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $page['name'],
                'controller' => $page['controller'],
                'controller_url' => $page['controller_url'],
                'directory' => $page['directory'],
                'obs' => '',
                'public_page' => $page['public_page'],
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
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
