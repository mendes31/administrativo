<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdTermosRepository;
use App\adms\Views\Services\LoadViewService;

class LgpdTermosCreate
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
            'versao' => $_POST['versao'] ?? '',
            'titulo' => $_POST['titulo'] ?? '',
            'tipo' => $_POST['tipo'] ?? 'login',
            'conteudo' => $_POST['conteudo'] ?? '',
            'data_inicio_vigencia' => $_POST['data_inicio_vigencia'] ?? date('Y-m-d 00:00:00'),
            'data_fim_vigencia' => $_POST['data_fim_vigencia'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
        ];

        if (empty($data['versao']) || empty($data['titulo']) || empty($data['conteudo'])) {
            $_SESSION['msg'] = "Erro: Versão, Título e Conteúdo são obrigatórios.";
            $_SESSION['msg_type'] = "danger";
            $this->data['formData'] = $data;
            $this->showForm();
            return;
        }

        $repo = new LgpdTermosRepository();
        $newId = $repo->create($data);

        if ($newId) {
            $_SESSION['msg'] = "Termo LGPD cadastrado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-termos-view/" . (int)$newId);
            exit;
        }

        $_SESSION['msg'] = "Erro: Termo não foi cadastrado.";
        $_SESSION['msg_type'] = "danger";
        $this->data['formData'] = $data;
        $this->showForm();
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Termo LGPD',
            'menu' => 'lgpd-termos',
            'buttonPermission' => ['LgpdTermosCreate'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/lgpd/termos/create', $this->data);
        $loadView->loadView();
    }
}


