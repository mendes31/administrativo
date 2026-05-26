<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacTicketMessagesRepository;

/**
 * Controller para responder/adicionar mensagem a um chamado SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacReplyTicket
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

        if (!CSRFHelper::validateCSRFToken('sac_reply_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Atualize a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
            exit;
        }

        $ticketId = (int)$id;
        $message = trim($_POST['message'] ?? '');
        $isInternal = isset($_POST['is_internal_note']) ? 1 : 0;
        $senderType = $_POST['sender_type'] ?? 'agent';

        if (empty($message)) {
            $_SESSION['msg'] = "A mensagem é obrigatória.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
            exit;
        }

        $messagesRepo = new SacTicketMessagesRepository();
        $messageData = [
            'ticket_id' => $ticketId,
            'user_id' => (int)($_SESSION['user_id'] ?? 1),
            'message' => $message,
            'is_internal_note' => $isInternal,
            'sender_type' => $senderType,
        ];

        $messageId = $messagesRepo->createMessage($messageData);

        if (!$messageId) {
            $_SESSION['msg'] = "Erro ao enviar mensagem.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
            exit;
        }

        if ($senderType === 'agent') {
            $ticketsRepo = new SacTicketsRepository();
            $ticket = $ticketsRepo->getTicketById($ticketId);

            if ($ticket && empty($ticket['first_response_at'])) {
                $ticketsRepo->updateTicket($ticketId, [
                    'first_response_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Upload de anexos
        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'sac'
                . DIRECTORY_SEPARATOR . 'attachments'
                . DIRECTORY_SEPARATOR . $ticketId;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileCount = count($_FILES['attachments']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $originalName = $_FILES['attachments']['name'][$i];
                $tmpName = $_FILES['attachments']['tmp_name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $mimeType = $_FILES['attachments']['type'][$i];

                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $relativePath = 'storage/sac/attachments/' . $ticketId . '/' . $safeName;
                    $messagesRepo->createAttachment([
                        'ticket_id' => $ticketId,
                        'message_id' => $messageId,
                        'file_name' => $originalName,
                        'file_path' => $relativePath,
                        'file_size' => $fileSize,
                        'file_type' => $mimeType,
                        'uploaded_by' => (int)($_SESSION['user_id'] ?? 1),
                    ]);
                }
            }
        }

        $_SESSION['msg'] = "Mensagem enviada com sucesso!";
        $_SESSION['msg_type'] = "success";
        header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
        exit;
    }
}
