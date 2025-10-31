<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar Oportunidade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateOpportunity
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        // Gerar próximo código
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $this->data['next_code'] = $opportunitiesRepo->getNextOpportunityCode();

        // Capturar partner_id da URL (se vier da página do parceiro)
        $this->data['preselected_partner_id'] = $_GET['partner_id'] ?? null;

        // Dados para selects
        $partnersRepo = new CrmPartnersRepository();
        $this->data['partners'] = $partnersRepo->getAllPartnersSelect();

        $stagesRepo = new CrmPipelineStagesRepository();
        $this->data['stages'] = $stagesRepo->getActivePipelineStages();

        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();

        // Carregar campos customizáveis para oportunidades
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('opportunity');
        $this->data['custom_field_values'] = []; // Vazio para criação

        // Layout
        $pageElements = [
            'title_head' => 'Nova Oportunidade - CRM',
            'menu' => 'crm-list-opportunities',
            'buttonPermission' => ['CrmCreateOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/opportunities/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'code' => $_POST['code'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'partner_id' => $_POST['partner_id'] ?? null,
            'responsible_user_id' => $_POST['responsible_user_id'] ?? $_SESSION['user_id'],
            'stage_id' => $_POST['stage_id'] ?? 1, // Primeira etapa por padrão
            'value' => str_replace(',', '.', $_POST['value'] ?? '0'),
            'probability' => $_POST['probability'] ?? 50,
            'expected_close_date' => $_POST['expected_close_date'] ?? null,
            'next_action' => $_POST['next_action'] ?? null,
            'next_action_date' => $_POST['next_action_date'] ?? null,
            'source' => $_POST['source'] ?? null,
            'notes' => $_POST['notes'] ?? null,
        ];

        // Validações
        if (empty($data['title'])) {
            $_SESSION['msg'] = "O título é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-opportunity");
            exit;
        }

        if (empty($data['partner_id'])) {
            $_SESSION['msg'] = "Selecione o parceiro.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-opportunity");
            exit;
        }

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $opportunityId = $opportunitiesRepo->createOpportunity($data);

        if ($opportunityId) {
            // Salvar campos customizáveis
            $customFieldsRepo = new CrmCustomFieldsRepository();
            $customFields = $customFieldsRepo->getFieldsByEntity('opportunity');
            
            $customFieldValues = [];
            foreach ($customFields as $field) {
                $fieldName = 'custom_field_' . $field['id'];
                
                // Tratar checkbox (array) e outros tipos
                if (isset($_POST[$fieldName])) {
                    if (is_array($_POST[$fieldName])) {
                        // Checkbox: converter array para string separada por vírgulas
                        $customFieldValues[$field['id']] = implode(',', $_POST[$fieldName]);
                    } else {
                        // Outros tipos: usar valor direto
                        $customFieldValues[$field['id']] = $_POST[$fieldName];
                    }
                } elseif ($field['field_type'] === 'checkbox') {
                    // Checkbox não marcado: salvar vazio
                    $customFieldValues[$field['id']] = '';
                }
            }
            
            if (!empty($customFieldValues)) {
                $customFieldsRepo->saveOpportunityFieldValues($opportunityId, $customFieldValues);
            }

            $_SESSION['msg'] = "Oportunidade criada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        } else {
            $_SESSION['msg'] = "Erro ao criar oportunidade.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-opportunity");
        }
        exit;
    }
}

