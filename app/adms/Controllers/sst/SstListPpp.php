<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstPppRepository;
use App\adms\Views\Services\LoadViewService;

class SstListPpp
{
    private array $data = [];
    private int $limit = 10;

    public function index(string|int $page = 1): void
    {
        $filters = ['search' => $_GET['search'] ?? ''];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstPppRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination($total, $this->limit, (int) $page, 'sst-list-ppp', $filters);
        $this->data['filters'] = $filters;
        $pageElements = ['title_head' => 'PPP gerados', 'menu' => 'sst-list-ppp', 'buttonPermission' => ['SstListPpp', 'SstViewPpp', 'SstGeneratePpp', 'SstExportPppPdf']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/ppp/list', $this->data))->loadView();
    }
}
