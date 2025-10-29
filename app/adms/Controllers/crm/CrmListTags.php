<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para listar Tags CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmListTags
{
    private array $data = [];

    public function index(): void
    {
        $tagsRepo = new CrmTagsRepository();
        $this->data['tags'] = $tagsRepo->getAllTags();

        // Layout
        $pageElements = [
            'title_head' => 'Tags - CRM',
            'menu' => 'crm-list-tags',
            'buttonPermission' => ['CrmListTags', 'CrmCreateTag'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/tags/list", $this->data);
        $loadView->loadView();
    }
}

