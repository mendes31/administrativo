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
 * Controller para editar Oportunidade CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUpdateOpportunity
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        // Buscar oportunidade
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $this->data['opportunity'] = $opportunitiesRepo->getOpportunity((int)$id);

        if (!$this->data['opportunity']) {
            $_SESSION['msg'] = "Oportunidade não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        // Dados para selects
        $partnersRepo = new CrmPartnersRepository();
        $this->data['partners'] = $partnersRepo->getAllPartnersSelect();

        $stagesRepo = new CrmPipelineStagesRepository();
        $this->data['stages'] = $stagesRepo->getActiveStages();

        // Filtrar apenas usuários do departamento comercial (respeitando hierarquia)
        $permissionService = new \App\adms\Models\Services\CrmPermissionService();
        $this->data['users'] = $permissionService::getCommercialDepartmentUsers();

        // Carregar campos customizáveis e valores
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $this->data['custom_fields'] = $customFieldsRepo->getFieldsByEntity('opportunity');
        $this->data['custom_field_values'] = $customFieldsRepo->getOpportunityFieldValues((int)$id);

        // Layout
        $pageElements = [
            'title_head' => 'Editar Oportunidade - CRM',
            'menu' => 'crm-list-opportunities',
            'buttonPermission' => ['CrmUpdateOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/opportunities/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $data = [
            'id' => $id, // Prioriza ID da URL
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'partner_id' => $_POST['partner_id'] ?? null,
            'responsible_user_id' => $_POST['responsible_user_id'] ?? $_SESSION['user_id'],
            'stage_id' => $_POST['stage_id'] ?? 1,
            'value' => str_replace(',', '.', $_POST['value'] ?? '0'),
            'probability' => $_POST['probability'] ?? 50,
            'expected_close_date' => $_POST['expected_close_date'] ?? null,
            'next_action' => $_POST['next_action'] ?? null,
            'next_action_date' => $_POST['next_action_date'] ?? null,
            'source' => $_POST['source'] ?? null,
            'status' => $_POST['status'] ?? 'Aberta',
            'notes' => $_POST['notes'] ?? null,
        ];

        // Validações
        if (empty($data['title']) || empty($data['partner_id'])) {
            $_SESSION['msg'] = "Campos obrigatórios não preenchidos.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-opportunity/" . $id);
            exit;
        }

        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $result = $opportunitiesRepo->updateOpportunity($data);

        if ($result) {
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
                $customFieldsRepo->saveOpportunityFieldValues($id, $customFieldValues);
            }

            $_SESSION['msg'] = "Oportunidade atualizada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $data['id']);
        } else {
            $_SESSION['msg'] = "Erro ao atualizar oportunidade.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-opportunity/" . $data['id']);
        }
        exit;
    }
}

