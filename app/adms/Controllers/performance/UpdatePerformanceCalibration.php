<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Services\PerformanceCalibrationService;
use App\adms\Views\Services\LoadViewService;

class UpdatePerformanceCalibration
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($calId);
            $this->data['calibration'] = $repository->getById($calId) ?? $cal;
        }

        $pageElements = [
            'title_head' => 'Editar Calibração',
            'menu' => 'list-performance-calibrations',
            'buttonPermission' => [
                'ListPerformanceCalibrations',
                'ViewPerformanceCalibration',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/update_calibration', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_performance_calibration', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $result = (new PerformanceCalibrationService())->update(
            $id,
            $_POST,
            (int) ($_SESSION['user_id'] ?? 0)
        );

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Calibração atualizada!</div>';
        GenerateLog::generateLog('info', 'Calibração atualizada.', ['id' => $id]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-calibration/' . $id);
        exit;
    }
}
