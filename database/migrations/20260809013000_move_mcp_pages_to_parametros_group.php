<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Move páginas MCP (chat + config + tools) do CRM/Configurações para o grupo ACL «Parâmetros».
 * O chat é transversal a departamentos; a config unificada fica em mcp-api-config (abas).
 */
final class MoveMcpPagesToParametrosGroup extends AbstractMigration
{
    private const GROUP_NAME = 'Parâmetros';

    /** @var list<string> */
    private const CONTROLLERS = [
        'McpApiConfig',
        'SaveMcpApiConfig',
        'ListMcpChatTools',
        'SaveMcpChatTool',
        'McpChat',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $gid = $this->ensureParametrosGroup($now);
        if ($gid <= 0) {
            return;
        }

        foreach (self::CONTROLLERS as $controller) {
            $c = $this->quote($controller);
            $this->execute(
                "UPDATE adms_pages
                 SET adms_groups_page_id = {$gid}, updated_at = {$this->quote($now)}
                 WHERE controller = {$c}"
            );
        }

        // Nome amigável da tela unificada (menu / ACL).
        $this->execute(
            "UPDATE adms_pages
             SET name = {$this->quote('Assistente MCP')},
                 obs = {$this->quote('Parâmetros do Assistente MCP (conexão + tools) e permissão de uso do chat.')},
                 updated_at = {$this->quote($now)}
             WHERE controller = 'McpApiConfig'
             LIMIT 1"
        );

        $this->execute(
            "UPDATE adms_pages
             SET obs = {$this->quote('Permissão para usar o chat Tiarajuzinho (vários departamentos).')},
                 updated_at = {$this->quote($now)}
             WHERE controller = 'McpChat'
             LIMIT 1"
        );

        $this->execute(
            "UPDATE adms_pages
             SET obs = {$this->quote('Aba Tools em mcp-api-config (redirect). Catálogo RH + relatórios dinâmicos.')},
                 updated_at = {$this->quote($now)}
             WHERE controller = 'ListMcpChatTools'
             LIMIT 1"
        );

        // Quem tem config ou tools deve ter ambos (tela unificada).
        if ($this->hasTable('adms_access_levels_pages')) {
            $this->syncAclPair('McpApiConfig', 'ListMcpChatTools', $now);
            $this->syncAclPair('ListMcpChatTools', 'McpApiConfig', $now);
            $this->syncAclPair('McpApiConfig', 'SaveMcpChatTool', $now);
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $cfg = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Configurações' LIMIT 1");
        $cfgId = (int) ($cfg['id'] ?? 0);
        $crm = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = 'CRM - Integrações e configurações' LIMIT 1"
        );
        $crmId = (int) ($crm['id'] ?? 0);
        if ($crmId <= 0) {
            $crmLegacy = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'CRM' LIMIT 1");
            $crmId = (int) ($crmLegacy['id'] ?? 0);
        }

        $now = date('Y-m-d H:i:s');
        if ($cfgId > 0) {
            foreach (['McpApiConfig', 'SaveMcpApiConfig', 'ListMcpChatTools', 'SaveMcpChatTool'] as $controller) {
                $c = $this->quote($controller);
                $this->execute(
                    "UPDATE adms_pages
                     SET adms_groups_page_id = {$cfgId}, updated_at = {$this->quote($now)}
                     WHERE controller = {$c}"
                );
            }
        }
        if ($crmId > 0) {
            $this->execute(
                "UPDATE adms_pages
                 SET adms_groups_page_id = {$crmId}, updated_at = {$this->quote($now)}
                 WHERE controller = 'McpChat'"
            );
        }
    }

    private function ensureParametrosGroup(string $now): int
    {
        $name = $this->quote(self::GROUP_NAME);
        $row = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = {$name} LIMIT 1");
        if ($row && !empty($row['id'])) {
            return (int) $row['id'];
        }

        $obs = $this->quote('Parâmetros transversais do sistema (ex.: Assistente MCP / Tiarajuzinho).');
        $this->execute(
            "INSERT INTO adms_groups_pages (name, obs, created_at, updated_at)
             VALUES ({$name}, {$obs}, {$this->quote($now)}, {$this->quote($now)})"
        );
        $row = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = {$name} LIMIT 1");

        return (int) ($row['id'] ?? 0);
    }

    private function syncAclPair(string $fromController, string $toController, string $now): void
    {
        $from = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = {$this->quote($fromController)} LIMIT 1");
        $to = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = {$this->quote($toController)} LIMIT 1");
        $fromId = (int) ($from['id'] ?? 0);
        $toId = (int) ($to['id'] ?? 0);
        if ($fromId <= 0 || $toId <= 0) {
            return;
        }

        $this->execute(
            "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT alp.permission, alp.adms_access_level_id, {$toId}, {$this->quote($now)}, {$this->quote($now)}
             FROM adms_access_levels_pages alp
             WHERE alp.adms_page_id = {$fromId}
               AND alp.permission = 1
             ON DUPLICATE KEY UPDATE
               permission = GREATEST(adms_access_levels_pages.permission, VALUES(permission)),
               updated_at = VALUES(updated_at)"
        );
    }

    private function bumpMenuPermissionCache(): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt', (string) time());
    }

    private function quote(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
