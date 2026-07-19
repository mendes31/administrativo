<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Onboarding pós-conversão (Expand Fase 4).
 */
final class CreateRhOnboarding extends AbstractMigration
{
    public function up(): void
    {
        $this->createPlanos();
        $this->createItens();
        $this->registerPage();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'RhOnboardingView' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }
        if ($this->hasTable('rh_onboarding_itens')) {
            $this->table('rh_onboarding_itens')->drop()->save();
        }
        if ($this->hasTable('rh_onboarding_planos')) {
            $this->table('rh_onboarding_planos')->drop()->save();
        }
    }

    private function createPlanos(): void
    {
        if ($this->hasTable('rh_onboarding_planos')) {
            return;
        }

        $this->table('rh_onboarding_planos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_conversao_id', 'integer', ['signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('rh_candidato_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'em_andamento'])
            ->addColumn('data_inicio', 'date', ['null' => true])
            ->addColumn('data_limite', 'date', ['null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_conversao_id'], ['unique' => true, 'name' => 'uq_rh_onboarding_conversao'])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_onboarding_user'])
            ->addIndex(['status'], ['name' => 'idx_rh_onboarding_status'])
            ->create();
    }

    private function createItens(): void
    {
        if ($this->hasTable('rh_onboarding_itens')) {
            return;
        }

        $this->table('rh_onboarding_itens', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_onboarding_plano_id', 'integer', ['signed' => false])
            ->addColumn('codigo', 'string', ['limit' => 50])
            ->addColumn('titulo', 'string', ['limit' => 180])
            ->addColumn('obrigatorio', 'boolean', ['default' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'pendente'])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('completed_at', 'datetime', ['null' => true])
            ->addColumn('completed_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rh_onboarding_plano_id'], ['name' => 'idx_rh_onboarding_itens_plano'])
            ->addIndex(['rh_onboarding_plano_id', 'codigo'], [
                'unique' => true,
                'name' => 'uq_rh_onboarding_itens_codigo',
            ])
            ->create();
    }

    private function registerPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'RhOnboardingView';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($existing) {
            $this->grantPageToRhVagasLevels((int) $existing['id']);
            return;
        }

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO adms_pages
                (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                 adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
             VALUES ('
            . $conn->quote('Onboarding do Colaborador') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('rh-onboarding-view') . ', '
            . $conn->quote('rh') . ', '
            . $conn->quote('Checklist de onboarding após conversão de oferta.') . ', '
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
            $this->grantPageToRhVagasLevels((int) $page['id']);
        }
    }

    private function grantPageToRhVagasLevels(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
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
