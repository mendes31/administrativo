<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class SacViewClient
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do cliente não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-clients");
            exit;
        }

        $clientsRepo = new SacClientsRepository();
        $this->data['client'] = $clientsRepo->getClientById((int)$id);

        if (!$this->data['client']) {
            $_SESSION['msg'] = "Cliente não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-clients");
            exit;
        }

        $ticketsRepo = new SacTicketsRepository();
        $this->data['tickets'] = $ticketsRepo->getAllTickets(1, 50, ['client_id' => (int)$id]);

        $clientId = (int) $this->data['client']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sac-view-client/' . $clientId;
        $this->data['log_resumo'] = LogResumoService::getResumo('sac_clients', $clientId, $returnUrl);

        $pageElements = [
            'title_head' => 'Visualizar Cliente - SAC',
            'menu' => 'sac-list-clients',
            'buttonPermission' => ['SacViewClient', 'SacUpdateClient', 'SacDeleteClient', 'SacCreateTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/clients/view", $this->data);
        $loadView->loadView();
    }
}
