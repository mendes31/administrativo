<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmNotesRepository;

/**
 * Controller para deletar Nota CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmDeleteNote
{
    public function index(string|int|null $id = null): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if (!$id) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID da nota não informado']);
                exit;
            }
            $_SESSION['msg'] = "ID da nota não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $notesRepo = new CrmNotesRepository();
        
        // Buscar nota para redirect
        $note = $notesRepo->getNoteById((int)$id);
        
        if (!$note) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Nota não encontrada']);
                exit;
            }
            $_SESSION['msg'] = "Nota não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        $result = $notesRepo->deleteNote((int)$id);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Nota excluída com sucesso!' : 'Erro ao excluir nota'
            ]);
            exit;
        }

        if ($result) {
            $_SESSION['msg'] = "Nota excluída com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao excluir nota.";
            $_SESSION['msg_type'] = "danger";
        }

        // Redirecionar de volta
        if (!empty($note['opportunity_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $note['opportunity_id']);
        } elseif (!empty($note['partner_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $note['partner_id']);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

