<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar avaliação de desempenho
 */
class CreatePerformanceReview
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        // Verificar se o formulário foi enviado
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        // Buscar usuários para seleção
        $usersRepo = new UsersRepository();
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        // Buscar avaliações disponíveis (para vincular)
        $evaluationRepo = new \App\adms\Models\Repository\EvaluationModelsRepository();
        $this->data['evaluations'] = $evaluationRepo->getAllModels([], 1, 100);

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Criar Avaliação de Desempenho',
            'menu' => 'create-performance-review',
            'buttonPermission' => [
                'ListPerformanceReviews',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'reviewer_id' => (int)($_POST['reviewer_id'] ?? $_SESSION['user_id'] ?? 0),
            'review_type' => $_POST['review_type'] ?? '360',
            'review_period_start' => $_POST['review_period_start'] ?? date('Y-m-d'),
            'review_period_end' => $_POST['review_period_end'] ?? date('Y-m-d'),
            'review_date' => $_POST['review_date'] ?? date('Y-m-d'),
            'status' => $_POST['status'] ?? 'draft',
            'evaluation_id' => !empty($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : null,
            'created_by' => $_SESSION['user_id'] ?? 0,
        ];

        // Validações básicas
        if (empty($data['employee_id'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Selecione o colaborador!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-review');
            exit;
        }

        if (empty($data['reviewer_id'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Selecione o avaliador!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-review');
            exit;
        }

        $repository = new PerformanceReviewsRepository();
        
        try {
            $id = $repository->create($data);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Avaliação de desempenho criada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-review/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar avaliação: ' . $e->getMessage() . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-review');
            exit;
        }
    }
}

