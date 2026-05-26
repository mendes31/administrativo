<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacTicketMessagesRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Controller para transferir chamado do SAC para outro agente/departamento
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacTransferTicket
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do chamado não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = "Método não permitido.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sac_transfer_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Atualize a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
            exit;
        }

        $ticketId = (int)$id;
        $newAssignedUserId = !empty($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null;
        $newDepartmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $notes = trim($_POST['notes'] ?? '');

        $ticketsRepo = new SacTicketsRepository();
        $ticket = $ticketsRepo->getTicketById($ticketId);

        if (!$ticket) {
            $_SESSION['msg'] = "Chamado não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        $updateData = [];
        if ($newAssignedUserId !== null) {
            $updateData['assigned_user_id'] = $newAssignedUserId;
        }
        if ($newDepartmentId !== null) {
            $updateData['department_id'] = $newDepartmentId;
        }

        $result = !empty($updateData) ? $ticketsRepo->updateTicket($ticketId, $updateData) : true;

        if ($result) {
            // Registrar log de status com notas da transferência
            $changedBy = (int)($_SESSION['user_id'] ?? 1);
            $ticketsRepo->updateStatus($ticketId, $ticket['status'], $changedBy);

            // Montar mensagem de sistema sobre a transferência
            $systemMessage = 'Chamado transferido';
            if ($newAssignedUserId) {
                $usersRepo = new UsersRepository();
                $newAgent = $usersRepo->getUser($newAssignedUserId);
                $agentName = $newAgent['name'] ?? 'Usuário #' . $newAssignedUserId;
                $systemMessage = 'Chamado transferido para ' . $agentName;
            }
            if (!empty($notes)) {
                $systemMessage .= '. Motivo: ' . $notes;
            }

            $messagesRepo = new SacTicketMessagesRepository();
            $messagesRepo->createMessage([
                'ticket_id' => $ticketId,
                'user_id' => (int)($_SESSION['user_id'] ?? 1),
                'message' => $systemMessage,
                'is_internal_note' => 0,
                'sender_type' => 'system',
            ]);

            $_SESSION['msg'] = "Chamado transferido com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao transferir chamado.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
        exit;
    }
}
