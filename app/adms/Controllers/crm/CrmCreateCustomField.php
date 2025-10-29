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
        $this->data['field'] = [
            'entity_type' => $_GET['entity_type'] ?? 'partner',
            'field_type' => 'text',
            'is_required' => 0,
            'status' => 'active'
        ];

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
        $data = [
            'entity_type' => $_POST['entity_type'] ?? 'partner',
            'field_name' => $_POST['field_name'] ?? '',
            'field_label' => $_POST['field_label'] ?? '',
            'field_type' => $_POST['field_type'] ?? 'text',
            'options' => $_POST['options'] ?? null,
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'display_order' => (int)($_POST['display_order'] ?? 0),
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

