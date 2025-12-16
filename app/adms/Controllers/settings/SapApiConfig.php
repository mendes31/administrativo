<?php

namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSapApiConfigRepository;

class SapApiConfig
{
    public function index(): void
    {
        $repo = new AdmsSapApiConfigRepository();
        $config = $repo->getConfig();

        $data = [
            'title_head' => 'Configuração SAP API',
            'menu' => 'sap-api-config',
            'buttonPermission' => ['SapApiConfig'],
            'sap_api_config' => $config,
            'csrf_token' => CSRFHelper::generateCSRFToken('form_sap_api_config'),
        ];

        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));

        $loadView = new LoadViewService('adms/Views/settings/sapApiConfig', $data);
        $loadView->loadView();
    }
}





