<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacTicketMessagesRepository;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar chamado do SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacUpdateTicket
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do chamado não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        $ticketsRepo = new SacTicketsRepository();
        $this->data['ticket'] = $ticketsRepo->getTicketById((int)$id);

        if (!$this->data['ticket']) {
            $_SESSION['msg'] = "Chamado não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        $messagesRepo = new SacTicketMessagesRepository();
        $this->data['attachments'] = $messagesRepo->getAttachmentsByTicketId((int)$id);

        $this->loadFormSelects();

        // Layout
        $pageElements = [
            'title_head' => 'Editar Chamado - SAC',
            'menu' => 'sac-list-tickets',
            'buttonPermission' => ['SacUpdateTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/tickets/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_ticket_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Atualize a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-ticket/" . $id);
            exit;
        }

        $ticketsRepo = new SacTicketsRepository();
        $oldTicket = $ticketsRepo->getTicketById($id);

        if (!$oldTicket) {
            $_SESSION['msg'] = "Chamado não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        $newStatus = $_POST['status'] ?? $oldTicket['status'];

        $data = [
            'subject' => trim($_POST['subject'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'product' => trim($_POST['product'] ?? '') ?: null,
            'batch' => trim($_POST['batch'] ?? '') ?: null,
            'status' => $newStatus,
            'priority' => $_POST['priority'] ?? 'Média',
            'channel' => $_POST['channel'] ?? 'Portal',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'assigned_user_id' => !empty($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'sla_response_deadline' => $oldTicket['sla_response_deadline'] ?? null,
            'sla_resolution_deadline' => $oldTicket['sla_resolution_deadline'] ?? null,
            'sla_response_breached' => $oldTicket['sla_response_breached'] ?? 0,
            'sla_resolution_breached' => $oldTicket['sla_resolution_breached'] ?? 0,
            'first_response_at' => $oldTicket['first_response_at'] ?? null,
            'resolved_at' => $oldTicket['resolved_at'] ?? null,
            'closed_at' => $oldTicket['closed_at'] ?? null,
        ];

        if (empty($data['subject']) || empty($data['client_id'])) {
            $_SESSION['msg'] = "Campos obrigatórios não preenchidos.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-ticket/" . $id);
            exit;
        }

        // Se é a primeira resposta e first_response_at é null, registrar
        if (empty($oldTicket['first_response_at'])) {
            $data['first_response_at'] = date('Y-m-d H:i:s');
        }

        $result = $ticketsRepo->updateTicket($id, $data);

        // Se o status mudou, registrar no log de status
        if ($result && $oldTicket['status'] !== $newStatus) {
            $changedBy = (int)($_SESSION['user_id'] ?? 1);
            $ticketsRepo->updateStatus($id, $newStatus, $changedBy);
        }

        $this->deleteMarkedAttachments($id);
        $this->processAttachments($id);

        if ($result) {
            $_SESSION['msg'] = "Chamado atualizado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
        } else {
            $_SESSION['msg'] = "Erro ao atualizar chamado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-ticket/" . $id);
        }
        exit;
    }

    private function deleteMarkedAttachments(int $ticketId): void
    {
        if (empty($_POST['delete_attachments']) || !is_array($_POST['delete_attachments'])) {
            return;
        }

        $messagesRepo = new SacTicketMessagesRepository();
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

        foreach ($_POST['delete_attachments'] as $attId) {
            $attId = (int)$attId;
            $attachments = $messagesRepo->getAttachmentsByTicketId($ticketId);
            foreach ($attachments as $att) {
                if ((int)$att['id'] === $attId) {
                    $filePath = $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $att['file_path'] ?? '');
                    if (!empty($att['file_path']) && file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    $messagesRepo->deleteAttachment($attId);
                    break;
                }
            }
        }
    }

    private function processAttachments(int $ticketId, ?int $messageId = null): void
    {
        if (empty($_FILES['attachments']) || !is_array($_FILES['attachments']['name'])) {
            return;
        }

        $maxSize = 10 * 1024 * 1024;
        $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','csv','txt','zip','rar','ppt','pptx'];

        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
            . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'sac'
            . DIRECTORY_SEPARATOR . 'attachments'
            . DIRECTORY_SEPARATOR . $ticketId;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $messagesRepo = new SacTicketMessagesRepository();
        $fileCount = count($_FILES['attachments']['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $_FILES['attachments']['name'][$i];
            $tmpName = $_FILES['attachments']['tmp_name'][$i];
            $fileSize = $_FILES['attachments']['size'][$i];
            $mimeType = $_FILES['attachments']['type'][$i];

            if ($fileSize > $maxSize) {
                continue;
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                continue;
            }

            $safeName = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
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

    private function loadFormSelects(): void
    {
        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $clientsRepo = new SacClientsRepository();
        $this->data['clients'] = $clientsRepo->getActiveClients();

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $departmentsRepo = new DepartmentsRepository();
        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();

        $this->data['statuses'] = ['Aberto', 'Em análise', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];
        $this->data['priorities'] = ['Baixa', 'Média', 'Alta', 'Urgente'];
        $this->data['channels'] = ['WhatsApp', 'E-mail', 'Telefone', 'Portal'];
    }
}
