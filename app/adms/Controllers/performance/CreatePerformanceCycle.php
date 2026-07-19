<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\PerformanceCycleService;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar ciclo de desempenho.
 */
class CreatePerformanceCycle
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'name' => '',
                'year' => (int) date('Y'),
                'period_start' => date('Y') . '-01-01',
                'period_end' => date('Y') . '-12-31',
                'status' => 'draft',
                'description' => '',
            ];
        }

        $pageElements = [
            'title_head' => 'Criar Ciclo de Desempenho',
            'menu' => 'create-performance-cycle',
            'buttonPermission' => ['ListPerformanceCycles'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/performance/create_cycle', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_performance_cycle', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-performance-cycle');
            exit;
        }

        $service = new PerformanceCycleService();
        $result = $service->create($_POST, (int) ($_SESSION['user_id'] ?? 0));

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao criar ciclo.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Ciclo criado com sucesso!</div>';
        GenerateLog::generateLog('info', 'Ciclo de desempenho criado.', ['id' => $result['id']]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-performance-cycle/' . $result['id']);
        exit;
    }
}
