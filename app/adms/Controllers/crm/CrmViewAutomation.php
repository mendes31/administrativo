<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmAutomationsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Visualizar Automação do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmViewAutomation
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da automação não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-automations");
            exit;
        }

        $repo = new CrmAutomationsRepository();
        $this->data['automation'] = $repo->getAutomationById((int)$id);

        if (!$this->data['automation']) {
            $_SESSION['msg'] = "Automação não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-automations");
            exit;
        }

        $aid = (int) $id;
        $returnUrl = $_ENV['URL_ADM'] . 'crm-view-automation/' . $aid;
        $this->data['log_resumo'] = LogResumoService::getResumo('crm_automations', $aid, $returnUrl);

        // Buscar logs de execução
        $this->data['logs'] = $repo->getAutomationLogs((int)$id, 100);

        $pageElements = [
            'title_head' => 'Visualizar Automação - CRM',
            'menu' => 'crm-list-automations',
            'buttonPermission' => ['CrmViewAutomation'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/automations/view", $this->data);
        $loadView->loadView();
    }
}

