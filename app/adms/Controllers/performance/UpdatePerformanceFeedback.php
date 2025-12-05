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
 * Controller para atualizar feedback de desempenho
 */
class UpdatePerformanceFeedback
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Feedback não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-performance-feedbacks");
            return;
        }

        $repository = new PerformanceFeedbacksRepository();
        $this->data['feedback'] = $repository->getById((int)$id);

        if (!$this->data['feedback']) {
            $_SESSION['error'] = 'Feedback não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-performance-feedbacks");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
        }

        // Buscar avaliações e metas relacionadas
        $reviewsRepo = new PerformanceReviewsRepository();
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['reviews'] = $reviewsRepo->getAll([], 1, 100);
        $this->data['goals'] = $goalsRepo->getAll([], 1, 100);

        $pageElements = [
            'title_head' => 'Editar Feedback de Desempenho',
            'menu' => 'update-performance-feedback',
            'buttonPermission' => [
                'ListPerformanceFeedbacks',
                'ViewPerformanceFeedback',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/update_feedback', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_update_performance_feedback', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            return;
        }

        $data = [
            'feedback_type' => $_POST['feedback_type'] ?? 'general',
            'feedback_text' => trim($_POST['feedback_text'] ?? ''),
            'is_anonymous' => isset($_POST['is_anonymous']) && $_POST['is_anonymous'] === '1',
            'is_public' => isset($_POST['is_public']) && $_POST['is_public'] === '1',
            'related_review_id' => !empty($_POST['related_review_id']) ? (int)$_POST['related_review_id'] : null,
            'related_goal_id' => !empty($_POST['related_goal_id']) ? (int)$_POST['related_goal_id'] : null,
        ];

        // Validações
        if (empty($data['feedback_text'])) {
            $_SESSION['error'] = 'Texto do feedback é obrigatório.';
            return;
        }

        $repository = new PerformanceFeedbacksRepository();
        
        if ($repository->update($id, $data)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Feedback atualizado com sucesso!</div>';
            GenerateLog::generateLog("info", "Feedback de desempenho atualizado.", ['id' => $id, 'data' => $data]);
            header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-feedback/' . $id);
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao atualizar feedback. Tente novamente.';
        }
    }
}

