<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTiposDadosRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdTiposDadosCreate
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
            'tipo_dado' => $_POST['tipo_dado'] ?? '',
            'status' => $_POST['status'] ?? 'Ativo'
        ];

        // Validação básica
        if (empty($data['tipo_dado'])) {
            $_SESSION['msg'] = "Erro: Tipo de Dados é obrigatório!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
            return;
        }

        $repository = new LgpdTiposDadosRepository();
        $result = $repository->create($data);

        if ($result) {
            $_SESSION['msg'] = "Tipo de Dados cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-tipos-dados");
            exit;
        } else {
            $_SESSION['msg'] = "Erro: Tipo de Dados não foi cadastrado!";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Tipo de Dados LGPD',
            'menu' => 'CreateLgpdTiposDados',
            'buttonPermission' => ['CreateLgpdTiposDados'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/tipos-dados/create", $this->data);
        $loadView->loadView();
    }
} 