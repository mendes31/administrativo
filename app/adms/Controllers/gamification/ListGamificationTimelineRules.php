<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationTimelineRulesRepository;
use App\adms\Views\Services\LoadViewService;

class ListGamificationTimelineRules
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new GamificationTimelineRulesRepository();
        $this->data['rules'] = $repo->listAll();

        $pageElements = [
            'title_head' => 'Gamificação — Regras da timeline',
            'menu' => 'ListGamificationTimelineRules',
            'buttonPermission' => [
                'UpdateGamificationTimelineRule',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/list_timeline_rules', $this->data);
        $loadView->loadView();
    }
}
