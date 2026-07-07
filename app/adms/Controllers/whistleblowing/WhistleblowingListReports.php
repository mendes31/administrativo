<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WhistleblowingReportsRepository;
use App\adms\Models\Services\WhistleblowingProtocolService;
use App\adms\Views\Services\LoadViewService;

/**
 * Listagem interna de denúncias (Compliance / Comitê de Ética).
 */
class WhistleblowingListReports
{
    private array $data = [];
    private int $page = 1;
    private int $perPage = 20;

    public function index(): void
    {
        $this->page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $this->perPage = isset($_GET['per_page']) ? max(1, (int) $_GET['per_page']) : 20;

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'category' => $_GET['category'] ?? '',
            'risk_level' => $_GET['risk_level'] ?? '',
            'assigned_user_id' => $_GET['assigned_user_id'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'include_archived' => $_GET['include_archived'] ?? '0',
        ];

        $repo = new WhistleblowingReportsRepository();
        $total = $repo->getTotalReports($filters);
        $this->data['reports'] = $repo->getAllReports($this->page, $this->perPage, $filters);
        $this->data['pagination'] = [
            'total' => $total,
            'per_page' => $this->perPage,
            'current_page' => $this->page,
            'last_page' => max(1, (int) ceil($total / $this->perPage)),
        ];
        $this->data['filters'] = $filters;
        $this->data['categories'] = WhistleblowingProtocolService::CATEGORIES;
        $this->data['risk_levels'] = WhistleblowingProtocolService::RISK_LEVELS;
        $this->data['statuses'] = WhistleblowingProtocolService::STATUSES;

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $pageElements = [
            'title_head' => 'Denúncias — Canal de Denúncias',
            'menu' => 'denuncias',
            'buttonPermission' => ['WhistleblowingViewReport', 'WhistleblowingUpdateStatus', 'WhistleblowingDashboard'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/reports/list', $this->data);
        $loadView->loadView();
    }
}
