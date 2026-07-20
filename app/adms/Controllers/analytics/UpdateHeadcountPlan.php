<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\HeadcountPlansRepository;
use App\adms\Models\Services\HeadcountPlanService;
use App\adms\Views\Services\LoadViewService;

class UpdateHeadcountPlan
{
    private array|string|null $data = null;

    public function index(string|int $id): void
    {
        $id = (int) $id;
        $repo = new HeadcountPlansRepository();
        $plan = $repo->getById($id);
        if (!$plan) {
            $_SESSION['error'] = 'Linha de quadro não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-headcount-plans');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFHelper::validateCSRFToken('form_update_headcount_plan', $_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token inválido.';
            } else {
                $result = (new HeadcountPlanService($repo))->update($id, $_POST);
                if ($result['ok']) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Linha atualizada!</div>';
                    GenerateLog::generateLog('info', 'Linha de headcount atualizada.', ['id' => $id]);
                    header('Location: ' . $_ENV['URL_ADM'] . 'view-headcount-plan/' . $id);
                    exit;
                }
                $_SESSION['error'] = $result['error'] ?? 'Erro ao atualizar.';
                $this->data['form'] = array_merge($plan, $_POST);
            }
        }
        if (!isset($this->data['form'])) {
            $this->data['form'] = $plan;
        }
        $this->data['plan'] = $plan;

        $pageElements = [
            'title_head' => 'Editar Linha de Quadro',
            'menu' => 'list-headcount-plans',
            'buttonPermission' => ['ListHeadcountPlans', 'ViewHeadcountPlan'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/update_headcount_plan', $this->data))->loadView();
    }
}
