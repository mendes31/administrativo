<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Services\PerformanceFeedbackService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para atualizar feedback de desempenho
 */
class UpdatePerformanceFeedback
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $fid = (int) $id;
        if ($fid <= 0) {
            $_SESSION['error'] = 'Feedback não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            return;
        }

        $repository = new PerformanceFeedbacksRepository();
        $feedback = $repository->getById($fid);
        if (!$feedback) {
            $_SESSION['error'] = 'Feedback não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            return;
        }

        $actorId = (int) ($_SESSION['user_id'] ?? 0);
        $fullAccess = UserAccessHelper::hasFullSystemAccess();
        $service = new PerformanceFeedbackService();
        if (!$service->canView($feedback, $actorId, $fullAccess) || !$service->canEdit($feedback, $actorId, $fullAccess)) {
            $_SESSION['error'] = 'Você não tem permissão para editar este feedback.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            return;
        }

        $this->data['feedback'] = $feedback;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($fid);
            $this->data['feedback'] = $repository->getById($fid) ?? $feedback;
        }

        $reviewsRepo = new PerformanceReviewsRepository();
        $goalsRepo = new PerformanceGoalsRepository();
        $this->data['reviews'] = $reviewsRepo->getAll([], 1, 100);
        $this->data['goals'] = $goalsRepo->getAll([], 1, 100);

        $pageElements = [
            'title_head' => 'Editar Feedback de Desempenho',
            'menu' => 'list-performance-feedbacks',
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
        if (!CSRFHelper::validateCSRFToken('form_update_performance_feedback', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $result = (new PerformanceFeedbackService())->update(
            $id,
            $_POST,
            (int) ($_SESSION['user_id'] ?? 0),
            UserAccessHelper::hasFullSystemAccess()
        );

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar feedback.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Feedback atualizado com sucesso!</div>';
        GenerateLog::generateLog('info', 'Feedback de desempenho atualizado.', ['id' => $id]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-feedback/' . $id);
        exit;
    }
}
