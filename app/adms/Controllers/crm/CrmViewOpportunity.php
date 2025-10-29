<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\CrmNotesRepository;
use App\adms\Models\Repository\CrmDocumentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar Oportunidade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmViewOpportunity
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da oportunidade não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        // Buscar dados da oportunidade
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $this->data['opportunity'] = $opportunitiesRepo->getOpportunity((int)$id);

        if (!$this->data['opportunity']) {
            $_SESSION['msg'] = "Oportunidade não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        // Buscar histórico de movimentações
        $this->data['stage_history'] = $opportunitiesRepo->getStageHistory((int)$id);

        // Buscar atividades
        $activitiesRepo = new CrmActivitiesRepository();
        $this->data['activities'] = $activitiesRepo->getActivitiesByOpportunity((int)$id);

        // Buscar notas
        $notesRepo = new CrmNotesRepository();
        $this->data['notes'] = $notesRepo->getNotesByOpportunity((int)$id);

        // Buscar documentos
        $documentsRepo = new CrmDocumentsRepository();
        $this->data['documents'] = $documentsRepo->getDocumentsByOpportunity((int)$id);

        // Layout
        $pageElements = [
            'title_head' => 'Visualizar Oportunidade - CRM',
            'menu' => 'crm-kanban-pipeline',
            'buttonPermission' => ['CrmViewOpportunity', 'CrmUpdateOpportunity', 'CrmDeleteOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/opportunities/view", $this->data);
        $loadView->loadView();
    }
}

