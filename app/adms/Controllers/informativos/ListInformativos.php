<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Views\Services\LoadViewService;

class ListInformativos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1)
    {
        // Capturar o parâmetro page da URL, se existir
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        
        // Garantir que a página seja sempre pelo menos 1
        $page = max(1, (int)$page);
        
        // Tratar per_page
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }
        
        $filters = [
            'categoria' => $_GET['categoria'] ?? '',
            'ativo' => $_GET['ativo'] ?? '',
            'urgente' => $_GET['urgente'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'busca' => $_GET['busca'] ?? '',
        ];
        
        $repo = new InformativosRepository();
        $this->data['informativos'] = $repo->getAllInformativos((int)$page, (int)$this->limitResult, $filters);
        $totalInformativos = $repo->getTotalInformativos($filters);
        
        $pagination = PaginationService::generatePagination(
            (int) $totalInformativos,
            (int) $this->limitResult,
            (int) $page,
            'list-informativos',
            array_merge($filters, ['per_page' => $this->limitResult])
        );
        
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['categorias'] = $repo->getCategorias();
        $this->data['filters'] = $filters;
        
        $pageElements = [
            'title_head' => 'Listar Informativos',
            'menu' => 'list-informativos',
            'buttonPermission' => ['CreateInformativo', 'ViewInformativo', 'UpdateInformativo', 'DeleteInformativo'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/informativos/list', $this->data);
        $loadView->loadView();
    }
} 