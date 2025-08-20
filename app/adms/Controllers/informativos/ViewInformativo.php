<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Views\Services\LoadViewService;

class ViewInformativo
{
    private array|string|null $data = null;

    public function index(string|int $id = null)
    {
        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $this->data['informativo'] = $informativo;

        $pageElements = [
            'title_head' => 'Visualizar Informativo',
            'menu' => 'view-informativo',
            'buttonPermission' => ['ViewInformativo', 'UpdateInformativo', 'DeleteInformativo'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/informativos/view', $this->data);
        $loadView->loadView();
    }
} 