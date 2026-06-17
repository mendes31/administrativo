<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstCipaRepository;
use App\adms\Views\Services\LoadViewService;

class SstListCipaMandatos
{
    private array $data = [];
    private int $limit = 10;

    public function index(string|int $page = 1): void
    {
        $filters = ['search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? ''];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstCipaRepository();
        $total = $repo->getMandatosTotal($filters);
        $this->data['items'] = $repo->getMandatos((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-cipa-mandatos', $filters);
        $this->data['filters'] = $filters;
        $pageElements = ['title_head' => 'CIPA — Mandatos', 'menu' => 'sst-list-cipa-mandatos', 'buttonPermission' => ['SstListCipaMandatos', 'SstCreateCipaMandato', 'SstViewCipaMandato', 'SstUpdateCipaMandato', 'SstDeleteCipaMandato']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/cipa/list', $this->data))->loadView();
    }
}
