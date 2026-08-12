<?php

declare(strict_types=1);

namespace App\adms\Controllers\portaria;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PortariaPontosRepository;
use App\adms\Views\Services\LoadViewService;

final class PortariaPontos
{
    private array $data = [];

    public function index(): void
    {
        $this->data['pontos'] = (new PortariaPontosRepository())->getAll();
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements([
            'title_head' => 'Pontos de Controle',
            'menu' => 'portaria-pontos',
            'buttonPermission' => ['PortariaPontosCreate', 'PortariaPontosUpdate'],
        ]));
        (new LoadViewService('adms/Views/portaria/pontos/list', $this->data))->loadView();
    }
}
