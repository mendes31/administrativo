<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdRopaRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdRopaView
{
    private array|string|null $data = null;

    public function index($id): void
    {
        if (!(int) $id) {
            $_SESSION['error'] = "Registro não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
            return;
        }
        $repo = new LgpdRopaRepository();
        $this->data['registro'] = $repo->getById($id);
        if (!$this->data['registro']) {
            $_SESSION['error'] = "Registro não encontrado!";
            header("Location: {$_ENV['URL_ADM']}lgpd-ropa");
            return;
        }
        $pageElements = [
            'title_head' => 'Visualizar ROPA',
            'menu' => 'ListLgpdRopa',
            'buttonPermission' => ['ListLgpdRopa', 'EditLgpdRopa', 'DeleteLgpdRopa', 'LgpdDataMappingCreate'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService("adms/Views/lgpd/ropa/view", $this->data);
        $loadView->loadView();
    }
} 