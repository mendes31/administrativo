<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAdmsHeadcountPlans extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPages();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        foreach ([
            'ListHeadcountPlans', 'CreateHeadcountPlan', 'ViewHeadcountPlan', 'UpdateHeadcountPlan',
        ] as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        if ($this->hasTable('adms_headcount_plans')) {
            $this->table('adms_headcount_plans')->drop()->save();
        }
        $this->bumpMenuCache();
    }

    private function createTable(): void
    {
        if ($this->hasTable('adms_headcount_plans')) {
            return;
        }
        $this->table('adms_headcount_plans', [
            'id' => false, 'primary_key' => ['id'], 'engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('department_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('position_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('period_year', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('period_month', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('planned_count', 'integer', ['signed' => false, 'default' => 0])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'draft', 'comment' => 'draft|active|closed'])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(
                ['department_id', 'position_id', 'period_year', 'period_month'],
                ['unique' => true, 'name' => 'uq_headcount_plan_dim']
            )
            ->addIndex(['status'], ['name' => 'idx_headcount_plans_status'])
            ->addIndex(['period_year', 'period_month'], ['name' => 'idx_headcount_plans_period'])
            ->addForeignKey('department_id', 'adms_departments', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('position_id', 'adms_positions', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->addForeignKey('created_by', 'adms_users', 'id', ['delete' => 'RESTRICT', 'update' => 'NO_ACTION'])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $groupId = 36;
        foreach (['PeopleAnalytics', 'ListPulseCampaigns', 'ListPerformanceGoals'] as $ref) {
            $group = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = '{$ref}' LIMIT 1");
            if ($group && !empty($group['adms_groups_page_id'])) {
                $groupId = (int) $group['adms_groups_page_id'];
                break;
            }
        }

        $pages = [
            ['Listar Planejamento de Quadro', 'ListHeadcountPlans', 'list-headcount-plans', 'Quadro planejado vs efetivo.'],
            ['Criar Linha de Quadro', 'CreateHeadcountPlan', 'create-headcount-plan', 'Criar linha de headcount planejado.'],
            ['Visualizar Linha de Quadro', 'ViewHeadcountPlan', 'view-headcount-plan', 'Detalhe e gap efetivo.'],
            ['Editar Linha de Quadro', 'UpdateHeadcountPlan', 'update-headcount-plan', 'Editar/ativar/fechar linha.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');
        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
            if ($existing) {
                $this->grant((int) $existing['id']);
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($name) . ', ' . $conn->quote($controller) . ', ' . $conn->quote($url) . ', '
                . $conn->quote('analytics') . ', ' . $conn->quote($obs) . ', 0, 0, 1, 1, '
                . $groupId . ', ' . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
            );
            $page = $this->fetchRow('SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1');
            if ($page) {
                $this->grant((int) $page['id']);
            }
        }
    }

    private function grant(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }
        $ref = null;
        foreach (['PeopleAnalytics', 'ListPulseCampaigns', 'ListPerformanceGoals'] as $ctrl) {
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

    private function bumpMenuCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }
}
