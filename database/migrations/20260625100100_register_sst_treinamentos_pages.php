<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstTreinamentosPages extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const PAGES = [
        ['name' => 'Listar Treinamentos SST', 'controller' => 'SstListTreinamentos', 'controller_url' => 'sst-list-treinamentos', 'obs' => 'Catálogo de treinamentos de segurança e medicina.'],
        ['name' => 'Cadastrar Treinamento SST', 'controller' => 'SstCreateTreinamento', 'controller_url' => 'sst-create-treinamento', 'obs' => 'Novo treinamento SST.'],
        ['name' => 'Visualizar Treinamento SST', 'controller' => 'SstViewTreinamento', 'controller_url' => 'sst-view-treinamento', 'obs' => 'Detalhe do treinamento SST.'],
        ['name' => 'Editar Treinamento SST', 'controller' => 'SstUpdateTreinamento', 'controller_url' => 'sst-update-treinamento', 'obs' => 'Edição de treinamento SST.'],
        ['name' => 'Excluir Treinamento SST', 'controller' => 'SstDeleteTreinamento', 'controller_url' => 'sst-delete-treinamento', 'obs' => 'Exclusão de treinamento SST.'],
        ['name' => 'Listar Necessidades Treinamento SST', 'controller' => 'SstListTreinamentoNecessidade', 'controller_url' => 'sst-list-treinamento-necessidade', 'obs' => 'Regras de treinamento por cargo/setor/risco.'],
        ['name' => 'Cadastrar Necessidade Treinamento SST', 'controller' => 'SstCreateTreinamentoNecessidade', 'controller_url' => 'sst-create-treinamento-necessidade', 'obs' => 'Nova necessidade de treinamento SST.'],
        ['name' => 'Editar Necessidade Treinamento SST', 'controller' => 'SstUpdateTreinamentoNecessidade', 'controller_url' => 'sst-update-treinamento-necessidade', 'obs' => 'Edição de necessidade de treinamento SST.'],
        ['name' => 'Excluir Necessidade Treinamento SST', 'controller' => 'SstDeleteTreinamentoNecessidade', 'controller_url' => 'sst-delete-treinamento-necessidade', 'obs' => 'Exclusão de necessidade de treinamento SST.'],
        ['name' => 'Listar Treinamentos por Risco SST', 'controller' => 'SstListRiscoTreinamento', 'controller_url' => 'sst-list-risco-treinamento', 'obs' => 'Matriz risco → treinamento.'],
        ['name' => 'Cadastrar Treinamento por Risco SST', 'controller' => 'SstCreateRiscoTreinamento', 'controller_url' => 'sst-create-risco-treinamento', 'obs' => 'Vínculo treinamento por risco.'],
        ['name' => 'Editar Treinamento por Risco SST', 'controller' => 'SstUpdateRiscoTreinamento', 'controller_url' => 'sst-update-risco-treinamento', 'obs' => 'Edição treinamento por risco.'],
        ['name' => 'Excluir Treinamento por Risco SST', 'controller' => 'SstDeleteRiscoTreinamento', 'controller_url' => 'sst-delete-risco-treinamento', 'obs' => 'Exclusão treinamento por risco.'],
        ['name' => 'Salvar Treinamentos Risco SST', 'controller' => 'SstSaveRiscoTreinamentos', 'controller_url' => 'sst-save-risco-treinamentos', 'obs' => 'Salvar aba treinamentos na visualização do risco.'],
        ['name' => 'Listar Vínculos Treinamento SST', 'controller' => 'SstListTreinamentoVinculos', 'controller_url' => 'sst-list-treinamento-vinculos', 'obs' => 'Status de treinamentos SST por colaborador.'],
        ['name' => 'Visualizar Vínculo Treinamento SST', 'controller' => 'SstViewTreinamentoVinculo', 'controller_url' => 'sst-view-treinamento-vinculo', 'obs' => 'Detalhe e histórico de aplicações SST.'],
        ['name' => 'Aplicar Treinamento SST', 'controller' => 'SstApplyTreinamento', 'controller_url' => 'sst-apply-treinamento', 'obs' => 'Registrar realização de treinamento SST.'],
        ['name' => 'Sincronizar Vínculos Treinamento SST', 'controller' => 'SstSyncTreinamentoVinculos', 'controller_url' => 'sst-sync-treinamento-vinculos', 'obs' => 'Sincroniza vínculos obrigatórios com a matriz SST.'],
        ['name' => 'Relatório Treinamentos SST', 'controller' => 'SstReportTreinamentos', 'controller_url' => 'sst-report-treinamentos', 'obs' => 'Relatório de treinamentos SST por status.'],
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
            static fn (array $p): string => "'" . addslashes($p['controller']) . "'",
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
