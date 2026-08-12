<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PortariaAutorizacoesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaAutorizacoes
{
    private array $data = [];

    public function index(): void
    {
        $this->data['filters'] = [
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];
        $this->data['autorizacoes'] = (new PortariaAutorizacoesRepository())->getAll($this->data['filters']);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Autorizações de Visita',
            'menu' => 'portaria-autorizacoes',
            'buttonPermission' => ['PortariaAutorizacoesCreate', 'PortariaAutorizacoesView'],
        ]));
        (new LoadViewService('adms/Views/portaria/autorizacoes/list', $this->data))->loadView();
    }
}
