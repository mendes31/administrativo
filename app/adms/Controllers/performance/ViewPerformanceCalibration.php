<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Repository\PerformanceReviewsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewPerformanceCalibration
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $calId = (int) $id;
        if ($calId <= 0) {
            $_SESSION['error'] = 'Calibração não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-calibrations');
            exit;
        }

        $repository = new PerformanceCalibrationsRepository();
        $cal = $repository->getById($calId);
        if (!$cal) {
            $_SESSION['error'] = 'Calibração não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-calibrations');
            exit;
        }

        $this->data['calibration'] = $cal;
        $cycleId = (int) $cal['performance_cycle_id'];
        $reviewsRepo = new PerformanceReviewsRepository();
        $this->data['reviews'] = $reviewsRepo->getAll(['performance_cycle_id' => $cycleId], 1, 100);
        $this->data['reviews_count'] = $reviewsRepo->count(['performance_cycle_id' => $cycleId]);

        $returnUrl = $_ENV['URL_ADM'] . 'view-performance-calibration/' . $calId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_performance_calibrations', $calId, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Calibração',
            'menu' => 'list-performance-calibrations',
            'buttonPermission' => [
                'ListPerformanceCalibrations',
                'UpdatePerformanceCalibration',
                'NineBoxMatrix',
                'ListPerformanceReviews',
                'ListTalentNominations',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/view_calibration', $this->data);
        $loadView->loadView();
    }
}
