<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmDocumentsRepository;

/**
 * Controller para Download de Documento CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDownloadDocument
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

        $filePath = $document['file_path'];

        if (!file_exists($filePath)) {
            $_SESSION['msg'] = "Arquivo não encontrado no servidor.";
            $_SESSION['msg_type'] = "danger";
            
            if (!empty($document['opportunity_id'])) {
                header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $document['opportunity_id']);
            } elseif (!empty($document['partner_id'])) {
                header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $document['partner_id']);
            } else {
                header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            }
            exit;
        }

        // Forçar download
        header('Content-Type: ' . ($document['file_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }
}

