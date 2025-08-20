<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ListLogAlteracoes
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
            'tabela' => $_GET['tabela'] ?? '',
            'objeto_id' => $_GET['objeto_id'] ?? '',
            'identificador' => $_GET['identificador'] ?? '',
            'usuario_nome' => $_GET['usuario_nome'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
        ];
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $repo = new LogAlteracoesRepository();
        $this->data['logs'] = $repo->getAll($paginaAtual, $perPage, $filtros);
        // Adiciona o link de registro para cada log
        foreach ($this->data['logs'] as &$log) {
            $log['link_registro'] = $this->getLinkRegistro($log['tabela'], $log['objeto_id']);
        }
        unset($log);
        $this->data['per_page'] = $perPage;
        $this->data['filtros'] = $filtros;
        $this->data['pagina_atual'] = $paginaAtual;
        $this->data['total_registros'] = $repo->countAll($filtros);
        $this->data['total_paginas'] = (int)ceil($this->data['total_registros'] / $perPage);
        $pageElements = [
            'title_head' => 'Log de Modificações',
            'menu' => 'list-log-alteracoes',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService("adms/Views/logs/listLogAlteracoes", $this->data);
        $loadView->loadView();
    }
    
    /**
     * Gera link para visualizar o registro original
     */
    public function getLinkRegistro(string $tabela, int $objetoId): ?string
    {
        switch ($tabela) {
            case 'adms_users':
                return $_ENV['URL_ADM'] . 'view-user/' . $objetoId;
            case 'adms_customer':
                return $_ENV['URL_ADM'] . 'view-customer/' . $objetoId;
            case 'adms_departments':
                return $_ENV['URL_ADM'] . 'view-department/' . $objetoId;
            case 'adms_pay':
                return $_ENV['URL_ADM'] . 'view-pay/' . $objetoId;
            case 'adms_receive':
                return $_ENV['URL_ADM'] . 'view-receive/' . $objetoId;
            case 'adms_trainings':
                return $_ENV['URL_ADM'] . 'view-training/' . $objetoId;
            case 'adms_supplier':
                return $_ENV['URL_ADM'] . 'view-supplier/' . $objetoId;
            case 'adms_banks':
                return $_ENV['URL_ADM'] . 'view-bank/' . $objetoId;
            case 'adms_cost_centers':
                return $_ENV['URL_ADM'] . 'view-cost-center/' . $objetoId;
            case 'adms_accounts_plan':
                return $_ENV['URL_ADM'] . 'view-account-plan/' . $objetoId;
            case 'adms_frequencies':
                return $_ENV['URL_ADM'] . 'view-frequency/' . $objetoId;
            case 'adms_payment_methods':
                return $_ENV['URL_ADM'] . 'view-payment-method/' . $objetoId;
            case 'adms_positions':
                return $_ENV['URL_ADM'] . 'view-position/' . $objetoId;
            case 'adms_packages':
                return $_ENV['URL_ADM'] . 'view-package/' . $objetoId;
            case 'adms_groups_pages':
                return $_ENV['URL_ADM'] . 'view-group-page/' . $objetoId;
            case 'adms_pages':
                return $_ENV['URL_ADM'] . 'view-page/' . $objetoId;
            case 'adms_access_levels':
                return $_ENV['URL_ADM'] . 'view-access-level/' . $objetoId;
            default:
                return null; // Tabela não mapeada
        }
    }
} 