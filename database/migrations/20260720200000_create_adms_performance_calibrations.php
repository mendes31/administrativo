<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Sessões de calibração por ciclo (Expand Fase 5).
 */
final class CreateAdmsPerformanceCalibrations extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach ([
            'ListPerformanceCalibrations',
            'CreatePerformanceCalibration',
            'ViewPerformanceCalibration',
            'UpdatePerformanceCalibration',
        ] as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        if ($this->hasTable('adms_performance_calibrations')) {
            $this->table('adms_performance_calibrations')->drop()->save();
        }
    }

    private function createTable(): void
    {
        if ($this->hasTable('adms_performance_calibrations')) {
            return;
        }

        $this->table('adms_performance_calibrations', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('performance_cycle_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'draft',
                'comment' => 'draft|open|locked',
            ])
            ->addColumn('session_notes', 'text', ['null' => true])
            ->addColumn('locked_at', 'datetime', ['null' => true])
            ->addColumn('locked_by', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['performance_cycle_id'], [
                'unique' => true,
                'name' => 'uq_perf_calibrations_cycle',
            ])
            ->addIndex(['status'], ['name' => 'idx_perf_calibrations_status'])
            ->addForeignKey('performance_cycle_id', 'adms_performance_cycles', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('locked_by', 'adms_users', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupId = 36;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListPerformanceGoals' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $pages = [
            ['Listar Calibrações', 'ListPerformanceCalibrations', 'list-performance-calibrations', 'Sessões de calibração por ciclo.'],
            ['Criar Calibração', 'CreatePerformanceCalibration', 'create-performance-calibration', 'Abrir sessão de calibração.'],
            ['Visualizar Calibração', 'ViewPerformanceCalibration', 'view-performance-calibration', 'Detalhe da calibração.'],
            ['Editar Calibração', 'UpdatePerformanceCalibration', 'update-performance-calibration', 'Editar notas/status da calibração.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                $this->grantPageToGoalsLevels((int) $existing['id']);
                continue;
            }

            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($name) . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote($url) . ', '
                . $conn->quote('performance') . ', '
                . $conn->quote($obs) . ', '
                . '0, 0, 1, 1, '
                . $groupId . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );

            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($page) {
                $this->grantPageToGoalsLevels((int) $page['id']);
            }
        }
    }

    private function grantPageToGoalsLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ListPerformanceGoals' LIMIT 1"
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
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
