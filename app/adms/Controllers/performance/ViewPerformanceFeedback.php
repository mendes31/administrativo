<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar feedback de desempenho
 */
class ViewPerformanceFeedback
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

        $fid = (int) $id;
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

