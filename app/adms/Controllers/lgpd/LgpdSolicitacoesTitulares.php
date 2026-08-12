<?php

declare(strict_types=1);

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdSolicitacoesTitularesRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdSolicitacoesTitulares
{
    private array $data = [];

    public function index(): void
    {
        $repo = new LgpdSolicitacoesTitularesRepository();
        $this->data['solicitacoes'] = $repo->getAll();
        $this->data['pendentes'] = $repo->countPendentes();
        $this->data['url_publico'] = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/lgpd';

        $pageElements = [
            'title_head' => 'Solicitações de titulares LGPD',
            'menu' => 'lgpd-solicitacoes-titulares',
            'buttonPermission' => ['LgpdSolicitacoesTitulares', 'LgpdSolicitacoesTitularesView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));

        (new LoadViewService('adms/Views/lgpd/solicitacoes/list', $this->data))->loadView();
    }
}
