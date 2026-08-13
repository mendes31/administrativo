<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Models\Repository\PortariaMovimentacoesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaPainel
{
    private array $data = [];

    public function index(): void
    {
        $presentes = (new PortariaMovimentacoesRepository())->listPresentesAgora();
        $aguardando = (new PortariaAutorizacoesRepository())->getAll(['status' => 'aguardando']);
        $this->data['presentes'] = $presentes;
        $this->data['total_presentes'] = count($presentes);
        $this->data['total_aguardando'] = count($aguardando);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Painel da Portaria',
            'menu' => 'portaria-painel',
            'buttonPermission' => [
                'PortariaVisitantes',
                'PortariaVisitantesCreate',
                'PortariaAutorizacoes',
                'PortariaAutorizacoesCreate',
                'PortariaAutorizacoesView',
                'PortariaPontos',
                'PortariaMovimentacoes',
            ],
        ]));
        (new LoadViewService('adms/Views/portaria/painel/index', $this->data))->loadView();
    }
}
