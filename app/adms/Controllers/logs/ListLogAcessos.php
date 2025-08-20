<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ListLogAcessos
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }
        $filtros = [
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo_acesso' => $_GET['tipo_acesso'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $repo = new LogAcessosRepository();
        $this->data['logs'] = $repo->getAll($paginaAtual, $perPage, $filtros);
        $this->data['per_page'] = $perPage;
        $this->data['filtros'] = $filtros;
        $this->data['pagina_atual'] = $paginaAtual;
        $this->data['total_registros'] = $repo->countAll($filtros);
        $this->data['total_paginas'] = (int)ceil($this->data['total_registros'] / $perPage);
        $pageElements = [
            'title_head' => 'Log de Acessos',
            'menu' => 'list-log-acessos',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService("adms/Views/logs/listLogAcessos", $this->data);
        $loadView->loadView();
    }
} 