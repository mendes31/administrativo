<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\HeadcountPlansRepository;
use App\adms\Models\Services\HeadcountPlanService;
use App\adms\Views\Services\LoadViewService;

class ViewHeadcountPlan
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
        $metrics = (new HeadcountPlanService($repo))->withActual($plan);
        $this->data['plan'] = array_merge($plan, $metrics);

        $pageElements = [
            'title_head' => 'Quadro — Detalhe',
            'menu' => 'list-headcount-plans',
            'buttonPermission' => ['ListHeadcountPlans', 'UpdateHeadcountPlan'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/view_headcount_plan', $this->data))->loadView();
    }
}
