<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Assistente MCP no menu/ACL «Configurações» (fora do CRM; sem submenu Parâmetros).
 */
final class MoveMcpPagesToConfiguracoesGroup extends AbstractMigration
{
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

        $row = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Configurações' LIMIT 1");
        $gid = (int) ($row['id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $nowQ = $this->quote($now);
        foreach (self::CONTROLLERS as $controller) {
            $c = $this->quote($controller);
            $this->execute(
                "UPDATE adms_pages
                 SET adms_groups_page_id = {$gid}, updated_at = {$nowQ}
                 WHERE controller = {$c}"
            );
        }

        $this->execute(
            "UPDATE adms_pages
             SET obs = {$this->quote('Assistente MCP (conexão + tools). Chat transversal; fora do CRM.')},
                 updated_at = {$nowQ}
             WHERE controller = 'McpApiConfig'
             LIMIT 1"
        );

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_groups_pages') || !$this->hasTable('adms_pages')) {
            return;
        }

        $row = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Parâmetros' LIMIT 1");
        $gid = (int) ($row['id'] ?? 0);
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $nowQ = $this->quote($now);
        foreach (self::CONTROLLERS as $controller) {
            $c = $this->quote($controller);
            $this->execute(
                "UPDATE adms_pages
                 SET adms_groups_page_id = {$gid}, updated_at = {$nowQ}
                 WHERE controller = {$c}"
            );
        }
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
