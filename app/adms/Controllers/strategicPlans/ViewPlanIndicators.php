<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Models\Repository\StrategicIndicatorsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;

class ViewPlanIndicators
{
    private $plansRepository;
    private $indicatorsRepository;
    private array $data = [];

    public function __construct()
    {
        $this->plansRepository = new StrategicPlansRepository();
        $this->indicatorsRepository = new StrategicIndicatorsRepository();
    }

    public function index(string|int|null $id = null): void
    {
        $planId = (int)$id;
        
        if (!$planId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do plano não fornecido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        $this->viewPlanIndicators($planId);
    }

    private function viewPlanIndicators(int $planId): void
    {
        // Obter dados do plano
        $plan = $this->plansRepository->getById($planId);
        
        if (!$plan) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Plano estratégico não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        // Obter indicadores do plano
        $indicators = $this->indicatorsRepository->getByStrategicPlanId($planId);
        
        // Calcular métricas do plano
        $planMetrics = $this->calculatePlanMetrics($indicators);

        // Elementos de página
        $pageElements = [
            'title_head' => 'Indicadores do Plano: ' . $plan['title'],
            'menu' => 'view-plan-indicators',
            'buttonPermission' => ['ViewPlanIndicators'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos
        $this->data['plan'] = $plan;
        $this->data['indicators'] = $indicators;
        $this->data['planMetrics'] = $planMetrics;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicPlans/view-plan-indicators", $this->data);
        $loadView->loadView();
    }

    private function calculatePlanMetrics(array $indicators): array
    {
        if (empty($indicators)) {
            return [
                'total_indicators' => 0,
                'active_indicators' => 0,
                'average_progress' => 0,
                'on_target_indicators' => 0,
                'below_target_indicators' => 0,
                'above_target_indicators' => 0
            ];
        }

        $total = count($indicators);
        $active = 0;
        $progressSum = 0;
        $onTarget = 0;
        $belowTarget = 0;
        $aboveTarget = 0;

        foreach ($indicators as $indicator) {
            if ($indicator['status'] === 'Ativo') {
                $active++;
            }

            // Calcular progresso se temos meta e valor atual
            if (!empty($indicator['target_value']) && !empty($indicator['current_value'])) {
                $target = (float)$indicator['target_value'];
                $current = (float)$indicator['current_value'];
                
                if ($target > 0) {
                    $progress = ($current / $target) * 100;
                    $progressSum += $progress;

                    if ($progress >= 100) {
                        $onTarget++;
                    } elseif ($progress >= 80) {
                        $belowTarget++; // Próximo da meta
                    } else {
                        $aboveTarget++; // Muito abaixo da meta
                    }
                }
            }
        }

        return [
            'total_indicators' => $total,
            'active_indicators' => $active,
            'average_progress' => $total > 0 ? round($progressSum / $total, 1) : 0,
            'on_target_indicators' => $onTarget,
            'below_target_indicators' => $belowTarget,
            'above_target_indicators' => $aboveTarget
        ];
    }
}



