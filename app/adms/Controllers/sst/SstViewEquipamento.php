<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEquipamentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstViewEquipamento
{
    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentosRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Equipamento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['historico'] = $repo->getVistoriaHistorico($id);
        $pageElements = [
            'title_head' => 'Equipamento ' . ($item['codigo'] ?? '') . ' - SST',
            'menu' => 'sst-list-equipamentos',
            'buttonPermission' => ['SstViewEquipamento', 'SstUpdateEquipamento', 'SstDeleteEquipamento', 'SstExecuteEquipamentoVistoria'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/view', $this->data))->loadView();
    }
}
