<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar feedback de desempenho
 */
class CreatePerformanceFeedback
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        // Buscar usuários para o select
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, function($user) use ($userId) {
                return isset($user['immediate_supervisor']) && $user['immediate_supervisor'] == $userId;
            });
        }

        // Buscar avaliações e metas relacionadas (opcional)
        $reviewsRepo = new PerformanceReviewsRepository();
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['reviews'] = $reviewsRepo->getAll([], 1, 100);
        $this->data['goals'] = $goalsRepo->getAll([], 1, 100);

        $pageElements = [
            'title_head' => 'Criar Feedback de Desempenho',
            'menu' => 'list-performance-feedbacks',
            'buttonPermission' => [
                'ListPerformanceFeedbacks',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/create_feedback', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_create_performance_feedback', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-feedback');
            exit;
        }

        $data = [
            'employee_id' => (int)($_POST['employee_id'] ?? 0),
            'given_by' => (int)($_POST['given_by'] ?? $_SESSION['user_id'] ?? 0),
            'feedback_type' => $_POST['feedback_type'] ?? 'general',
            'feedback_text' => trim($_POST['feedback_text'] ?? ''),
            'is_anonymous' => isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1',
            'is_public' => isset($_POST['is_public']) && $_POST['is_public'] === '1',
            'related_review_id' => !empty($_POST['related_review_id']) ? (int)$_POST['related_review_id'] : null,
            'related_goal_id' => !empty($_POST['related_goal_id']) ? (int)$_POST['related_goal_id'] : null,
        ];

        // Validações
        if (empty($data['employee_id'])) {
            $_SESSION['error'] = 'Colaborador é obrigatório.';
            return;
        }

        if (empty($data['feedback_text'])) {
            $_SESSION['error'] = 'Texto do feedback é obrigatório.';
            return;
        }

        if (empty($data['given_by'])) {
            $_SESSION['error'] = 'Avaliador é obrigatório.';
            return;
        }

        $repository = new PerformanceFeedbacksRepository();
        
        if ($repository->create($data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Feedback criado com sucesso!</div>';
            GenerateLog::generateLog("info", "Feedback de desempenho criado.", $data);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao criar feedback. Tente novamente.';
        }
    }
}

