<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Views\Services\LoadViewService;

class ListGamificationPointLedger
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filterUser = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        if ($filterUser !== null && $filterUser <= 0) {
            $filterUser = null;
        }

        $repo = new GamificationLedgerRepository();
        $this->data['entries'] = $repo->listRecent($filterUser, 250);
        $this->data['filter_user_id'] = $filterUser;

        $pageElements = [
            'title_head' => 'Gamificação — Extrato de pontos',
            'menu' => 'ListGamificationPointLedger',
            'buttonPermission' => [],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/list_point_ledger', $this->data);
        $loadView->loadView();
    }
}
