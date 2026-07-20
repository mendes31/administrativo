<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Services\PerformanceFeedbackService;
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

        $actorId = (int) ($_SESSION['user_id'] ?? 0);
        $fullAccess = UserAccessHelper::hasFullSystemAccess();
        $service = new PerformanceFeedbackService();
        $this->data['employees'] = $service->listEligibleRecipients($actorId, $fullAccess);

        $reviewsRepo = new PerformanceReviewsRepository();
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['reviews'] = $reviewsRepo->getAll([], 1, 100);
        $this->data['goals'] = $goalsRepo->getAll([], 1, 100);

        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'employee_id' => '',
                'feedback_type' => 'general',
                'feedback_text' => '',
                'is_anonymous' => '0',
                'is_public' => '0',
                'related_review_id' => '',
                'related_goal_id' => '',
            ];
        }

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
        if (!CSRFHelper::validateCSRFToken('form_create_performance_feedback', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-feedback');
            exit;
        }

        $result = (new PerformanceFeedbackService())->create(
            $_POST,
            (int) ($_SESSION['user_id'] ?? 0),
            UserAccessHelper::hasFullSystemAccess()
        );

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao criar feedback.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Feedback criado com sucesso!</div>';
        GenerateLog::generateLog('info', 'Feedback de desempenho criado.', ['id' => $result['id'] ?? 0]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-feedback/' . ($result['id'] ?? 0));
        exit;
    }
}
