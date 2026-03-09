<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Views\Services\LoadViewService;

class ListPolicyCategories
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new PoliciesRepository();
        $this->data['categorias'] = $repo->getCategorias();

        $pageElements = [
            'title_head'       => 'Categorias de Políticas Internas',
            'menu'             => 'gestao_pessoas',
            'buttonPermission' => ['ListPolicyCategories', 'CreatePolicyCategory', 'UpdatePolicyCategory', 'DeletePolicyCategory'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/categories/list', $this->data);
        $loadView->loadView();
    }
}

