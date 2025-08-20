<?php
namespace App\adms\Controllers\settings;

use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\AdmsEmailConfigRepository;

class ListEmailConfig
{
    public function index(): void
    {
        $repo = new AdmsEmailConfigRepository();
        $data = [
            'title_head' => 'Configuração de E-mail',
            'menu' => 'email-config',
            'buttonPermission' => ['ListEmailConfig'],
            'email_config' => $repo->getConfig(),
        ];
        $pageLayout = new PageLayoutService();
        $data = array_merge($data, $pageLayout->configurePageElements($data));
        $loadView = new LoadViewService('adms/Views/settings/emailConfig', $data);
        $loadView->loadView();
    }
} 