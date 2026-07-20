<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Visualizar ciclo de desempenho.
 */
class ViewPerformanceCycle
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $cycleId = (int) $id;
        if ($cycleId <= 0) {
            $_SESSION['error'] = 'Ciclo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-cycles');
            exit;
        }

        $repository = new PerformanceCyclesRepository();
        $cycle = $repository->getById($cycleId);
        if (!$cycle) {
            $_SESSION['error'] = 'Ciclo não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-cycles');
            exit;
        }

        $this->data['cycle'] = $cycle;
        $this->data['goals_count'] = $repository->countGoals($cycleId);
        $this->data['reviews_count'] = $repository->countReviews($cycleId);

        $returnUrl = $_ENV['URL_ADM'] . 'view-performance-cycle/' . $cycleId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_performance_cycles', $cycleId, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Ciclo de Desempenho',
            'menu' => 'list-performance-cycles',
            'buttonPermission' => [
                'ListPerformanceCycles',
                'UpdatePerformanceCycle',
                'ListPerformanceGoals',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/view_cycle', $this->data);
        $loadView->loadView();
    }
}
