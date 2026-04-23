<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Views\Services\LoadViewService;

class GamificationLeaderboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new GamificationLedgerRepository();
        $this->data['leaderboard'] = $repo->getLeaderboard(40);

        $pageElements = [
            'title_head' => 'Ranking de pontos — Gamificação',
            'menu' => 'GamificationLeaderboard',
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/leaderboard', $this->data);
        $loadView->loadView();
    }
}
