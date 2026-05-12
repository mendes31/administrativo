<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Models\Repository\CrmNotesRepository;
use App\adms\Models\Repository\CrmDocumentsRepository;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar Parceiro CRM com timeline completo
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmViewPartner
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do parceiro não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
            exit;
        }

        // Buscar dados do parceiro
        $partnersRepo = new CrmPartnersRepository();
        $this->data['partner'] = $partnersRepo->getPartner((int)$id);

        if (!$this->data['partner']) {
            $_SESSION['msg'] = "Parceiro não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
            exit;
        }

        // Buscar oportunidades do parceiro
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $this->data['opportunities'] = $opportunitiesRepo->getOpportunitiesByPartner((int)$id);

        // Buscar atividades do parceiro
        $activitiesRepo = new CrmActivitiesRepository();
        $this->data['activities'] = $activitiesRepo->getActivitiesByPartner((int)$id);

        // Buscar notas do parceiro
        $notesRepo = new CrmNotesRepository();
        $this->data['notes'] = $notesRepo->getNotesByPartner((int)$id);

        // Buscar documentos do parceiro
        $documentsRepo = new CrmDocumentsRepository();
        $this->data['documents'] = $documentsRepo->getDocumentsByPartner((int)$id);
        
        // Buscar tags do parceiro e todas as tags disponíveis
        $tagsRepo = new CrmTagsRepository();
        $this->data['partner_tags'] = $tagsRepo->getPartnerTags((int)$id);
        $this->data['all_tags'] = $tagsRepo->getAllTags();

        // Carregar campos customizáveis e valores
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('partner');
        $this->data['custom_field_values'] = $customFieldsRepo->getPartnerFieldValues((int)$id);

        $pid = (int) $this->data['partner']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'crm-view-partner/' . $pid;
        $this->data['log_resumo'] = LogResumoService::getResumo('crm_partners', $pid, $returnUrl);

        // Layout
        $pageElements = [
            'title_head' => 'Visualizar Parceiro - CRM',
            'menu' => 'crm-list-partners',
            'buttonPermission' => ['CrmViewPartner', 'CrmUpdatePartner', 'CrmDeletePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/partners/view", $this->data);
        $loadView->loadView();
    }
}

