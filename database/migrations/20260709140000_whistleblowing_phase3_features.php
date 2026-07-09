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
        $groupId = $groupId ? (int) $groupId['id'] : null;

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
            $row = [
                'name' => $page['name'],
                'controller' => $page['controller'],
                'controller_url' => $page['controller_url'],
                'directory' => $page['directory'],
                'public_page' => $page['public_page'],
                'page_status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($groupId) {
                $row['group_id'] = $groupId;
            }
            $this->table('adms_pages')->insert($row)->saveData();
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
                $exists = $this->fetchRow(
                    "SELECT id FROM adms_access_levels_pages WHERE adms_access_level_id = {$levelId} AND page_id = {$pageId} LIMIT 1"
                );
                if ($exists) {
                    continue;
                }
                $this->table('adms_access_levels_pages')->insert([
                    'adms_access_level_id' => $levelId,
                    'page_id' => $pageId,
                    'permission' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->saveData();
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
