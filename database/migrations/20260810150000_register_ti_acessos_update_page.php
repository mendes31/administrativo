<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Página TiAcessosUpdate — editar login/perfil de acesso ativo no mapa TI.
 * ACL copiada de TiAcessosCreate (quem libera também edita).
 */
final class RegisterTiAcessosUpdatePage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existing = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosUpdate' LIMIT 1"
        );
        if (!$existing) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'TI - Sistemas e Acessos' LIMIT 1"
            );
            if (!$group) {
                return;
            }
            $gid = (int) $group['id'];
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('Editar Acesso (TI)') . ', '
                . $conn->quote('TiAcessosUpdate') . ', '
                . $conn->quote('ti-acessos-update') . ', '
                . $conn->quote('ti') . ', '
                . $conn->quote('Editar login/perfil/obs de um acesso ativo no mapa TI.') . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        $page = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosUpdate' LIMIT 1"
        );
        $ref = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosCreate' LIMIT 1"
        );
        if (!$page || !$ref || !$this->hasTable('adms_access_levels_pages')) {
            $this->bumpMenuCache();
            return;
        }

        $pageId = (int) $page['id'];
        $refId = (int) $ref['id'];

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT alp.permission, alp.adms_access_level_id, {$pageId}, " . $conn->quote($now) . ', ' . $conn->quote($now) . "
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$refId}
               AND NOT EXISTS (
                   SELECT 1 FROM adms_access_levels_pages x
                   WHERE x.adms_access_level_id = alp.adms_access_level_id
                     AND x.adms_page_id = {$pageId}
               )"
        );

        $this->bumpMenuCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $row = $this->fetchRow(
            "SELECT id FROM adms_pages WHERE controller = 'TiAcessosUpdate' LIMIT 1"
        );
        if (!$row) {
            return;
        }
        $pid = (int) $row['id'];
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
        }
        $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
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
