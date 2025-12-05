<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CompetenciesRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar competência
 */
class UpdateCompetency
{
    private array|string|null $data = null;

    public function index(string|int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        } else {
            $this->showForm((int)$id);
        }
    }

    private function showForm(int $id): void
    {
        $repository = new CompetenciesRepository();
        $competency = $repository->getById($id);

        if (!$competency) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Competência não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-competencies');
            exit;
        }

        $this->data['competency'] = $competency;

        $pageElements = [
            'title_head' => 'Editar Competência',
            'menu' => 'update-competency',
            'buttonPermission' => [
                'ListCompetencies',
                'ViewCompetency',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/update_competency', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $data = [
            'name' => $_POST['name'] ?? '',
            'competency_type' => $_POST['competency_type'] ?? '',
            'category' => $_POST['category'] ?? null,
            'description' => $_POST['description'] ?? null,
            'level_1_description' => $_POST['level_1_description'] ?? null,
            'level_2_description' => $_POST['level_2_description'] ?? null,
            'level_3_description' => $_POST['level_3_description'] ?? null,
            'level_4_description' => $_POST['level_4_description'] ?? null,
            'level_5_description' => $_POST['level_5_description'] ?? null,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Nome é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-competency/' . $id);
            exit;
        }

        if (empty($data['competency_type'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Tipo é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-competency/' . $id);
            exit;
        }

        $repository = new CompetenciesRepository();
        
        try {
            $repository->update($id, $data);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Competência atualizada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-competency/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar competência: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-competency/' . $id);
            exit;
        }
    }
}

