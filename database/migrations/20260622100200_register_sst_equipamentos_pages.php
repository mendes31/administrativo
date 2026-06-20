<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegisterSstEquipamentosPages extends AbstractMigration
{
    /** @var list<array<string, mixed>> */
    private const PAGES = [
        ['name' => 'Tipos equipamento SST', 'controller' => 'SstListEquipamentoTipos', 'controller_url' => 'sst-list-equipamento-tipos', 'obs' => 'Tipos de equipamentos de segurança e modelos de checklist.'],
        ['name' => 'Cadastrar tipo equipamento SST', 'controller' => 'SstCreateEquipamentoTipo', 'controller_url' => 'sst-create-equipamento-tipo', 'obs' => 'Novo tipo de equipamento.'],
        ['name' => 'Visualizar tipo equipamento SST', 'controller' => 'SstViewEquipamentoTipo', 'controller_url' => 'sst-view-equipamento-tipo', 'obs' => 'Detalhe do tipo e checklist.'],
        ['name' => 'Editar tipo equipamento SST', 'controller' => 'SstUpdateEquipamentoTipo', 'controller_url' => 'sst-update-equipamento-tipo', 'obs' => 'Editar tipo de equipamento.'],
        ['name' => 'Excluir tipo equipamento SST', 'controller' => 'SstDeleteEquipamentoTipo', 'controller_url' => 'sst-delete-equipamento-tipo', 'obs' => 'Excluir tipo de equipamento.'],
        ['name' => 'Gerenciar checklist equipamento SST', 'controller' => 'SstManageEquipamentoChecklistItem', 'controller_url' => 'sst-manage-equipamento-checklist-item', 'obs' => 'Itens do checklist por tipo.'],
        ['name' => 'Listar equipamentos SST', 'controller' => 'SstListEquipamentos', 'controller_url' => 'sst-list-equipamentos', 'obs' => 'Cadastro de equipamentos de segurança.'],
        ['name' => 'Cadastrar equipamento SST', 'controller' => 'SstCreateEquipamento', 'controller_url' => 'sst-create-equipamento', 'obs' => 'Novo equipamento.'],
        ['name' => 'Visualizar equipamento SST', 'controller' => 'SstViewEquipamento', 'controller_url' => 'sst-view-equipamento', 'obs' => 'Detalhe e histórico de vistorias.'],
        ['name' => 'Editar equipamento SST', 'controller' => 'SstUpdateEquipamento', 'controller_url' => 'sst-update-equipamento', 'obs' => 'Editar equipamento.'],
        ['name' => 'Excluir equipamento SST', 'controller' => 'SstDeleteEquipamento', 'controller_url' => 'sst-delete-equipamento', 'obs' => 'Excluir equipamento.'],
        ['name' => 'Vistorias equipamentos SST', 'controller' => 'SstListEquipamentoVistorias', 'controller_url' => 'sst-list-equipamento-vistorias', 'obs' => 'Todas as vistorias de equipamentos.'],
        ['name' => 'Minhas vistorias equipamentos SST', 'controller' => 'SstMinhasEquipamentoVistorias', 'controller_url' => 'sst-minhas-equipamento-vistorias', 'obs' => 'Fila de vistorias do responsável.'],
        ['name' => 'Executar vistoria equipamento SST', 'controller' => 'SstExecuteEquipamentoVistoria', 'controller_url' => 'sst-execute-equipamento-vistoria', 'obs' => 'Preencher checklist da vistoria.'],
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
