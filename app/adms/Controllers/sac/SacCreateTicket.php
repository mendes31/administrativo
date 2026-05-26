<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacTicketMessagesRepository;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\SacClientsRepository;
use App\adms\Models\Repository\SacSlaRulesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Services\SacEmailService;
use App\adms\Models\Services\SacTicketNotificationService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar chamado no SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacCreateTicket
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $ticketsRepo = new SacTicketsRepository();
        $this->data['next_code'] = $ticketsRepo->getNextCode();

        $this->loadFormSelects();

        // Layout
        $pageElements = [
            'title_head' => 'Novo Chamado - SAC',
            'menu' => 'sac-list-tickets',
            'buttonPermission' => ['SacCreateTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/tickets/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_ticket_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Atualize a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-ticket");
            exit;
        }

        $data = [
            'subject' => trim($_POST['subject'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'product' => trim($_POST['product'] ?? '') ?: null,
            'batch' => trim($_POST['batch'] ?? '') ?: null,
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'priority' => $_POST['priority'] ?? 'Média',
            'channel' => $_POST['channel'] ?? 'Portal',
            'assigned_user_id' => !empty($_POST['assigned_user_id']) ? (int)$_POST['assigned_user_id'] : null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
            'status' => 'Aberto',
            'created_by' => (int)($_SESSION['user_id'] ?? 1),
        ];

        if (empty($data['subject'])) {
            $_SESSION['msg'] = "O assunto é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-ticket");
            exit;
        }

        if (empty($data['description'])) {
            $_SESSION['msg'] = "A descrição é obrigatória.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-ticket");
            exit;
        }

        if (empty($data['client_id'])) {
            $_SESSION['msg'] = "Selecione o cliente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-ticket");
            exit;
        }

        $ticketsRepo = new SacTicketsRepository();
        $data['code'] = $ticketsRepo->getNextCode();

        // Aplicar SLA baseado na regra encontrada
        $slaRepo = new SacSlaRulesRepository();
        $categoryId = $data['category_id'] ?? 0;
        $slaRule = $slaRepo->findRuleForTicket($categoryId, $data['priority']);

        if ($slaRule) {
            $now = new \DateTime();
            $responseDeadline = (clone $now)->modify('+' . (int)$slaRule['response_time_hours'] . ' hours');
            $resolutionDeadline = (clone $now)->modify('+' . (int)$slaRule['resolution_time_hours'] . ' hours');

            $data['sla_response_deadline'] = $responseDeadline->format('Y-m-d H:i:s');
            $data['sla_resolution_deadline'] = $resolutionDeadline->format('Y-m-d H:i:s');
        }

        $ticketId = $ticketsRepo->createTicket($data);

        if ($ticketId) {
            $this->processAttachments($ticketId);

            $ticket = $ticketsRepo->getTicketById($ticketId);
            if ($ticket) {
                SacEmailService::sendTicketCreatedToClient($ticket);

                SacTicketNotificationService::notifyNewTicket(
                    $ticketId,
                    (string) ($ticket['code'] ?? ''),
                    (string) ($ticket['subject'] ?? ''),
                    !empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null
                );
            }

            $_SESSION['msg'] = "Chamado criado com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
        } else {
            $_SESSION['msg'] = "Erro ao criar chamado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-ticket");
        }
        exit;
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

        $this->data['priorities'] = ['Baixa', 'Média', 'Alta', 'Urgente'];
        $this->data['channels'] = ['WhatsApp', 'E-mail', 'Telefone', 'Portal'];
    }
}
