<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Talent pool / HiPo por ciclo (Expand Fase 5).
 */
final class CreateAdmsTalentNominations extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach ([
            'ListTalentNominations',
            'CreateTalentNomination',
            'ViewTalentNomination',
            'UpdateTalentNomination',
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

        if ($this->hasTable('adms_talent_nominations')) {
            $this->table('adms_talent_nominations')->drop()->save();
        }
    }

    private function createTable(): void
    {
        if ($this->hasTable('adms_talent_nominations')) {
            return;
        }

        $this->table('adms_talent_nominations', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('performance_cycle_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('nine_box', 'integer', ['signed' => false, 'null' => true, 'comment' => '1-9'])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'active',
                'comment' => 'active|removed',
            ])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('nominated_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['user_id', 'performance_cycle_id'], [
                'unique' => true,
                'name' => 'uq_talent_user_cycle',
            ])
            ->addIndex(['performance_cycle_id'], ['name' => 'idx_talent_cycle'])
            ->addIndex(['status'], ['name' => 'idx_talent_status'])
            ->addForeignKey('user_id', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('performance_cycle_id', 'adms_performance_cycles', 'id', [
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
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListPerformanceCalibrations' LIMIT 1"
        );
        if (!$group || empty($group['adms_groups_page_id'])) {
            $group = $this->fetchRow(
                "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'ListPerformanceGoals' LIMIT 1"
            );
        }
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $pages = [
            ['Listar Talent Pool', 'ListTalentNominations', 'list-talent-nominations', 'Nomeações HiPo por ciclo.'],
            ['Nomear Talent Pool', 'CreateTalentNomination', 'create-talent-nomination', 'Criar nomeação HiPo.'],
            ['Visualizar Nomeação', 'ViewTalentNomination', 'view-talent-nomination', 'Detalhe da nomeação.'],
            ['Editar Nomeação', 'UpdateTalentNomination', 'update-talent-nomination', 'Editar/remover nomeação HiPo.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                $this->grantPageToCalibrationLevels((int) $existing['id']);
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
                $this->grantPageToCalibrationLevels((int) $page['id']);
            }
        }
    }

    private function grantPageToCalibrationLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'ListPerformanceCalibrations' LIMIT 1"
        );
        if (!$ref) {
            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'ListPerformanceGoals' LIMIT 1"
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
             WHERE alp.adms_page_id = {$refId} AND alp.permission = 1
             AND NOT EXISTS (
                 SELECT 1 FROM adms_access_levels_pages x
                 WHERE x.adms_access_level_id = alp.adms_access_level_id AND x.adms_page_id = {$pageId}
             )"
        );
    }
}
