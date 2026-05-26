<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Dashboard SAC com KPIs e métricas de atendimento
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacDashboard
{
    private array $data = [];

    public function index(): void
    {
        $ticketsRepo = new SacTicketsRepository();

        $this->data['open_count'] = $ticketsRepo->getOpenTicketsCount();
        $this->data['critical_count'] = $ticketsRepo->getCriticalTicketsCount();
        $this->data['sla_breached'] = $ticketsRepo->getSlaBreachedCount();
        $this->data['avg_response_time'] = $ticketsRepo->getAverageResponseTime();
        $this->data['avg_resolution_time'] = $ticketsRepo->getAverageResolutionTime();

        $this->data['counts_by_status'] = $ticketsRepo->getTicketCountsByStatus();
        $this->data['counts_by_category'] = $ticketsRepo->getTicketCountsByCategory();
        $this->data['counts_by_priority'] = $ticketsRepo->getTicketCountsByPriority();
        $this->data['counts_by_channel'] = $ticketsRepo->getTicketCountsByChannel();

        $this->data['recent_tickets'] = $ticketsRepo->getAllTickets(1, 5);

        // Layout
        $pageElements = [
            'title_head' => 'Dashboard - SAC',
            'menu' => 'sac-dashboard',
            'buttonPermission' => ['SacDashboard', 'SacListTickets', 'SacCreateTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/dashboard", $this->data);
        $loadView->loadView();
    }
}
