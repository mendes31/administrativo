<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RequestTypesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar tipos de solicitação
 */
class ListRequestTypes
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repository = new RequestTypesRepository();
        $this->data['requestTypes'] = $repository->getAll();

        $pageElements = [
            'title_head' => 'Listar Tipos de Solicitação',
            'menu' => 'list-request-types',
            'buttonPermission' => [
                'CreateRequestType',
                'UpdateRequestType',
                'DeleteRequestType',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/portal/list_request_types', $this->data);
        $loadView->loadView();
    }
}

