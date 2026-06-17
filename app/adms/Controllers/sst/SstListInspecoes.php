<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstInspecoesRepository;
use App\adms\Views\Services\LoadViewService;

class SstListInspecoes
{
    private array $data = [];
    private int $limit = 10;

    public function index(string|int $page = 1): void
    {
        $filters = ['search' => $_GET['search'] ?? '', 'tipo' => $_GET['tipo'] ?? '', 'status' => $_GET['status'] ?? ''];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstInspecoesRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-inspecoes', $filters);
        $this->data['filters'] = $filters;
        $pageElements = ['title_head' => 'Inspeções SST', 'menu' => 'sst-list-inspecoes', 'buttonPermission' => ['SstListInspecoes', 'SstCreateInspecao', 'SstViewInspecao', 'SstUpdateInspecao', 'SstDeleteInspecao']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/inspecoes/list', $this->data))->loadView();
    }
}
