<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdClassificacoesDadosRepository;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdClassificacoesDadosCreate
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function create(): void
    {
        $data = [
            'classificacao' => $_POST['classificacao'] ?? '',
            'exemplos' => $_POST['exemplos'] ?? '',
            'base_legal_id' => $_POST['base_legal_id'] ?? '',
            'status' => $_POST['status'] ?? 'Ativo'
        ];

        // Validação básica
        if (empty($data['classificacao'])) {
            $_SESSION['msg'] = "Erro: Classificação é obrigatória!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
            return;
        }

        if (empty($data['base_legal_id'])) {
            $_SESSION['msg'] = "Erro: Base Legal é obrigatória!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
            return;
        }

        $repository = new LgpdClassificacoesDadosRepository();
        $result = $repository->create($data);

        if ($result) {
            $_SESSION['msg'] = "Classificação de Dados cadastrada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-classificacoes-dados");
            exit;
        } else {
            $_SESSION['msg'] = "Erro: Classificação de Dados não foi cadastrada!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $basesLegaisRepo = new LgpdBasesLegaisRepository();
        $this->data['bases_legais'] = $basesLegaisRepo->getActiveBasesLegais();

        $pageElements = [
            'title_head' => 'Cadastrar Classificação de Dados LGPD',
            'menu' => 'CreateLgpdClassificacoesDados',
            'buttonPermission' => ['CreateLgpdClassificacoesDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/classificacoes-dados/create", $this->data);
        $loadView->loadView();
    }
} 