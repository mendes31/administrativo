<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class ListLogAcessos
{
    private array|string|null $data = null;
    private int $limitResult = 50;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }

        // Validar per_page (itens por página)
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = $this->limitResult;
        if (isset($_GET['per_page']) && is_numeric($_GET['per_page'])) {
            $candidate = (int)$_GET['per_page'];
            if (in_array($candidate, $allowedPerPage, true)) {
                $perPage = $candidate;
            }
        }
        $this->limitResult = $perPage;
        $filtros = [
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo_acesso' => $_GET['tipo_acesso'] ?? '',
            'ip' => $_GET['ip'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];

        $paginaAtual = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
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