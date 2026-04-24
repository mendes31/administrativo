<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Views\Services\LoadViewService;

class GamificationLeaderboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $repo = new GamificationLedgerRepository();
        $scope = (string)($_GET['scope'] ?? 'general');
        if (!in_array($scope, ['general', 'monthly', 'department'], true)) {
            $scope = 'general';
        }
        $monthRef = (string)($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthRef)) {
            $monthRef = date('Y-m');
        }
        $departmentId = (int)($_GET['department_id'] ?? 0);
        if ($scope !== 'department') {
            $departmentId = 0;
        }

        $this->data['scope'] = $scope;
        $this->data['month_ref'] = $monthRef;
        $this->data['department_id'] = $departmentId;
        $this->data['departments'] = $repo->getDepartmentRankingOptions();
        $this->data['leaderboard'] = $repo->getLeaderboard(40, $scope, $departmentId > 0 ? $departmentId : null, $monthRef);
        $programRepo = new GamificationProgramRepository();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            $points = $programRepo->getUserTotalPoints($userId);
            $this->data['my_level'] = $programRepo->resolveUserLevel($points);
            $this->data['my_badges'] = $programRepo->listBadgesByUser($userId);
            $this->data['my_weekly_missions'] = $programRepo->listWeeklyMissionProgressByUser(
                $userId,
                (new \DateTimeImmutable('monday this week'))->format('Y-m-d')
            );
        }

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
