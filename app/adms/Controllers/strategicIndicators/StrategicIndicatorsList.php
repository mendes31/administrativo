<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicIndicators;

use App\adms\Models\Repository\StrategicIndicatorsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class StrategicIndicatorsList
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicIndicatorsRepository();
    }

    public function index(): void
    {
        // Obter indicadores com paginação
        $indicators = $this->repository->getAll();
        
        // Elementos de página
        $pageElements = [
            'title_head' => 'Listar Indicadores Estratégicos',
            'menu' => 'strategic-indicators-list',
            'buttonPermission' => ['StrategicIndicatorsList'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['indicators'] = $indicators;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicIndicators/strategic-indicators-list", $this->data);
        $loadView->loadView();
    }
} 