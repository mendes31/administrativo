<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Criar Campo Customizável do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateCustomField
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        // Exibir formulário
        $entityType = $_GET['entity_type'] ?? 'partner';
        
        // Calcular próxima ordem de exibição baseada nos campos existentes
        // Sempre incrementa de 10 em 10 (múltiplos de 10)
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $existingFields = $customFieldsRepo->getAllFields(['entity_type' => $entityType]);
        $nextOrder = 10; // Primeiro campo sempre começa em 10
        if (!empty($existingFields)) {
            $maxOrder = max(array_column($existingFields, 'display_order'));
            // Garantir que sempre seja múltiplo de 10
            $nextOrder = (intval($maxOrder / 10) + 1) * 10;
        }
        
        $this->data['field'] = [
            'entity_type' => $entityType,
            'field_type' => 'text',
            'is_required' => 0,
            'status' => 'active',
            'display_order' => $nextOrder
        ];
        
        // Passar campos existentes para exibir na view
        $this->data['existing_fields'] = $existingFields;

        $pageElements = [
            'title_head' => 'Novo Campo Customizável - CRM',
            'menu' => 'crm-list-custom-fields',
            'buttonPermission' => ['CrmCreateCustomField'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/custom_fields/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        // Calcular ordem automaticamente (múltiplos de 10)
        $entityType = $_POST['entity_type'] ?? 'partner';
        $customFieldsRepo = new CrmCustomFieldsRepository();
        $existingFields = $customFieldsRepo->getAllFields(['entity_type' => $entityType]);
        $displayOrder = 10; // Primeiro campo sempre começa em 10
        if (!empty($existingFields)) {
            $maxOrder = max(array_column($existingFields, 'display_order'));
            // Garantir que sempre seja múltiplo de 10
            $displayOrder = (intval($maxOrder / 10) + 1) * 10;
        }
        
        $data = [
            'entity_type' => $entityType,
            'field_name' => $_POST['field_name'] ?? '',
            'field_label' => $_POST['field_label'] ?? '',
            'field_type' => $_POST['field_type'] ?? 'text',
            'options' => $_POST['options'] ?? null,
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'display_order' => $displayOrder, // Usar ordem calculada automaticamente
            'status' => $_POST['status'] ?? 'active'
        ];

        // Validações
        if (empty($data['field_name'])) {
            $_SESSION['msg'] = "Nome do campo é obrigatório.";
            $_SESSION['msg_type'] = "warning";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-custom-field");
            exit;
        }

        if (empty($data['field_label'])) {
            $_SESSION['msg'] = "Label do campo é obrigatório.";
            $_SESSION['msg_type'] = "warning";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-custom-field");
            exit;
        }

        // Converter options para JSON se for select ou checkbox
        if (in_array($data['field_type'], ['select', 'checkbox']) && !empty($data['options'])) {
            $optionsArray = array_filter(array_map('trim', explode(',', $data['options'])));
            $data['options'] = json_encode($optionsArray);
        }

        $repo = new CrmCustomFieldsRepository();
        $result = $repo->createField($data);

        if ($result) {
            $_SESSION['msg'] = "Campo customizável criado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields?entity_type=" . $data['entity_type']);
        } else {
            $_SESSION['msg'] = "Erro ao criar campo customizável.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-custom-field");
        }
        exit;
    }
}

