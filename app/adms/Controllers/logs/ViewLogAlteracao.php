<?php

namespace App\adms\Controllers\logs;

use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogAlteracoesDetalhesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ViewLogAlteracao
{
    private array|string|null $data = null;

    public function index(int $id): void
    {
        $repo = new LogAlteracoesRepository();
        $detalheRepo = new LogAlteracoesDetalhesRepository();
        $justRepo = new LogJustificativasRepository();
        $log = $repo->getById($id);
        if (!$log) {
            $_SESSION['error'] = 'Log de alteração não encontrado!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-log-alteracoes');
            exit;
        }
        $this->data['log'] = $log;
        $this->data['detalhes'] = $detalheRepo->getByLogAlteracaoId($id);
        $this->data['justificativa'] = $justRepo->getByLogAlteracaoId($id);
        $pageElements = [
            'title_head' => 'Detalhes da Modificação',
            'menu' => 'list-log-alteracoes',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService("adms/Views/logs/viewLogAlteracao", $this->data);
        $loadView->loadView();
    }
} 