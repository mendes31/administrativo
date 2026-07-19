<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Movimentações organizacionais (Expand Fase 4).
 */
final class CreateRhMovimentacoes extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPages();
    }

    public function down(): void
    {
        foreach (['RhMovimentacoes', 'RhMovimentacoesCreate', 'RhMovimentacoesView'] as $controller) {
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
        if ($this->hasTable('rh_movimentacoes')) {
            $this->table('rh_movimentacoes')->drop()->save();
        }
    }

    private function createTable(): void
    {
        if ($this->hasTable('rh_movimentacoes')) {
            return;
        }

        $this->table('rh_movimentacoes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('tipo', 'string', ['limit' => 40])
            ->addColumn('data_vigencia', 'date', ['null' => false])
            ->addColumn('departamento_id_antes', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('departamento_id_depois', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('cargo_id_antes', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('cargo_id_depois', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('gestor_id_antes', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('gestor_id_depois', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('motivo', 'string', ['limit' => 255])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['adms_user_id'], ['name' => 'idx_rh_mov_user'])
            ->addIndex(['tipo'], ['name' => 'idx_rh_mov_tipo'])
            ->addIndex(['data_vigencia'], ['name' => 'idx_rh_mov_vigencia'])
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
            ['Movimentações', 'RhMovimentacoes', 'rh-movimentacoes', 'Listagem de movimentações organizacionais.'],
            ['Registrar Movimentação', 'RhMovimentacoesCreate', 'rh-movimentacoes-create', 'Registrar transferência/promoção/alteração.'],
            ['Visualizar Movimentação', 'RhMovimentacoesView', 'rh-movimentacoes-view', 'Detalhe da movimentação.'],
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
