<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Models\Repository\SstEquipamentoVistoriasRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEquipamentoVistorias
{
    private array $data = [];
    private int $limit = 20;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'competencia' => $_GET['competencia'] ?? '',
            'resultado' => $_GET['resultado'] ?? '',
            'adms_sst_equipamento_tipo_id' => $_GET['tipo_id'] ?? '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstEquipamentoVistoriasRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-equipamento-vistorias', $filters);
        $this->data['filters'] = $filters;
        $this->data['tipos'] = (new SstEquipamentoTiposRepository())->getAllActiveForSelect();
        $pageElements = [
            'title_head' => 'Vistorias de equipamentos - SST',
            'menu' => 'sst-list-equipamento-vistorias',
            'buttonPermission' => ['SstListEquipamentoVistorias', 'SstExecuteEquipamentoVistoria', 'SstMinhasEquipamentoVistorias'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/vistorias_list', $this->data))->loadView();
    }
}
