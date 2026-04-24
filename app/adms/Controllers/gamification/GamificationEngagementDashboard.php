<?php

declare(strict_types=1);

namespace App\adms\Controllers\gamification;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Views\Services\LoadViewService;

class GamificationEngagementDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $monthRef = (string)($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthRef)) {
            $monthRef = date('Y-m');
        }

        $repo = new GamificationProgramRepository();
        $this->data['month_ref'] = $monthRef;
        $this->data['indicators'] = $repo->getEngagementIndicators($monthRef);
        $this->data['department_engagement'] = $repo->getDepartmentEngagement($monthRef);
        $this->data['levels'] = $repo->listActiveLevels();
        $this->data['badges'] = $repo->listActiveBadges();
        $this->data['weekly_missions'] = $repo->listActiveWeeklyMissions();
        $this->data['anti_fraud_events'] = $repo->listRecentAntiFraudEvents(25);

        $pageElements = [
            'title_head' => 'Gamificação — Dashboard de engajamento',
            'menu' => 'GamificationEngagementDashboard',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/gamification/engagement_dashboard', $this->data);
        $loadView->loadView();
    }
}
