<?php

declare(strict_types=1);

use App\adms\Models\Repository\MenuPermissionUserRepository;
use Phinx\Migration\AbstractMigration;

/**
 * Páginas ACL: catálogo Tools do Assistente MCP.
 */
final class RegisterMcpChatToolsPages extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT id, adms_groups_page_id, adms_packages_page_id FROM adms_pages WHERE controller = 'McpApiConfig' LIMIT 1");
        if (!$ref) {
            return;
        }

        $gid = (int) ($ref['adms_groups_page_id'] ?? 0);
        $pkg = (int) ($ref['adms_packages_page_id'] ?? 1);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $pages = [
            [
                'name' => 'Tools do Assistente MCP',
                'controller' => 'ListMcpChatTools',
                'controller_url' => 'list-mcp-chat-tools',
                'obs' => 'Catálogo de tools do chat (RH built-in + relatórios dinâmicos).',
            ],
            [
                'name' => 'Salvar Tool do Assistente MCP',
                'controller' => 'SaveMcpChatTool',
                'controller_url' => 'save-mcp-chat-tool',
                'obs' => 'Salvar metadados chat_enabled / tool / exemplos de relatório dinâmico.',
            ],
        ];

        foreach ($pages as $page) {
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$page['controller']}' LIMIT 1");
            if ($exists) {
                continue;
            }

            $this->table('adms_pages')->insert([
                'name' => $page['name'],
                'controller' => $page['controller'],
                'controller_url' => $page['controller_url'],
                'directory' => 'settings',
                'obs' => $page['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => $pkg > 0 ? $pkg : 1,
                'adms_groups_page_id' => $gid,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $mcpPage = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = 'McpApiConfig' LIMIT 1");
        $mcpId = (int) ($mcpPage['id'] ?? 0);

        foreach (['ListMcpChatTools', 'SaveMcpChatTool'] as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $newId = (int) $row['id'];

            // Replica permissões de quem já tem McpApiConfig; senão só Super Admin.
            if ($mcpId > 0) {
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT alp.permission, alp.adms_access_level_id, {$newId}, '{$now}', '{$now}'
                     FROM adms_access_levels_pages alp
                     WHERE alp.adms_page_id = {$mcpId}
                     ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = VALUES(updated_at)"
                );
            } else {
                $this->execute(
                    "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                     SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
                     FROM adms_access_levels al
                     ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)"
                );
                $this->execute(
                    "UPDATE adms_access_levels_pages
                     SET permission = 1, updated_at = '{$now}'
                     WHERE adms_page_id = {$newId} AND adms_access_level_id = 1"
                );
            }
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        foreach (['ListMcpChatTools', 'SaveMcpChatTool'] as $controller) {
            $row = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$row) {
                continue;
            }
            $pid = (int) $row['id'];
            if ($this->hasTable('adms_access_levels_pages')) {
                $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
            }
            $this->execute("DELETE FROM adms_pages WHERE id = {$pid} LIMIT 1");
        }

        MenuPermissionUserRepository::bumpGlobalPermissionCacheVersion();
    }
}
