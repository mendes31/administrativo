<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\PerformanceFeedbackService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar feedback de desempenho
 */
class ViewPerformanceFeedback
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
        if (!$service->canView($feedback, $actorId, $fullAccess)) {
            $_SESSION['error'] = 'Você não tem permissão para ver este feedback.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-feedbacks');
            return;
        }

        $this->data['feedback'] = $feedback;
        $this->data['author_display'] = $service->displayAuthorName($feedback, $actorId, $fullAccess);
        $this->data['can_edit'] = $service->canEdit($feedback, $actorId, $fullAccess);

        $returnUrl = $_ENV['URL_ADM'] . 'view-performance-feedback/' . $fid;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_performance_feedbacks', $fid, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Feedback de Desempenho',
            'menu' => 'list-performance-feedbacks',
            'buttonPermission' => [
                'ListPerformanceFeedbacks',
                'UpdatePerformanceFeedback',
                'DeletePerformanceFeedback',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/view_feedback', $this->data);
        $loadView->loadView();
    }
}
