<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmTagsRepository;

/**
 * Controller para deletar Tag CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteTag
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da tag não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
            exit;
        }

        $tagsRepo = new CrmTagsRepository();
        $result = $tagsRepo->deleteTag((int)$id);

        if ($result) {
            $_SESSION['msg'] = "Tag excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir tag. Verifique se não há parceiros ou oportunidades usando esta tag.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
        exit;
    }
}

