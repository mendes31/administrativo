<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Cargos críticos e sucessores (Expand Fase 5).
 */
final class CreateAdmsSuccessionTables extends AbstractMigration
{
    public function up(): void
    {
        $this->createCriticalPositions();
        $this->createSuccessors();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach ([
            'ListCriticalPositions',
            'CreateCriticalPosition',
            'ViewCriticalPosition',
            'UpdateCriticalPosition',
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

        if ($this->hasTable('adms_succession_successors')) {
            $this->table('adms_succession_successors')->drop()->save();
        }
        if ($this->hasTable('adms_critical_positions')) {
            $this->table('adms_critical_positions')->drop()->save();
        }
    }

    private function createCriticalPositions(): void
    {
        if ($this->hasTable('adms_critical_positions')) {
            return;
        }

        $this->table('adms_critical_positions', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('position_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('risk_level', 'string', [
                'limit' => 20,
                'default' => 'medium',
                'comment' => 'high|medium|low',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'active',
                'comment' => 'active|inactive',
            ])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['position_id'], ['unique' => true, 'name' => 'uq_critical_position'])
            ->addIndex(['status'], ['name' => 'idx_critical_status'])
            ->addForeignKey('position_id', 'adms_positions', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }

    private function createSuccessors(): void
    {
        if ($this->hasTable('adms_succession_successors')) {
            return;
        }

        $this->table('adms_succession_successors', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('critical_position_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('readiness', 'string', [
                'limit' => 20,
                'default' => 'ready_1_2y',
                'comment' => 'ready_now|ready_1_2y|ready_3y|emergency',
            ])
            ->addColumn('priority_order', 'integer', ['signed' => false, 'default' => 1])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('nominated_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['critical_position_id', 'user_id'], [
                'unique' => true,
                'name' => 'uq_successor_position_user',
            ])
            ->addIndex(['critical_position_id'], ['name' => 'idx_successor_critical'])
            ->addForeignKey('critical_position_id', 'adms_critical_positions', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('user_id', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('nominated_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
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
        foreach (['ListTalentNominations', 'ListPerformanceCalibrations', 'ListPerformanceGoals'] as $refCtrl) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id FROM adms_pages WHERE controller = '{$refCtrl}' LIMIT 1"
            );
            if ($group && !empty($group['adms_groups_page_id'])) {
                $groupId = (int) $group['adms_groups_page_id'];
                break;
            }
        }

        $pages = [
            ['Listar Sucessão', 'ListCriticalPositions', 'list-critical-positions', 'Cargos críticos e sucessores.'],
            ['Criar Cargo Crítico', 'CreateCriticalPosition', 'create-critical-position', 'Marcar cargo como crítico.'],
            ['Visualizar Sucessão', 'ViewCriticalPosition', 'view-critical-position', 'Detalhe e sucessores.'],
            ['Editar Cargo Crítico', 'UpdateCriticalPosition', 'update-critical-position', 'Editar cargo crítico.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                $this->grantPage((int) $existing['id']);
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
                $this->grantPage((int) $page['id']);
            }
        }
    }

    private function grantPage(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = null;
        foreach (['ListTalentNominations', 'ListPerformanceCalibrations', 'ListPerformanceGoals'] as $ctrl) {
            $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$ctrl}' LIMIT 1");
            if ($ref) {
                break;
            }
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
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
