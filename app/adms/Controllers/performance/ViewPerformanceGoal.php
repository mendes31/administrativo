<?php

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceGoalsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar meta de desempenho
 */
class ViewPerformanceGoal
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Meta não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        $repository = new PerformanceGoalsRepository();
        $this->data['goal'] = $repository->getById((int)$id);

        if (!$this->data['goal']) {
            $_SESSION['error'] = 'Meta não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        $gid = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'view-performance-goal/' . $gid;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_performance_goals', $gid, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Meta de Desempenho',
            'menu' => 'view-performance-goal',
            'buttonPermission' => [
                'ListPerformanceGoals',
                'UpdatePerformanceGoal',
                'DeletePerformanceGoal',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/performance/view_goal', $this->data);
        $loadView->loadView();
    }
}

