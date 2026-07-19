<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Ciclos de desempenho (Expand Fase 5) + FK opcional em metas/avaliações.
 */
final class CreateAdmsPerformanceCycles extends AbstractMigration
{
    public function up(): void
    {
        $this->createCyclesTable();
        $this->addCycleFkToGoals();
        $this->addCycleFkToReviews();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach ([
            'ListPerformanceCycles',
            'CreatePerformanceCycle',
            'ViewPerformanceCycle',
            'UpdatePerformanceCycle',
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

        if ($this->hasTable('adms_performance_goals')
            && $this->table('adms_performance_goals')->hasColumn('performance_cycle_id')
        ) {
            $this->table('adms_performance_goals')
                ->dropForeignKey('performance_cycle_id')
                ->removeColumn('performance_cycle_id')
                ->update();
        }

        if ($this->hasTable('adms_performance_reviews')
            && $this->table('adms_performance_reviews')->hasColumn('performance_cycle_id')
        ) {
            $this->table('adms_performance_reviews')
                ->dropForeignKey('performance_cycle_id')
                ->removeColumn('performance_cycle_id')
                ->update();
        }

        if ($this->hasTable('adms_performance_cycles')) {
            $this->table('adms_performance_cycles')->drop()->save();
        }
    }

    private function createCyclesTable(): void
    {
        if ($this->hasTable('adms_performance_cycles')) {
            return;
        }

        $this->table('adms_performance_cycles', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('year', 'integer', ['signed' => false])
            ->addColumn('period_start', 'date', ['null' => false])
            ->addColumn('period_end', 'date', ['null' => false])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'default' => 'draft',
                'comment' => 'draft|open|closed',
            ])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('created_by', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['year'], ['name' => 'idx_perf_cycles_year'])
            ->addIndex(['status'], ['name' => 'idx_perf_cycles_status'])
            ->addIndex(['period_start', 'period_end'], ['name' => 'idx_perf_cycles_period'])
            ->addForeignKey('created_by', 'adms_users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }

    private function addCycleFkToGoals(): void
    {
        if (!$this->hasTable('adms_performance_goals')) {
            return;
        }
        $table = $this->table('adms_performance_goals');
        if ($table->hasColumn('performance_cycle_id')) {
            return;
        }

        $table
            ->addColumn('performance_cycle_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'performance_review_id',
                'comment' => 'Ciclo de desempenho (opcional)',
            ])
            ->addIndex(['performance_cycle_id'], ['name' => 'idx_perf_goals_cycle'])
            ->addForeignKey('performance_cycle_id', 'adms_performance_cycles', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }

    private function addCycleFkToReviews(): void
    {
        if (!$this->hasTable('adms_performance_reviews')) {
            return;
        }
        $table = $this->table('adms_performance_reviews');
        if ($table->hasColumn('performance_cycle_id')) {
            return;
        }

        $table
            ->addColumn('performance_cycle_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'review_type',
                'comment' => 'Ciclo de desempenho (opcional)',
            ])
            ->addIndex(['performance_cycle_id'], ['name' => 'idx_perf_reviews_cycle'])
            ->addForeignKey('performance_cycle_id', 'adms_performance_cycles', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
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
            ['Listar Ciclos de Desempenho', 'ListPerformanceCycles', 'list-performance-cycles', 'Listagem de ciclos de desempenho.'],
            ['Criar Ciclo de Desempenho', 'CreatePerformanceCycle', 'create-performance-cycle', 'Formulário para criar ciclo.'],
            ['Visualizar Ciclo de Desempenho', 'ViewPerformanceCycle', 'view-performance-cycle', 'Detalhe do ciclo.'],
            ['Editar Ciclo de Desempenho', 'UpdatePerformanceCycle', 'update-performance-cycle', 'Editar ciclo de desempenho.'],
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
