<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstMatrizTreinamentoCargoPages extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const PAGES = [
        ['name' => 'Matriz Treinamentos por Cargo SST', 'controller' => 'SstMatrizTreinamentoCargo', 'controller_url' => 'sst-matriz-treinamento-cargo', 'obs' => 'Vincular treinamentos obrigatórios por cargo (matriz).'],
        ['name' => 'Salvar Matriz Treinamentos Cargo SST', 'controller' => 'SstSaveMatrizTreinamentoCargo', 'controller_url' => 'sst-save-matriz-treinamento-cargo', 'obs' => 'Persistir matriz cargo × treinamento.'],
    ];

    public function up(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach (self::PAGES as $page) {
            $ctrl = $page['controller'];
            $exists = $this->fetchRow("SELECT id FROM adms_pages WHERE controller = '" . addslashes($ctrl) . "' LIMIT 1");
            if ($exists) {
                continue;
            }
            $this->table('adms_pages')->insert([
                'name' => $page['name'],
                'controller' => $page['controller'],
                'controller_url' => $page['controller_url'],
                'directory' => 'sst',
                'obs' => $page['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => 41,
                'created_at' => $now,
                'updated_at' => $now,
            ])->save();
        }

        $this->bumpMenuPermissionCache();
    }

    public function down(): void
    {
        if (!$this->hasTable('adms_pages')) {
            return;
        }
        $controllers = array_map(
            static fn(array $p): string => "'" . addslashes($p['controller']) . "'",
            self::PAGES
        );
        $this->execute('DELETE FROM adms_pages WHERE controller IN (' . implode(',', $controllers) . ')');
        $this->bumpMenuPermissionCache();
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
}
