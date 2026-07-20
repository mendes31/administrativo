<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\HeadcountPlanService;
use App\adms\Views\Services\LoadViewService;

class CreateHeadcountPlan
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_create_headcount_plan', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new HeadcountPlanService())->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Linha de quadro criada!</div>';
                    GenerateLog::generateLog('info', 'Linha de headcount criada.', ['id' => $result['id']]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-headcount-plan/' . $result['id']);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro ao criar.';
                $this->data['form'] = $_POST;
            }
        }
        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'department_id' => '',
                'position_id' => '',
                'period_year' => date('Y'),
                'period_month' => date('n'),
                'planned_count' => '0',
                'notes' => '',
            ];
        }
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();

        $pageElements = [
            'title_head' => 'Nova Linha de Quadro',
            'menu' => 'list-headcount-plans',
            'buttonPermission' => ['ListHeadcountPlans'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/create_headcount_plan', $this->data))->loadView();
    }
}
