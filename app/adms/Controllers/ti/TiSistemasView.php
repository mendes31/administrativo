<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\TiAcessoRepository;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

final class TiSistemasView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $sistemaId = (int) $id;
        $sistema = (new TiSistemaRepository())->getById($sistemaId);
        if ($sistema === null) {
            $_SESSION['msg'] = 'Sistema não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas');
            exit;
        }

        $acessoRepo = new TiAcessoRepository();
        $this->data['sistema'] = $sistema;
        $this->data['acessos'] = $acessoRepo->listBySistema($sistemaId);
        $returnUrl = ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . $sistemaId;
        $this->data['log_resumo'] = LogResumoService::getResumo('ti_sistemas', $sistemaId, $returnUrl);

        $pageElements = [
            'title_head' => 'Sistema (TI)',
            'menu' => 'ti-sistemas',
            'buttonPermission' => ['TiSistemas', 'TiSistemasUpdate', 'TiAcessosCreate', 'TiAcessosUpdate', 'TiAcessosRevoke'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/sistemas/view', $this->data))->loadView();
    }
}
