<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Registra páginas SST (fichas EPI + estoque) e invalida cache de menu.
 */
final class RegisterSstEpiFichasEstoquePages extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const PAGES = [
        ['name' => 'Listar Fichas EPI SST', 'controller' => 'SstListEpiFichas', 'controller_url' => 'sst-list-epi-fichas', 'directory' => 'sst', 'obs' => 'Fichas de entrega de EPI com assinatura no portal.', 'adms_groups_page_id' => 41],
        ['name' => 'Cadastrar Ficha EPI SST', 'controller' => 'SstCreateEpiFicha', 'controller_url' => 'sst-create-epi-ficha', 'directory' => 'sst', 'obs' => 'Nova ficha de entrega de EPI.', 'adms_groups_page_id' => 41],
        ['name' => 'Visualizar Ficha EPI SST', 'controller' => 'SstViewEpiFicha', 'controller_url' => 'sst-view-epi-ficha', 'directory' => 'sst', 'obs' => 'Visualização de ficha de EPI.', 'adms_groups_page_id' => 41],
        ['name' => 'Exportar PDF Ficha EPI SST', 'controller' => 'SstExportEpiFichaPdf', 'controller_url' => 'sst-export-epi-ficha-pdf', 'directory' => 'sst', 'obs' => 'Download PDF da ficha de EPI.', 'adms_groups_page_id' => 41],
        ['name' => 'Posição estoque EPI SST', 'controller' => 'SstListEpiEstoque', 'controller_url' => 'sst-list-epi-estoque', 'directory' => 'sst', 'obs' => 'Saldo e alertas de estoque mínimo.', 'adms_groups_page_id' => 41],
        ['name' => 'Listar movimentações EPI SST', 'controller' => 'SstListEpiMovimentos', 'controller_url' => 'sst-list-epi-movimentos', 'directory' => 'sst', 'obs' => 'Histórico de entradas/saídas de EPI.', 'adms_groups_page_id' => 41],
        ['name' => 'Registrar movimentação EPI SST', 'controller' => 'SstCreateEpiMovimento', 'controller_url' => 'sst-create-epi-movimento', 'directory' => 'sst', 'obs' => 'Entrada, saída ou ajuste de estoque EPI.', 'adms_groups_page_id' => 41],
        ['name' => 'Meus EPIs (portal)', 'controller' => 'MyEpiDeliveries', 'controller_url' => 'my-epi-deliveries', 'directory' => 'portal', 'obs' => 'Fichas e histórico de EPIs do colaborador.', 'adms_groups_page_id' => 36],
        ['name' => 'Assinar Ficha EPI (portal)', 'controller' => 'SignEpiFicha', 'controller_url' => 'sign-epi-ficha', 'directory' => 'portal', 'obs' => 'Confirmação de recebimento de EPI no portal.', 'adms_groups_page_id' => 36],
        ['name' => 'Ver PDF Ficha EPI (portal)', 'controller' => 'ViewEpiFichaPdf', 'controller_url' => 'view-epi-ficha-pdf', 'directory' => 'portal', 'obs' => 'Visualizar PDF da própria ficha de EPI.', 'adms_groups_page_id' => 36],
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
