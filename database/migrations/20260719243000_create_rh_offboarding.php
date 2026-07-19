<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Offboarding com checklist e desligamento em adms_users (Expand Fase 4).
 */
final class CreateRhOffboarding extends AbstractMigration
{
    public function up(): void
    {
        $this->createPlanos();
        $this->createItens();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach (['RhOffboardings', 'RhOffboardingsCreate', 'RhOffboardingsView'] as $controller) {
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
        if ($this->hasTable('rh_offboarding_itens')) {
            $this->table('rh_offboarding_itens')->drop()->save();
        }
        if ($this->hasTable('rh_offboarding_planos')) {
            $this->table('rh_offboarding_planos')->drop()->save();
        }
    }

    private function createPlanos(): void
    {
        if ($this->hasTable('rh_offboarding_planos')) {
            return;
        }

        $this->table('rh_offboarding_planos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('tipo', 'string', ['limit' => 40])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'em_andamento'])
            ->addColumn('data_prevista', 'date', ['null' => true])
            ->addColumn('data_desligamento', 'date', ['null' => true])
            ->addColumn('motivo', 'string', ['limit' => 255])
            ->addColumn('tipo_impacto', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('concluded_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('concluded_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_off_user'])
            ->addIndex(['status'], ['name' => 'idx_rh_off_status'])
            ->addIndex(['tipo'], ['name' => 'idx_rh_off_tipo'])
            ->create();
    }

    private function createItens(): void
    {
        if ($this->hasTable('rh_offboarding_itens')) {
            return;
        }

        $this->table('rh_offboarding_itens', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('rh_offboarding_plano_id', 'integer', ['signed' => false])
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
            ->addIndex(['rh_offboarding_plano_id'], ['name' => 'idx_rh_off_itens_plano'])
            ->addIndex(['rh_offboarding_plano_id', 'codigo'], [
                'unique' => true,
                'name' => 'uq_rh_off_itens_codigo',
            ])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $groupId = 30;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'RhVagas' LIMIT 1"
        );
        if ($group && !empty($group['adms_groups_page_id'])) {
            $groupId = (int) $group['adms_groups_page_id'];
        }

        $pages = [
            ['Offboarding', 'RhOffboardings', 'rh-offboardings', 'Listagem de planos de offboarding.'],
            ['Iniciar Offboarding', 'RhOffboardingsCreate', 'rh-offboardings-create', 'Iniciar checklist de desligamento.'],
            ['Visualizar Offboarding', 'RhOffboardingsView', 'rh-offboardings-view', 'Checklist e conclusão do offboarding.'],
        ];

        $conn = $this->getAdapter()->getConnection();
        $now = date('Y-m-d H:i:s');

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                $this->grantPageToRhVagasLevels((int) $existing['id']);
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
                . $conn->quote('rh') . ', '
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
                $this->grantPageToRhVagasLevels((int) $page['id']);
            }
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
