<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\SstEquipamentoNaoConformidadesRepository;
use App\adms\Views\Services\LoadViewService;

class SstListEquipamentoNaoConformidades
{
    private array $data = [];
    private int $limit = 20;

    public function index(string|int $page = 1): void
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'status_open' => !empty($_GET['abertas']) ? '1' : '',
        ];
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }
        $repo = new SstEquipamentoNaoConformidadesRepository();
        $total = $repo->getTotal($filters);
        $this->data['items'] = $repo->getAll((int) $page, $this->limit, $filters);
        $this->data['pagination'] = PaginationService::generatePagination(
            $total,
            $this->limit,
            (int) $page,
            'sst-list-equipamento-nao-conformidades',
            $filters
        );
        $this->data['filters'] = $filters;
        $pageElements = [
            'title_head' => 'Não conformidades - Equipamentos SST',
            'menu' => 'sst-list-equipamento-nao-conformidades',
            'buttonPermission' => [
                'SstListEquipamentoNaoConformidades',
                'SstViewEquipamentoNaoConformidade',
                'SstCreateEquipamentoAcaoCorretiva',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/nao_conformidades_list', $this->data))->loadView();
    }
}
