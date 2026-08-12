<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Canal público LGPD (arts. 18 e 41): amplia solicitações do titular,
 * registra páginas públicas e ACL do backoffice.
 */
final class LgpdPublicPortalAndSolicitacoes extends AbstractMigration
{
    public function up(): void
    {
        $this->expandSolicitacoesTable();
        $this->registerPages();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            foreach (['LgpdSolicitacoesTitularesView', 'LgpdSolicitacoesTitulares', 'LgpdPublico'] as $controller) {
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
        }

        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "UPDATE adms_pages SET public_page = 0, updated_at = NOW()
                 WHERE controller IN ('PoliticaPrivacidade', 'TermosDeUso')"
            );
        }

        $this->bumpMenuCache();
    }

    private function expandSolicitacoesTable(): void
    {
        if (!$this->hasTable('lgpd_solicitacoes_titulares')) {
            return;
        }

        $table = $this->table('lgpd_solicitacoes_titulares');
        $columns = [
            'protocolo' => ['type' => 'string', 'limit' => 32, 'null' => true, 'after' => 'id'],
            'titular_cpf' => ['type' => 'string', 'limit' => 14, 'null' => true, 'after' => 'titular_email'],
            'titular_endereco' => ['type' => 'text', 'null' => true],
            'titular_nascimento' => ['type' => 'date', 'null' => true],
            'titular_telefone' => ['type' => 'string', 'limit' => 30, 'null' => true],
            'titular_categoria' => ['type' => 'string', 'limit' => 40, 'null' => true],
            'titular_categoria_outro' => ['type' => 'string', 'limit' => 120, 'null' => true],
            'informacoes_adicionais' => ['type' => 'text', 'null' => true],
            'por_procurador' => ['type' => 'boolean', 'default' => false, 'null' => false],
            'procurador_nome' => ['type' => 'string', 'limit' => 150, 'null' => true],
            'procurador_cpf' => ['type' => 'string', 'limit' => 14, 'null' => true],
            'procurador_email' => ['type' => 'string', 'limit' => 150, 'null' => true],
            'direitos_json' => ['type' => 'text', 'null' => true],
            'comunicacao_meio' => ['type' => 'string', 'limit' => 20, 'null' => true],
            'comunicacao_outro' => ['type' => 'string', 'limit' => 255, 'null' => true],
            'ip_address' => ['type' => 'string', 'limit' => 45, 'null' => true],
            'user_agent' => ['type' => 'string', 'limit' => 255, 'null' => true],
            'observacao_atendimento' => ['type' => 'text', 'null' => true],
            'atendido_por' => ['type' => 'integer', 'null' => true],
            'atendido_em' => ['type' => 'datetime', 'null' => true],
        ];

        foreach ($columns as $name => $opts) {
            if ($table->hasColumn($name)) {
                continue;
            }
            $type = $opts['type'];
            unset($opts['type']);
            $table->addColumn($name, $type, $opts);
        }

        if (!$table->hasIndex('protocolo')) {
            $table->addIndex(['protocolo'], ['unique' => true, 'name' => 'idx_lgpd_solicitacoes_protocolo']);
        }

        $table->update();
    }

    private function registerPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $this->execute(
            "UPDATE adms_pages SET public_page = 1, updated_at = " . $conn->quote($now) . "
             WHERE controller IN ('PoliticaPrivacidade', 'TermosDeUso')"
        );

        $groupPublic = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'LGPD - Dashboard / Termos / Legal' LIMIT 1"
        );
        if (!$groupPublic) {
            $groupPublic = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'LgpdDashboard' LIMIT 1"
            );
        }
        $groupTitulares = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'LGPD - Titulares' LIMIT 1"
        );
        if (!$groupTitulares) {
            $groupTitulares = $this->fetchRow(
                "SELECT adms_groups_page_id AS id FROM adms_pages WHERE controller = 'LgpdCategoriasTitulares' LIMIT 1"
            );
        }
        if (!$groupPublic || !$groupTitulares) {
            return;
        }

        $pages = [
            [
                'name' => 'Portal público LGPD',
                'controller' => 'LgpdPublico',
                'controller_url' => 'lgpd',
                'directory' => 'lgpd',
                'obs' => 'Landing pública LGPD (DPO, políticas e formulário Art. 18). Sem login.',
                'public_page' => 1,
                'group_id' => (int) $groupPublic['id'],
            ],
            [
                'name' => 'Solicitações de titulares LGPD',
                'controller' => 'LgpdSolicitacoesTitulares',
                'controller_url' => 'lgpd-solicitacoes-titulares',
                'directory' => 'lgpd',
                'obs' => 'Backoffice DPO: listar requisições de direitos do titular.',
                'public_page' => 0,
                'group_id' => (int) $groupTitulares['id'],
            ],
            [
                'name' => 'Visualizar solicitação de titular LGPD',
                'controller' => 'LgpdSolicitacoesTitularesView',
                'controller_url' => 'lgpd-solicitacoes-titulares-view',
                'directory' => 'lgpd',
                'obs' => 'Backoffice DPO: ver e atender requisição Art. 18.',
                'public_page' => 0,
                'group_id' => (int) $groupTitulares['id'],
            ],
        ];

        foreach ($pages as $p) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($p['controller']) . ' LIMIT 1'
            );
            if ($existing) {
                continue;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($p['name']) . ', '
                . $conn->quote($p['controller']) . ', '
                . $conn->quote($p['controller_url']) . ', '
                . $conn->quote($p['directory']) . ', '
                . $conn->quote($p['obs']) . ', '
                . (int) $p['public_page'] . ', 0, 1, 1, '
                . (int) $p['group_id'] . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'LgpdConsentimentos' LIMIT 1"
        );
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];

        foreach (['LgpdSolicitacoesTitulares', 'LgpdSolicitacoesTitularesView'] as $controller) {
            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
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
