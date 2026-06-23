<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Fase 2 SST Treinamentos: portal colaborador + certificado PDF.
 */
final class RegisterSstTreinamentosFase2Pages extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const PAGES = [
        ['name' => 'Meus treinamentos SST (portal)', 'controller' => 'MySstTreinamentos', 'controller_url' => 'my-sst-treinamentos', 'directory' => 'portal', 'obs' => 'Treinamentos SST obrigatórios e certificados do colaborador.', 'adms_groups_page_id' => 36],
        ['name' => 'Ver certificado treinamento SST (portal)', 'controller' => 'ViewSstTreinamentoCertificadoPdf', 'controller_url' => 'view-sst-treinamento-certificado-pdf', 'directory' => 'portal', 'obs' => 'Visualizar PDF do certificado SST do colaborador.', 'adms_groups_page_id' => 36],
        ['name' => 'Exportar certificado treinamento SST', 'controller' => 'SstExportTreinamentoCertificadoPdf', 'controller_url' => 'sst-export-treinamento-certificado-pdf', 'directory' => 'sst', 'obs' => 'Download PDF do certificado de treinamento SST.', 'adms_groups_page_id' => 41],
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
                'directory' => $page['directory'],
                'obs' => $page['obs'],
                'public_page' => 0,
                'default_page' => 0,
                'page_status' => 1,
                'adms_packages_page_id' => 1,
                'adms_groups_page_id' => $page['adms_groups_page_id'],
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
