<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\KpiDashboardRepository;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * API endpoint para buscar dados de um widget específico
 */
class GetKpiWidgetData
{
    private KpiDashboardRepository $dashboardRepo;
    private DynamicReportsRepository $reportsRepo;
    private DynamicQueryBuilderService $queryBuilder;

    public function __construct()
    {
        $this->dashboardRepo = new KpiDashboardRepository();
        $this->reportsRepo = new DynamicReportsRepository();
        $this->queryBuilder = new DynamicQueryBuilderService();
    }

    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ob_clean();

        try {
            $widgetId = filter_input(INPUT_GET, 'widget_id', FILTER_VALIDATE_INT);
            
            if (!$widgetId) {
                echo json_encode(['success' => false, 'error' => 'Widget ID inválido']);
                exit;
            }

            // Buscar widget
            $sql = "SELECT * FROM adms_kpi_widgets WHERE id = :id";
            $conn = (new \App\adms\Models\Services\DbConnection())->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $widgetId, \PDO::PARAM_INT);
            $stmt->execute();
            $widget = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$widget) {
                echo json_encode(['success' => false, 'error' => 'Widget não encontrado']);
                exit;
            }

            // Se o widget tem relatório vinculado, executar a query
            if ($widget['report_id']) {
                $report = $this->reportsRepo->findById((int)$widget['report_id']);
                
                if ($report) {
                    $result = $this->queryBuilder->executeReport($report);
                    
                    // Processar dados conforme o tipo de widget
                    $processedData = $this->processDataForWidget($result, $widget);
                    
                    echo json_encode([
                        'success' => true,
                        'widget' => $widget,
                        'data' => $processedData,
                        'raw_result' => $result
                    ]);
                    exit;
                }
            }

            echo json_encode([
                'success' => true,
                'widget' => $widget,
                'data' => null
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Processar dados de acordo com o tipo de widget
     */
    private function processDataForWidget(array $result, array $widget): array
    {
        if (!$result['success'] || empty($result['data'])) {
            return ['value' => 0, 'formatted' => '0'];
        }

        $data = $result['data'];
        $widgetType = $widget['widget_type'];

        switch ($widgetType) {
            case 'number':
            case 'gauge':
                // Para widgets numéricos, pegar o primeiro valor da primeira linha
                $firstRow = $data[0] ?? [];
                $value = !empty($firstRow) ? reset($firstRow) : 0;
                
                return [
                    'value' => $value,
                    'formatted' => $this->formatValue($value, $widget),
                    'target' => $widget['target_value'] ?? null
                ];

            case 'chart_bar':
            case 'chart_line':
            case 'chart_pie':
            case 'chart_doughnut':
                // Para gráficos, preparar labels e valores
                $labels = [];
                $values = [];
                
                foreach ($data as $row) {
                    $rowValues = array_values($row);
                    $labels[] = $rowValues[0] ?? '';
                    $values[] = $rowValues[1] ?? 0;
                }
                
                return [
                    'labels' => $labels,
                    'values' => $values,
                    'datasets' => [
                        [
                            'label' => $widget['title'],
                            'data' => $values
                        ]
                    ]
                ];

            case 'table':
                // Para tabelas, retornar os dados como estão
                return [
                    'rows' => $data,
                    'columns' => !empty($data) ? array_keys($data[0]) : []
                ];

            default:
                return ['value' => 0];
        }
    }

    /**
     * Formatar valor conforme configuração do widget
     */
    private function formatValue($value, array $widget): string
    {
        $format = $widget['value_format'] ?? 'number';
        $prefix = $widget['value_prefix'] ?? '';
        $suffix = $widget['value_suffix'] ?? '';

        switch ($format) {
            case 'currency':
                $formatted = 'R$ ' . number_format((float)$value, 2, ',', '.');
                break;
            
            case 'percentage':
                $formatted = number_format((float)$value, 2, ',', '.') . '%';
                break;
            
            case 'number':
                $formatted = number_format((float)$value, 0, ',', '.');
                break;
            
            default:
                $formatted = (string)$value;
        }

        return $prefix . $formatted . $suffix;
    }
}

