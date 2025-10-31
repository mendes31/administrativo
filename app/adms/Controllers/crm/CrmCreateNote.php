<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmNotesRepository;

/**
 * Controller para criar Nota CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateNote
{
    public function index(): void
    {
        // Suporta tanto POST normal quanto AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Método não permitido']);
                exit;
            }
            $_SESSION['msg'] = "Método não permitido.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
            exit;
        }

        // Converter valores vazios para null
        $partnerId = !empty($_POST['partner_id']) ? (int)$_POST['partner_id'] : null;
        $opportunityId = !empty($_POST['opportunity_id']) ? (int)$_POST['opportunity_id'] : null;
        
        $data = [
            'partner_id' => $partnerId,
            'opportunity_id' => $opportunityId,
            'content' => trim($_POST['content'] ?? ''),
            'is_important' => isset($_POST['is_important']) ? 1 : 0,
        ];

        // Validações
        if (empty($data['content'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'O conteúdo da nota é obrigatório']);
                exit;
            }
            $_SESSION['msg'] = "O conteúdo da nota é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($data);
            exit;
        }

        if (empty($data['opportunity_id']) && empty($data['partner_id'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'A nota deve estar vinculada a uma oportunidade ou parceiro']);
                exit;
            }
            $_SESSION['msg'] = "A nota deve estar vinculada a uma oportunidade ou parceiro.";
            $_SESSION['msg_type'] = "danger";
            $this->redirectBack($data);
            exit;
        }

        $notesRepo = new CrmNotesRepository();
        $result = $notesRepo->createNote($data);

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Nota criada com sucesso!',
                    'note_id' => $result
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar nota']);
            }
            exit;
        }

        if ($result) {
            $_SESSION['msg'] = "Nota criada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao criar nota.";
            $_SESSION['msg_type'] = "danger";
        }

        $this->redirectBack($data);
    }

    private function redirectBack(array $data): void
    {
        if (!empty($data['opportunity_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-opportunity/" . $data['opportunity_id']);
        } elseif (!empty($data['partner_id'])) {
            header("Location: " . $_ENV['URL_ADM'] . "crm-view-partner/" . $data['partner_id']);
        } else {
            header("Location: " . $_ENV['URL_ADM'] . "crm-kanban-pipeline");
        }
        exit;
    }
}

