<?php

declare(strict_types=1);

namespace App\adms\Controllers\analytics;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\PulseCampaignsRepository;
use App\adms\Views\Services\LoadViewService;

class ListPulseCampaigns
{
    private array|string|null $data = null;

    public function index(string|int|null $page = null): void
    {
        $page = (int) ($page ?: ($_GET['page'] ?? 1));
        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        if (!empty($_GET['campaign_type'])) {
            $filters['campaign_type'] = $_GET['campaign_type'];
        }
        $repo = new PulseCampaignsRepository();
        $this->data['campaigns'] = $repo->getAll($filters, $page, 20);
        $total = $repo->count($filters);
        $pagination = PaginationService::generatePagination($total, 20, $page, 'list-pulse-campaigns', $filters);
        $this->data['pagination'] = $pagination['html'] ?? '';
        $this->data['filters'] = $filters;

        $pageElements = [
            'title_head' => 'Pesquisas Pulse / eNPS',
            'menu' => 'list-pulse-campaigns',
            'buttonPermission' => ['CreatePulseCampaign', 'ViewPulseCampaign', 'UpdatePulseCampaign', 'RespondPulseCampaign'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/analytics/list_pulse_campaigns', $this->data))->loadView();
    }
}
