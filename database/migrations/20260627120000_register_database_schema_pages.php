<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Biblioteca de base de dados (MySQL) — listagem e detalhe de tabelas.
 */
final class RegisterDatabaseSchemaPages extends AbstractMigration
{
    private const GROUP_NAME = 'Base de Dados';

    public function up(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $gid = $this->ensureGroup();
        if ($gid <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->registerPage(
            'Biblioteca — Sistema (MySQL)',
            'ListDatabaseTables',
            'list-database-tables',
            'Catálogo das tabelas MySQL do sistema (INFORMATION_SCHEMA).',
            $gid,
            $now
        );
        $this->registerPage(
            'Visualizar tabela (biblioteca BD)',
            'ViewDatabaseTable',
            'view-database-table',
            'Detalhe de colunas, índices e FKs de uma tabela MySQL.',
            $gid,
            $now
        );

        $this->syncPermissionsFromReference('ListLogAcessos');
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        foreach (['ListDatabaseTables', 'ViewDatabaseTable'] as $controller) {
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
    }

    private function ensureGroup(): int
    {
        $g = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = '" . self::GROUP_NAME . "' LIMIT 1");
        if ($g) {
            return (int) $g['id'];
        }

        $now = date('Y-m-d H:i:s');
        $this->table('adms_groups_pages')->insert([
            'name' => self::GROUP_NAME,
            'obs' => 'Biblioteca e documentação de tabelas do sistema.',
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $new = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');

        return (int) ($new['id'] ?? 0);
    }

    private function registerPage(
        string $name,
        string $controller,
        string $url,
        string $obs,
        int $groupId,
        string $now
    ): void {
        $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
        if ($exists) {
            return;
        }

        $this->table('adms_pages')->insert([
            'name' => $name,
            'controller' => $controller,
            'controller_url' => $url,
            'directory' => 'databaseSchema',
            'obs' => $obs,
            'public_page' => 0,
            'default_page' => 0,
            'page_status' => 1,
            'adms_packages_page_id' => 1,
            'adms_groups_page_id' => $groupId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->save();

        $newRow = $this->fetchRow('SELECT LAST_INSERT_ID() AS id');
        $newId = (int) ($newRow['id'] ?? 0);
        if ($newId <= 0 || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $this->execute(
            "INSERT IGNORE INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
             SELECT 0, al.id, {$newId}, '{$now}', '{$now}'
             FROM adms_access_levels al"
        );
    }

    private function syncPermissionsFromReference(string $refController): void
    {
        if (!$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $ref = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$refController}' LIMIT 1");
        if (!$ref) {
            return;
        }
        $refId = (int) $ref['id'];

        foreach (['ListDatabaseTables', 'ViewDatabaseTable'] as $controller) {
            $page = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '{$controller}' LIMIT 1");
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 SELECT ref.permission, ref.adms_access_level_id, {$pageId}, NOW(), NOW()
                 FROM adms_access_levels_pages ref
                 WHERE ref.adms_page_id = {$refId}
                 ON DUPLICATE KEY UPDATE permission = VALUES(permission), updated_at = NOW()"
            );
        }
    }
}
