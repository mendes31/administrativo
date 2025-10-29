<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmDocumentsRepository;

/**
 * Controller para Deletar Documento CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteDocument
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do documento não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $documentsRepo = new CrmDocumentsRepository();
        $document = $documentsRepo->getDocumentById((int)$id);

        if (!$document) {
            $_SESSION['msg'] = "Documento não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        // Deletar do banco de dados
        $result = $documentsRepo->deleteDocument((int)$id);

        if ($result) {
            // Deletar arquivo físico
            if (file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            $_SESSION['msg'] = "Documento excluído com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir documento.";
            $_SESSION['msg_type'] = "danger";
        }

        // Redirecionar de volta
        if (!empty($document['opportunity_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $document['opportunity_id']);
        } elseif (!empty($document['partner_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $document['partner_id']);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

