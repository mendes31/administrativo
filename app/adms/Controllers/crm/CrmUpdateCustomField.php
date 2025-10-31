<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmCustomFieldsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Editar Campo Customizável do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUpdateCustomField
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do campo não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        // Buscar campo
        $repo = new CrmCustomFieldsRepository();
        $this->data['field'] = $repo->getFieldById((int)$id);

        if (!$this->data['field']) {
            $_SESSION['msg'] = "Campo não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields");
            exit;
        }

        $pageElements = [
            'title_head' => 'Editar Campo Customizável - CRM',
            'menu' => 'crm-list-custom-fields',
            'buttonPermission' => ['CrmUpdateCustomField'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/custom_fields/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        // Permitir alterar a ordem (pode ser qualquer valor, mas recomenda-se múltiplos de 10)
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        
        $data = [
            'id' => $id,
            'entity_type' => $_POST['entity_type'] ?? 'partner',
            'field_name' => $_POST['field_name'] ?? '',
            'field_label' => $_POST['field_label'] ?? '',
            'field_type' => $_POST['field_type'] ?? 'text',
            'options' => $_POST['options'] ?? null,
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'display_order' => $displayOrder, // Permite alterar a ordem
            'status' => $_POST['status'] ?? 'active'
        ];

        // Converter options para JSON
        if (in_array($data['field_type'], ['select', 'checkbox']) && !empty($data['options'])) {
            $optionsArray = array_filter(array_map('trim', explode(',', $data['options'])));
            $data['options'] = json_encode($optionsArray);
        }

        $repo = new CrmCustomFieldsRepository();
        $result = $repo->updateField($data);

        if ($result) {
            $_SESSION['msg'] = "Campo customizável atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields?entity_type=" . $data['entity_type']);
        } else {
            $_SESSION['msg'] = "Erro ao atualizar campo customizável.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-custom-field/" . $id);
        }
        exit;
    }
}

