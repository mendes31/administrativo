<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Tabela adms_whistleblowing_categories + páginas de gestão de classificações.
 * Versão 20260709150100 — substitui arquivo duplicado 20260709150000 no deploy.
 */
final class WhistleblowingReportCategories extends AbstractMigration
{
    /** @var list<string> */
    private const DEFAULT_CATEGORIES = [
        'Assédio Moral',
        'Assédio Sexual',
        'Fraude',
        'Corrupção',
        'Favorecimento',
        'Furto',
        'Discriminação',
        'Segurança',
        'Qualidade',
        'Meio Ambiente',
        'Conflito de Interesse',
        'Outros',
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_whistleblowing_categories')) {
            $this->table('adms_whistleblowing_categories')
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('description', 'text', ['null' => true])
                ->addColumn('sort_order', 'integer', ['default' => 0, 'signed' => false])
                ->addColumn('is_active', 'boolean', ['default' => 1, 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['name'], ['unique' => true])
                ->addIndex(['is_active'])
                ->addIndex(['sort_order'])
                ->create();
        }

        $now = date('Y-m-d H:i:s');
        $order = 10;
        foreach (self::DEFAULT_CATEGORIES as $name) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_whistleblowing_categories WHERE name = '
                . $this->getAdapter()->getConnection()->quote($name)
                . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }
            $this->table('adms_whistleblowing_categories')->insert([
                'name' => $name,
                'description' => null,
                'sort_order' => $order,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->saveData();
            $order += 10;
        }

        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $group = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = 'Canal de Denúncias' LIMIT 1");
        if (!$group) {
            $group = $this->fetchRow(
                "SELECT id FROM adms_groups_pages WHERE name LIKE '%Denúncia%' OR name LIKE '%Whistle%' LIMIT 1"
            );
        }
        if (!$group) {
            return;
        }
        $gid = (int) $group['id'];
        $q = fn (string $value): string => $this->getAdapter()->getConnection()->quote($value);

        $pages = [
            ['WhistleblowingListCategories', 'list-whistleblowing-categories', 'Classificações de denúncias'],
            ['WhistleblowingCreateCategory', 'create-whistleblowing-category', 'Nova classificação'],
            ['WhistleblowingUpdateCategory', 'update-whistleblowing-category', 'Editar classificação'],
        ];

        foreach ($pages as [$controller, $url, $name]) {
            $exists = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $q($controller) . ' LIMIT 1'
            );
            if ($exists) {
                continue;
            }

            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $q($name) . ', '
                . $q($controller) . ', '
                . $q($url) . ", "
                . $q('whistleblowing') . ", "
                . $q('') . ', 0, 0, 1, 1, '
                . $gid . ', '
                . $q($now) . ', '
                . $q($now)
                . ')'
            );
        }

        $this->grantAdminPages(['WhistleblowingListCategories', 'WhistleblowingCreateCategory', 'WhistleblowingUpdateCategory'], $now);
    }

    public function down(): void
    {
        if ($this->hasTable('adms_pages')) {
            $this->execute(
                "DELETE FROM adms_pages WHERE controller IN (
                    'WhistleblowingListCategories',
                    'WhistleblowingCreateCategory',
                    'WhistleblowingUpdateCategory'
                )"
            );
        }
        if ($this->hasTable('adms_whistleblowing_categories')) {
            $this->table('adms_whistleblowing_categories')->drop()->save();
        }
    }

    /**
     * @param list<string> $controllers
     */
    private function grantAdminPages(array $controllers, string $now): void
    {
        if (!$this->hasTable('adms_access_levels') || !$this->hasTable('adms_access_levels_pages')) {
            return;
        }

        $level = $this->fetchRow(
            "SELECT id FROM adms_access_levels WHERE name = 'Canal de Denúncias — Administrador' LIMIT 1"
        );
        if (!$level) {
            return;
        }
        $levelId = (int) $level['id'];

        foreach ($controllers as $controller) {
            $page = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = '
                . $this->getAdapter()->getConnection()->quote($controller)
                . ' LIMIT 1'
            );
            if (!$page) {
                continue;
            }
            $pageId = (int) $page['id'];
            $this->execute(
                "INSERT INTO adms_access_levels_pages (permission, adms_access_level_id, adms_page_id, created_at, updated_at)
                 VALUES (1, {$levelId}, {$pageId}, '{$now}', '{$now}')
                 ON DUPLICATE KEY UPDATE permission = 1, updated_at = '{$now}'"
            );
        }
    }
}
