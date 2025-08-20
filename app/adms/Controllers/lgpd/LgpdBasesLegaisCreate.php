<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdBasesLegaisRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdBasesLegaisCreate
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
            'base_legal' => $_POST['base_legal'] ?? '',
            'descricao' => $_POST['descricao'] ?? '',
            'exemplo' => $_POST['exemplo'] ?? '',
            'status' => $_POST['status'] ?? 'Ativo'
        ];

        // Validação básica
        if (empty($data['base_legal'])) {
            $_SESSION['msg'] = "Erro: Base Legal é obrigatória!";
            $_SESSION['msg_type'] = "danger";
            $this->showForm();
            return;
        }

        $repository = new LgpdBasesLegaisRepository();
        $result = $repository->create($data);

        if ($result) {
            $_SESSION['msg'] = "Base Legal cadastrada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-bases-legais");
            exit;
        } else {
            $_SESSION['msg'] = "Erro: Base Legal não foi cadastrada!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Base Legal LGPD',
            'menu' => 'CreateLgpdBasesLegais',
            'buttonPermission' => ['CreateLgpdBasesLegais'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/bases-legais/create", $this->data);
        $loadView->loadView();
    }
} 