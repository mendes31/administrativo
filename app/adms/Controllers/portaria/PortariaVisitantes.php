<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PortariaVisitantesRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaVisitantes
{
    private array $data = [];

    public function index(): void
    {
        $this->data['filters'] = [
            'busca' => trim((string) ($_GET['busca'] ?? '')),
            'ativo' => (string) ($_GET['ativo'] ?? ''),
        ];
        $this->data['visitantes'] = (new PortariaVisitantesRepository())->getAll($this->data['filters']);
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Visitantes da Portaria',
            'menu' => 'portaria-visitantes',
            'buttonPermission' => ['PortariaVisitantesCreate', 'PortariaVisitantesView', 'PortariaVisitantesUpdate'],
        ]));
        (new LoadViewService('adms/Views/portaria/visitantes/list', $this->data))->loadView();
    }
}
