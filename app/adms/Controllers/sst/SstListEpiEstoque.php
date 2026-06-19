<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SstEpiEstoqueService;
use App\adms\Views\Services\LoadViewService;

class SstListEpiEstoque
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')) ?: null,
        ];
        if (!empty($_GET['estoque_baixo'])) {
            $filters['estoque_baixo'] = true;
        }
        $this->data['items'] = (new SstEpiEstoqueService())->listarPosicaoEstoque($filters);
        $this->data['filters'] = $filters;
        $pageElements = [
            'title_head' => 'Posição de estoque EPI',
            'menu' => 'sst-list-epi-estoque',
            'buttonPermission' => ['SstListEpiEstoque', 'SstCreateEpiMovimento', 'SstListEpiMovimentos'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_estoque/posicao', $this->data))->loadView();
    }
}
