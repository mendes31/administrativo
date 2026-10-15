<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

final class RegisterFormacaoPrecoPdfPage extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existing = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'FormacaoPrecoPdf' LIMIT 1");
        if (!$existing) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name = 'Comercial - Formação de Preço' LIMIT 1"
            );
            $gid = (int) ($group['id'] ?? 0);
            if ($gid <= 0) {
                $ref = $this->fetchRow("SELECT adms_groups_page_id FROM adms_pages WHERE controller = 'FormacaoPreco' LIMIT 1");
                $gid = (int) ($ref['adms_groups_page_id'] ?? 0);
            }
            if ($gid <= 0) {
                return;
            }
            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote('PDF Formação de Preço') . ', '
                . $conn->quote('FormacaoPrecoPdf') . ', '
                . $conn->quote('formacao-preco-pdf') . ', '
                . $conn->quote('pricing') . ', '
                . $conn->quote('Exporta a análise completa da precificação, inclusive colunas ocultas.') . ', '
                . '0, 0, 1, 1, ' . $gid . ', '
                . $conn->quote($now) . ', ' . $conn->quote($now) . ')'
            );
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
            return;
        }
        $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'FormacaoPrecoPdf' LIMIT 1");
        if (!$page) {
            return;
        }
        $pageId = (int) $page['id'];
        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$pageId}, " . $conn->quote($now) . ', ' . $conn->quote($now) . '
             FROM adms_access_levels al'
        );
        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'FormacaoPrecoView' LIMIT 1");
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
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'FormacaoPrecoPdf' LIMIT 1");
        if ($row) {
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
        }
        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
