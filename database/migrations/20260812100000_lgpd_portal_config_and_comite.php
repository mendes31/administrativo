<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Configuração do portal público LGPD (DPO, empresa, comitê) — sem depender do .env.
 */
final class LgpdPortalConfigAndComite extends AbstractMigration
{
    public function up(): void
    {
        $this->createPortalConfigTable();
        $this->createComiteTable();
        $this->registerConfigPage();
        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = 'LgpdPublicoConfig' LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute('DELETE FROM adms_access_levels_pages WHERE adms_page_id = ' . $pid);
                }
                $this->execute('DELETE FROM adms_pages WHERE id = ' . $pid);
            }
        }

        if ($this->hasTable('lgpd_comite_membros')) {
            $this->table('lgpd_comite_membros')->drop()->save();
        }

        if ($this->hasTable('lgpd_portal_config')) {
            $this->table('lgpd_portal_config')->drop()->save();
        }

        $this->bumpMenuCache();
    }

    private function createPortalConfigTable(): void
    {
        if ($this->hasTable('lgpd_portal_config')) {
            return;
        }

        $this->table('lgpd_portal_config', ['id' => 'id', 'primary_key' => ['id']])
            ->addColumn('empresa_nome', 'string', ['limit' => 200, 'null' => true])
            ->addColumn('dpo_nome', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('dpo_email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('dpo_telefone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('cartilha_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('carta_compromisso_path', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('comite_titulo', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('comite_descricao', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->create();
    }

    private function createComiteTable(): void
    {
        if ($this->hasTable('lgpd_comite_membros')) {
            return;
        }

        $this->table('lgpd_comite_membros', ['id' => 'id', 'primary_key' => ['id']])
            ->addColumn('nome', 'string', ['limit' => 150, 'null' => false])
            ->addColumn('cargo', 'string', ['limit' => 120, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 150, 'null' => true])
            ->addColumn('telefone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('ordem', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('ativo', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['ativo', 'ordem'], ['name' => 'idx_lgpd_comite_ativo_ordem'])
            ->create();
    }

    private function registerConfigPage(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $conn = $this->getAdapter()->getConnection();
        $controller = 'LgpdPublicoConfig';
        $existing = $this->fetchRow(
            'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
        );
        if ($existing) {
            $this->grantPageAcl((int) $existing['id']);

            return;
        }

        $groupId = 31;
        $group = $this->fetchRow(
            "SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'LgpdDashboard' LIMIT 1"
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
            . $conn->quote('Configuração portal público LGPD') . ', '
            . $conn->quote($controller) . ', '
            . $conn->quote('lgpd-publico-config') . ', '
            . $conn->quote('lgpd') . ', '
            . $conn->quote('DPO, empresa e comitê exibidos na página pública /lgpd.') . ', '
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
            $this->grantPageAcl((int) $page['id']);
        }
    }

    private function grantPageAcl(int $pageId): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'LgpdDashboard' LIMIT 1"
        );
        if (!$ref) {
            return;
        }

        $refId = (int) $ref['id'];
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT alp.permission, alp.adms_access_level_id, {$pageId}, "
            . $this->getAdapter()->getConnection()->quote($now) . ', '
            . $this->getAdapter()->getConnection()->quote($now) . "
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND NOT EXISTS (
                   SELECT 1 FROM adms_access_levels_pages x
                   WHERE x.adms_access_level_id = alp.adms_access_level_id
                     AND x.adms_page_id = {$pageId}
               )"
        );
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
