<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Inventário de IDs RustDesk (TI / Acessos).
 * Senha em AES-256-GCM (coluna senha_encriptada). Páginas no grupo TI - Sistemas e Acessos.
 * ACL copiada das páginas equivalentes de Sistemas.
 */
final class CreateTiRustdesk extends AbstractMigration
{
    public function up(): void
    {
        $this->createTable();
        $this->registerPages();
    }

    public function down(): void
    {
        $controllers = [
            'TiRustdesk',
            'TiRustdeskCreate',
            'TiRustdeskUpdate',
            'TiRustdeskView',
            'TiRustdeskReveal',
        ];
        foreach ($controllers as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1"
            );
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }

        if ($this->hasTable('ti_rustdesk')) {
            $this->table('ti_rustdesk')->drop()->save();
        }

        $this->bumpMenuCache();
    }

    private function createTable(): void
    {
        if ($this->hasTable('ti_rustdesk')) {
            return;
        }

        $this->table('ti_rustdesk', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('alias', 'string', ['limit' => 180])
            ->addColumn('rustdesk_id', 'string', ['limit' => 32])
            ->addColumn('senha_encriptada', 'text', ['null' => true])
            ->addColumn('adms_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'ativo'])
            ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('updated_by_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['rustdesk_id'], ['unique' => true, 'name' => 'uq_ti_rustdesk_id'])
            ->addIndex(['alias'], ['name' => 'idx_ti_rustdesk_alias'])
            ->addIndex(['status'], ['name' => 'idx_ti_rustdesk_status'])
            ->addIndex(['adms_user_id'], ['name' => 'idx_ti_rustdesk_user'])
            ->create();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
        );
        if (!$group) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('TI - Sistemas e Acessos',
                         'Catálogo de sistemas e mapa de acessos (TI / Acessos)',
                         " . $conn->quote($now) . ')'
            );
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $pages = [
            ['RustDesk (TI)', 'TiRustdesk', 'ti-rustdesk', 'Listagem do inventário de IDs RustDesk.', 'TiSistemas'],
            ['Cadastrar RustDesk (TI)', 'TiRustdeskCreate', 'ti-rustdesk-create', 'Cadastrar ID e senha RustDesk.', 'TiSistemasCreate'],
            ['Editar RustDesk (TI)', 'TiRustdeskUpdate', 'ti-rustdesk-update', 'Editar cadastro RustDesk.', 'TiSistemasUpdate'],
            ['Visualizar RustDesk (TI)', 'TiRustdeskView', 'ti-rustdesk-view', 'Detalhe do cadastro RustDesk.', 'TiSistemasView'],
            ['Revelar senha RustDesk (TI)', 'TiRustdeskReveal', 'ti-rustdesk-reveal', 'Descriptografa a senha após confirmar a senha do usuário logado.', 'TiSistemasView'],
        ];

        foreach ($pages as [$name, $controller, $url, $obs, $refController]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if (!$existing) {
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

            if (!$this->hasTable('adms_access_levels_pages')) {
                continue;
            }

            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            $ref = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($refController) . ' LIMIT 1'
            );
            if (!$page || !$ref) {
                continue;
            }
            $pageId = (int) $page['id'];
            $refId = (int) $ref['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT alp.permission, alp.adms_access_level_id, {$pageId}, "
                . $conn->quote($now) . ', ' . $conn->quote($now) . "
                 FROM adms_access_levels_pages alp
                 WHERE alp.adms_page_id = {$refId}
                   AND NOT EXISTS (
                       SELECT 1 FROM adms_access_levels_pages x
                       WHERE x.adms_access_level_id = alp.adms_access_level_id
                         AND x.adms_page_id = {$pageId}
                   )"
            );
        }

        $this->bumpMenuCache();
    }

    private function bumpMenuCache(): void
    {
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
