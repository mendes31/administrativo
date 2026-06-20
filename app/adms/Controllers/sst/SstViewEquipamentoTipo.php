<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewEquipamentoTipo
{
    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentoTiposRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Tipo não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['checklist'] = $repo->getChecklistItens($id);
        $pageElements = [
            'title_head' => 'Tipo de equipamento - SST',
            'menu' => 'sst-list-equipamento-tipos',
            'buttonPermission' => ['SstViewEquipamentoTipo', 'SstUpdateEquipamentoTipo', 'SstDeleteEquipamentoTipo', 'SstManageEquipamentoChecklistItem'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/tipo_view', $this->data))->loadView();
    }
}
