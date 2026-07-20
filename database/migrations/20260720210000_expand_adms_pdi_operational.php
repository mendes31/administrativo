<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * PDI operacional (Expand Fase 5): FKs + páginas ACL.
 */
final class ExpandAdmsPdiOperational extends AbstractMigration
{
    public function up(): void
    {
        $this->expandPlans();
        $this->expandCompetencies();
        $this->expandActionsTrainingFk();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach (['ListPdiPlans', 'CreatePdiPlan', 'ViewPdiPlan', 'UpdatePdiPlan'] as $controller) {
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

        if ($this->hasTable('adms_pdi_actions')
            && $this->table('adms_pdi_actions')->hasForeignKey('training_id')
        ) {
            $this->table('adms_pdi_actions')->dropForeignKey('training_id')->update();
        }

        if ($this->hasTable('adms_pdi_competencies')
            && $this->table('adms_pdi_competencies')->hasColumn('competency_id')
        ) {
            $t = $this->table('adms_pdi_competencies');
            if ($t->hasForeignKey('competency_id')) {
                $t->dropForeignKey('competency_id');
            }
            $t->removeColumn('competency_id')->update();
        }

        if ($this->hasTable('adms_pdi_plans')
            && $this->table('adms_pdi_plans')->hasColumn('performance_cycle_id')
        ) {
            $t = $this->table('adms_pdi_plans');
            if ($t->hasForeignKey('performance_cycle_id')) {
                $t->dropForeignKey('performance_cycle_id');
            }
            $t->removeColumn('performance_cycle_id')->update();
        }
    }

    private function expandPlans(): void
    {
        if (!$this->hasTable('adms_pdi_plans')) {
            return;
        }
        $table = $this->table('adms_pdi_plans');
        if ($table->hasColumn('performance_cycle_id')) {
            return;
        }
        if (!$this->hasTable('adms_performance_cycles')) {
            return;
        }

        $table
            ->addColumn('performance_cycle_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'evaluation_id',
            ])
            ->addIndex(['performance_cycle_id'], ['name' => 'idx_pdi_plans_cycle'])
            ->addForeignKey('performance_cycle_id', 'adms_performance_cycles', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }

    private function expandCompetencies(): void
    {
        if (!$this->hasTable('adms_pdi_competencies')) {
            return;
        }
        $table = $this->table('adms_pdi_competencies');
        if ($table->hasColumn('competency_id')) {
            return;
        }
        if (!$this->hasTable('adms_competencies')) {
            return;
        }

        $table
            ->addColumn('competency_id', 'integer', [
                'signed' => false,
                'null' => true,
                'after' => 'pdi_plan_id',
            ])
            ->addIndex(['competency_id'], ['name' => 'idx_pdi_comp_catalog'])
            ->addForeignKey('competency_id', 'adms_competencies', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }

    private function expandActionsTrainingFk(): void
    {
        if (!$this->hasTable('adms_pdi_actions') || !$this->hasTable('adms_trainings')) {
            return;
        }
        $table = $this->table('adms_pdi_actions');
        if ($table->hasForeignKey('training_id')) {
            return;
        }
        if (!$table->hasColumn('training_id')) {
            return;
        }

        $table
            ->addForeignKey('training_id', 'adms_trainings', 'id', [
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
            ['Listar PDIs', 'ListPdiPlans', 'list-pdi-plans', 'Listagem de planos de desenvolvimento individual.'],
            ['Criar PDI', 'CreatePdiPlan', 'create-pdi-plan', 'Formulário para criar PDI.'],
            ['Visualizar PDI', 'ViewPdiPlan', 'view-pdi-plan', 'Detalhe do PDI com ações e competências.'],
            ['Editar PDI', 'UpdatePdiPlan', 'update-pdi-plan', 'Editar plano PDI.'],
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
                . $conn->quote('pdi') . ', '
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
