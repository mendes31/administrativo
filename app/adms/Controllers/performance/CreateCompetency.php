<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar competência
 */
class CreateCompetency
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Competência',
            'menu' => 'list-competencies',
            'buttonPermission' => [
                'ListCompetencies',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/create_competency', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? null,
            'competency_type' => $_POST['competency_type'] ?? 'technical',
            'category' => $_POST['category'] ?? null,
            'level_1_description' => $_POST['level_1_description'] ?? null,
            'level_2_description' => $_POST['level_2_description'] ?? null,
            'level_3_description' => $_POST['level_3_description'] ?? null,
            'level_4_description' => $_POST['level_4_description'] ?? null,
            'level_5_description' => $_POST['level_5_description'] ?? null,
            'status' => isset($_POST['status']) ? (bool)$_POST['status'] : true,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Nome da competência é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-competency');
            exit;
        }

        $repository = new CompetenciesRepository();
        
        try {
            $id = $repository->create($data);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Competência criada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-competency/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar competência: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-competency');
            exit;
        }
    }
}

