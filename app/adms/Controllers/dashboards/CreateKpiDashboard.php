<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\KpiDashboardRepository;
use App\adms\Models\Repository\DynamicReportsRepository;

class CreateKpiDashboard extends PageLayoutService
{
    private KpiDashboardRepository $repository;
    private DynamicReportsRepository $reportsRepository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new KpiDashboardRepository();
        $this->reportsRepository = new DynamicReportsRepository();
    }

    public function index(): void
    {
        // Buscar todos os relatórios disponíveis
        $reports = $this->reportsRepository->getAll();
        
        $this->loadView('dashboards/form', [
            'dashboard' => null,
            'reports' => $reports
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $_ENV['URL_ADM'] . "create-kpi-dashboard");
            exit;
        }

        try {
            $userId = $_SESSION['user_id'] ?? 0;
            
            $data = [
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS),
                'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS),
                'layout' => filter_input(INPUT_POST, 'layout', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'grid',
                'refresh_interval' => filter_input(INPUT_POST, 'refresh_interval', FILTER_VALIDATE_INT),
                'is_public' => filter_input(INPUT_POST, 'is_public', FILTER_VALIDATE_BOOLEAN),
                'created_by' => $userId
            ];

            // Validação básica
            if (empty($data['name'])) {
                throw new \Exception("O nome do dashboard é obrigatório!");
            }

            $dashboardId = $this->repository->create($data);

            // Processar widgets se enviados
            $widgets = $_POST['widgets'] ?? [];
            if (is_array($widgets) && !empty($widgets)) {
                foreach ($widgets as $index => $widgetData) {
                    if (!empty($widgetData['title'])) {
                        $this->repository->createWidget([
                            'dashboard_id' => $dashboardId,
                            'report_id' => !empty($widgetData['report_id']) ? (int)$widgetData['report_id'] : null,
                            'title' => $widgetData['title'],
                            'widget_type' => $widgetData['widget_type'] ?? 'number',
                            'size' => $widgetData['size'] ?? 'medium',
                            'position_order' => $index * 10,
                            'color_scheme' => $widgetData['color_scheme'] ?? null,
                            'icon' => $widgetData['icon'] ?? null,
                            'value_format' => $widgetData['value_format'] ?? 'number',
                            'value_prefix' => $widgetData['value_prefix'] ?? null,
                            'value_suffix' => $widgetData['value_suffix'] ?? null,
                            'target_value' => !empty($widgetData['target_value']) ? (float)$widgetData['target_value'] : null,
                            'config_json' => !empty($widgetData['config_json']) ? $widgetData['config_json'] : null
                        ]);
                    }
                }
            }

            $_SESSION['msg'] = "<p class='alert alert-success'>Dashboard criado com sucesso!</p>";
            header("Location: " . $_ENV['URL_ADM'] . "view-kpi-dashboard?id=" . $dashboardId);
            exit;

        } catch (\Exception $e) {
            $_SESSION['msg'] = "<p class='alert alert-danger'>Erro ao criar dashboard: " . $e->getMessage() . "</p>";
            header("Location: " . $_ENV['URL_ADM'] . "create-kpi-dashboard");
            exit;
        }
    }
}

