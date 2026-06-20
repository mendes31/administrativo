<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEquipamentoTipos
{
    private array $data = [];
    private int $limit = 15;

    public function index(string|int $page = 1): void
    {
        $filters = ['search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? ''];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstEquipamentoTiposRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-equipamento-tipos', $filters);
        $this->data['filters'] = $filters;
        $pageElements = [
            'title_head' => 'Tipos de equipamento - SST',
            'menu' => 'sst-list-equipamento-tipos',
            'buttonPermission' => ['SstListEquipamentoTipos', 'SstCreateEquipamentoTipo', 'SstViewEquipamentoTipo', 'SstUpdateEquipamentoTipo', 'SstDeleteEquipamentoTipo'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/tipos_list', $this->data))->loadView();
    }
}
