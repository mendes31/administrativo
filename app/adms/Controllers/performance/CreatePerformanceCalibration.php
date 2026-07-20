<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCalibrationsRepository;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Services\PerformanceCalibrationService;
use App\adms\Views\Services\LoadViewService;

class CreatePerformanceCalibration
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $cyclesRepo = new PerformanceCyclesRepository();
        $calRepo = new PerformanceCalibrationsRepository();
        $linkable = $cyclesRepo->listLinkable();
        $available = [];
        foreach ($linkable as $cycle) {
            if (!$calRepo->getByCycleId((int) $cycle['id'])) {
                $available[] = $cycle;
            }
        }
        $this->data['cycles'] = $available;
        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'performance_cycle_id' => $_GET['performance_cycle_id'] ?? '',
                'status' => 'draft',
                'session_notes' => '',
            ];
        }

        $pageElements = [
            'title_head' => 'Criar Calibração',
            'menu' => 'list-performance-calibrations',
            'buttonPermission' => ['ListPerformanceCalibrations'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/create_calibration', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_performance_calibration', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-calibration');
            exit;
        }

        $result = (new PerformanceCalibrationService())->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao criar calibração.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Calibração criada com sucesso!</div>';
        GenerateLog::generateLog('info', 'Calibração criada.', ['id' => $result['id']]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-calibration/' . $result['id']);
        exit;
    }
}
