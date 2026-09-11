<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SstEpiEstoqueService;
use App\adms\Views\Services\LoadViewService;

/** Posição de estoque EPI com recorte por tamanho/numeração. */
class SstListEpiEstoque
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'estoque_baixo' => !empty($_GET['estoque_baixo']) ? '1' : '',
            'status' => 'Ativo',
        ];
        $this->data['filters'] = $filters;
        $this->data['items'] = (new SstEpiEstoqueService())->listarPosicaoEstoque($filters);
        $this->data['buttonPermission'] = [];
        $pageElements = [
            'title_head' => 'Posição de estoque EPI - SST',
            'menu' => 'sst-list-epi-estoque',
            'buttonPermission' => ['SstListEpiEstoque', 'SstCreateEpiMovimento', 'SstListEpiMovimentos'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_estoque/posicao', $this->data))->loadView();
    }
}
