<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Domínio TI / Acessos — catálogo de sistemas + mapa de acessos (ADR-0008).
 * Páginas no grupo ACL novo; sem concessão em massa.
 */
final class CreateTiSistemasAcessos extends AbstractMigration
{
    public function up(): void
    {
        $this->createSistemas();
        $this->createAcessos();
        $this->registerGroupAndPages();
    }

    public function down(): void
    {
        $controllers = [
            'TiSistemas',
            'TiSistemasCreate',
            'TiSistemasUpdate',
            'TiSistemasView',
            'TiAcessosCreate',
            'TiAcessosRevoke',
        ];
        foreach ($controllers as $controller) {
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

        if ($this->hasTable('ti_acessos')) {
            $this->table('ti_acessos')->drop()->save();
        }
        if ($this->hasTable('ti_sistemas')) {
            $this->table('ti_sistemas')->drop()->save();
        }

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
        );
        if ($group) {
            $gid = (int) $group['id'];
            $still = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE adms_groups_page_id = {$gid} LIMIT 1"
            );
            if (!$still) {
                $this->execute("DELETE FROM adms_groups_pages WHERE id = {$gid}");
            }
        }
    }

    private function createSistemas(): void
    {
        if ($this->hasTable('ti_sistemas')) {
            return;
        }

        $this->table('ti_sistemas', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('codigo', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('nome', 'string', ['limit' => 180])
            ->addColumn('descricao', 'text', ['null' => true])
            ->addColumn('tipo', 'string', ['limit' => 20, 'default' => 'outro'])
            ->addColumn('localizacao', 'string', ['limit' => 180, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo'])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_ti_sistemas_codigo'])
            ->addIndex(['nome'], ['name' => 'idx_ti_sistemas_nome'])
            ->addIndex(['status'], ['name' => 'idx_ti_sistemas_status'])
            ->addIndex(['tipo'], ['name' => 'idx_ti_sistemas_tipo'])
            ->create();
    }

    private function createAcessos(): void
    {
        if ($this->hasTable('ti_acessos')) {
            return;
        }

        $this->table('ti_acessos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('adms_user_id', 'integer', ['signed' => false])
            ->addColumn('ti_sistema_id', 'integer', ['signed' => false])
            ->addColumn('login_externo', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('perfil_obs', 'string', ['limit' => 180, 'null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo'])
            ->addColumn('data_liberacao', 'date', ['null' => true])
            ->addColumn('data_revogacao', 'date', ['null' => true])
            ->addColumn('liberado_por', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('revogado_por', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['adms_user_id'], ['name' => 'idx_ti_acessos_user'])
            ->addIndex(['ti_sistema_id'], ['name' => 'idx_ti_acessos_sistema'])
            ->addIndex(['status'], ['name' => 'idx_ti_acessos_status'])
            ->addIndex(['adms_user_id', 'ti_sistema_id', 'status'], [
                'name' => 'idx_ti_acessos_user_sistema_status',
            ])
            ->create();
    }

    private function registerGroupAndPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existsGroup = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
        );
        if (!$existsGroup) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('TI - Sistemas e Acessos',
                         'Catálogo de sistemas e mapa de acessos (TI / Acessos)',
                         " . $conn->quote($now) . ')'
            );
        }

        $groupRow = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
        );
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $pages = [
            ['Sistemas (TI)', 'TiSistemas', 'ti-sistemas', 'Listagem do catálogo de sistemas.'],
            ['Cadastrar Sistema (TI)', 'TiSistemasCreate', 'ti-sistemas-create', 'Cadastrar sistema/equipamento no inventário de acessos.'],
            ['Editar Sistema (TI)', 'TiSistemasUpdate', 'ti-sistemas-update', 'Editar sistema do catálogo TI.'],
            ['Visualizar Sistema (TI)', 'TiSistemasView', 'ti-sistemas-view', 'Detalhe do sistema e usuários com acesso.'],
            ['Liberar Acesso (TI)', 'TiAcessosCreate', 'ti-acessos-create', 'Vincular colaborador a um sistema.'],
            ['Revogar Acesso (TI)', 'TiAcessosRevoke', 'ti-acessos-revoke', 'Marcar acesso como inativado no mapa.'],
        ];

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
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
                . $conn->quote('ti') . ', '
                . $conn->quote($obs) . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        // Invalida cache de menu em sessão (Super Admin / demais níveis).
        $cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt',
            (string) time()
        );
    }
}
