<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmCustomFieldsRepository;

/**
 * Deletar Campo Customizável do CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteCustomField
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do campo não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields");
            exit;
        }

        $repo = new CrmCustomFieldsRepository();
        $field = $repo->getFieldById((int)$id);

        if (!$field) {
            $_SESSION['msg'] = "Campo não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields");
            exit;
        }

        $result = $repo->deleteField((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Campo customizável excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir campo customizável.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-custom-fields?entity_type=" . ($field['entity_type'] ?? ''));
        exit;
    }
}

