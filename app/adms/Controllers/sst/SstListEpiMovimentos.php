<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpisRepository;
use App\adms\Models\Repository\SstEpiMovimentosRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEpiMovimentos
{
    private array $data = [];

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'adms_sst_epi_id' => (int) ($_GET['adms_sst_epi_id'] ?? 0) ?: null,
            'tipo_movimento' => trim((string) ($_GET['tipo_movimento'] ?? '')) ?: null,
        ];
        $repo = new SstEpiMovimentosRepository();
        $this->data['items'] = $repo->getAll($page, $perPage, $filters);
        $total = $repo->getTotal($filters);
        $this->data['pagination'] = [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
        $this->data['filters'] = $filters;
        $this->data['epis'] = (new SstEpisRepository())->getAll(1, 500);
        $pageElements = [
            'title_head' => 'Movimentações de estoque EPI',
            'menu' => 'sst-list-epi-movimentos',
            'buttonPermission' => ['SstListEpiMovimentos', 'SstCreateEpiMovimento', 'SstListEpiEstoque'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_estoque/movimentos_list', $this->data))->loadView();
    }
}
