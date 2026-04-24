<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationLedgerRepository;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Models\Services\GamificationAwardService;
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

        $this->data['scope'] = $scope;
        $this->data['month_ref'] = $monthRef;
        $this->data['department_id'] = $departmentId;
        $this->data['departments'] = $repo->getDepartmentRankingOptions();
        $this->data['leaderboard'] = $repo->getLeaderboard(
            40,
            $scope,
            $departmentId > 0 ? $departmentId : null,
            $monthRef
        );
        $programRepo = new GamificationProgramRepository();
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId > 0) {
            $pointsFilterMonth = $programRepo->getUserMonthlyPoints($userId, $monthRef);
            $prevMonthRef = self::previousMonthRef($monthRef);
            $pointsPrevMonth = $programRepo->getUserMonthlyPoints($userId, $prevMonthRef);
            $this->data['my_level'] = $programRepo->resolveUserLevel($pointsFilterMonth);
            $this->data['my_level_previous_month'] = $programRepo->resolveUserLevel($pointsPrevMonth);
            $this->data['my_monthly_points'] = $pointsFilterMonth;
            $this->data['my_previous_month_points'] = $pointsPrevMonth;
            $this->data['level_prev_month_ref'] = $prevMonthRef;
            (new GamificationAwardService())->syncBadgesForUser($userId);
            $this->data['my_badges'] = $programRepo->listBadgesByUser($userId);
            $missionMonthStart = $monthRef . '-01';
            $this->data['my_missions'] = $programRepo->listWeeklyMissionProgressByUser($userId, $missionMonthStart);
            $this->data['mission_month_ref'] = $monthRef;
            $this->data['mission_month_range_label'] = self::formatMonthRangeBr($monthRef);
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

    /** @param string $monthRef Y-m */
    private static function previousMonthRef(string $monthRef): string
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $monthRef)) {
            $monthRef = date('Y-m');
        }
        $first = \DateTimeImmutable::createFromFormat('Y-m-d', $monthRef . '-01');
        if ($first === false) {
            return date('Y-m', strtotime('first day of last month'));
        }

        return $first->modify('-1 month')->format('Y-m');
    }

    /** Intervalo do mês civil (dd/mm — dd/mm) para o filtro Y-m. */
    private static function formatMonthRangeBr(string $monthRef): string
    {
        $monthRef = preg_match('/^\d{4}-\d{2}$/', $monthRef) ? $monthRef : date('Y-m');
        $first = \DateTimeImmutable::createFromFormat('Y-m-d', $monthRef . '-01');
        if ($first === false) {
            return $monthRef;
        }
        $last = $first->modify('last day of this month');

        return $first->format('d/m/Y') . ' — ' . $last->format('d/m/Y');
    }
}
