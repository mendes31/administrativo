<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Formação de Preço: cenários nomeados com os três métodos comerciais.
 * ACL copiada de CrmDashboard quando existir; demais níveis nascem fechados.
 */
final class CreateFormacaoPreco extends AbstractMigration
{
    public function up(): void
    {
        $this->createTables();
        $this->registerPages();
    }

    public function down(): void
    {
        $controllers = [
            'FormacaoPreco',
            'FormacaoPrecoCreate',
            'FormacaoPrecoUpdate',
            'FormacaoPrecoView',
            'FormacaoPrecoDelete',
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

        if ($this->hasTable('formacao_preco_itens')) {
            $this->table('formacao_preco_itens')->drop()->save();
        }
        if ($this->hasTable('formacao_precos')) {
            $this->table('formacao_precos')->drop()->save();
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    private function createTables(): void
    {
        if (!$this->hasTable('formacao_precos')) {
            $this->table('formacao_precos', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
                ->addColumn('nome', 'string', ['limit' => 180])
                ->addColumn('observacao', 'text', ['null' => true])
                ->addColumn('icms_padrao', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0.120000'])
                ->addColumn('pis_padrao', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0.016500'])
                ->addColumn('cofins_padrao', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0.076000'])
                ->addColumn('created_by_user_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('updated_by_user_id', 'integer', ['signed' => false, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['nome'], ['name' => 'idx_formacao_precos_nome'])
                ->create();
        }

        if (!$this->hasTable('formacao_preco_itens')) {
            $this->table('formacao_preco_itens', [
                'id' => false,
                'primary_key' => ['id'],
                'engine' => 'InnoDB',
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
                ->addColumn('formacao_preco_id', 'integer', ['signed' => false])
                ->addColumn('ordem', 'integer', ['signed' => false, 'default' => 1])
                ->addColumn('codigo_produto', 'string', ['limit' => 40, 'null' => true])
                ->addColumn('produto', 'string', ['limit' => 255])
                ->addColumn('forecast', 'decimal', ['precision' => 18, 'scale' => 4, 'null' => true])
                ->addColumn('icms', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('pis', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('cofins', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('custo_variavel', 'decimal', ['precision' => 18, 'scale' => 6, 'default' => '0'])
                ->addColumn('custo_fixo', 'decimal', ['precision' => 18, 'scale' => 6, 'default' => '0'])
                ->addColumn('meta_pb', 'decimal', ['precision' => 10, 'scale' => 6, 'null' => true])
                ->addColumn('meta_rl', 'decimal', ['precision' => 10, 'scale' => 6, 'null' => true])
                ->addColumn('meta_mk', 'decimal', ['precision' => 10, 'scale' => 6, 'null' => true])
                ->addColumn('preco_informado', 'decimal', ['precision' => 18, 'scale' => 6, 'null' => true])
                ->addColumn('frete_pct', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('comissao_pct', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('desconto_pct', 'decimal', ['precision' => 10, 'scale' => 6, 'default' => '0'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['formacao_preco_id'], ['name' => 'idx_fp_itens_header'])
                ->addForeignKey('formacao_preco_id', 'formacao_precos', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ])
                ->create();
        }
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'Comercial - Formação de Preço' LIMIT 1"
        );
        if (!$group) {
            $this->execute(
                "INSERT INTO adms_groups_pages (name, obs, created_at)
                 VALUES ('Comercial - Formação de Preço',
                         'Formação de preço de venda (três métodos comerciais).',
                         " . $conn->quote($now) . ')'
            );
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'Comercial - Formação de Preço' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];

        $pages = [
            ['Formação de Preço', 'FormacaoPreco', 'formacao-preco', 'Listagem das precificações nomeadas.'],
            ['Cadastrar Formação de Preço', 'FormacaoPrecoCreate', 'formacao-preco-create', 'Criar uma precificação nomeada.'],
            ['Editar Formação de Preço', 'FormacaoPrecoUpdate', 'formacao-preco-update', 'Editar produtos e calcular os três métodos.'],
            ['Visualizar Formação de Preço', 'FormacaoPrecoView', 'formacao-preco-view', 'Consultar precificação, comparativo e explicações.'],
            ['Excluir Formação de Preço', 'FormacaoPrecoDelete', 'formacao-preco-delete', 'Excluir uma precificação nomeada.'],
        ];

        foreach ($pages as [$name, $controller, $url, $obs]) {
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
                    . $conn->quote('pricing') . ', '
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
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT 0, al.id, {$pageId}, " . $conn->quote($now) . ', ' . $conn->quote($now) . '
                 FROM adms_access_levels al'
            );

            $ref = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'CrmDashboard' LIMIT 1"
            );
            if ($ref) {
                $refId = (int) $ref['id'];
                $this->execute(
                    "UPDATE adms_access_levels_pages dest
                     INNER JOIN adms_access_levels_pages src
                        ON src.adms_access_level_id = dest.adms_access_level_id
                       AND src.permission = 1
                       AND src.adms_page_id = {$refId}
                     SET dest.permission = 1, dest.updated_at = " . $conn->quote($now) . "
                     WHERE dest.adms_page_id = {$pageId}"
                );
            }
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
