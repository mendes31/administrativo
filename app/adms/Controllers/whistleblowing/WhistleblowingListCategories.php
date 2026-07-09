<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\WhistleblowingCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingListCategories
{
    private array $data = [];

    public function index(): void
    {
        $repo = new WhistleblowingCategoriesRepository();
        $this->data['categories'] = $repo->getAll();

        $pageElements = [
            'title_head' => 'Classificações — Canal de Denúncias',
            'menu' => 'list-whistleblowing-categories',
            'buttonPermission' => ['WhistleblowingCreateCategory', 'WhistleblowingUpdateCategory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/categories/list', $this->data);
        $loadView->loadView();
    }
}
