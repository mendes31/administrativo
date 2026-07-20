<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Services\PerformanceCycleService;
use App\adms\Views\Services\LoadViewService;

/**
 * Editar ciclo de desempenho.
 */
class UpdatePerformanceCycle
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($cycleId);
            $this->data['cycle'] = $repository->getById($cycleId) ?? $cycle;
        }

        $pageElements = [
            'title_head' => 'Editar Ciclo de Desempenho',
            'menu' => 'list-performance-cycles',
            'buttonPermission' => [
                'ListPerformanceCycles',
                'ViewPerformanceCycle',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/update_cycle', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_performance_cycle', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';

            return;
        }

        $service = new PerformanceCycleService();
        $result = $service->update($id, $_POST);

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar ciclo.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Ciclo atualizado com sucesso!</div>';
        GenerateLog::generateLog('info', 'Ciclo de desempenho atualizado.', ['id' => $id]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-cycle/' . $id);
        exit;
    }
}
