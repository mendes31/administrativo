<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SacTicketsRepository;
use App\adms\Models\Repository\SacTicketMessagesRepository;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para visualizar chamado do SAC
 *
 * @package App\adms\Controllers\sac
 * @author Rafael Mendes
 */
class SacViewTicket
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

        $ticketsRepo = new SacTicketsRepository();
        $this->data['ticket'] = $ticketsRepo->getTicketById((int)$id);

        if (!$this->data['ticket']) {
            $_SESSION['msg'] = "Chamado não encontrado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        $messagesRepo = new SacTicketMessagesRepository();
        $messages = $messagesRepo->getMessagesByTicketId((int)$id);
        $this->data['attachments'] = $messagesRepo->getAttachmentsByTicketId((int)$id);
        $this->data['status_log'] = $messagesRepo->getStatusLogByTicketId((int)$id);

        $attachmentsByMsg = [];
        foreach ($this->data['attachments'] as $att) {
            $mid = $att['message_id'] ?? 0;
            if ($mid) {
                $attachmentsByMsg[$mid][] = $att;
            }
        }
        foreach ($messages as &$msg) {
            $msg['attachments'] = $attachmentsByMsg[$msg['id'] ?? 0] ?? [];
        }
        unset($msg);
        $this->data['messages'] = $messages;

        // Log de alterações resumido
        $ticketId = (int)$this->data['ticket']['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'sac-view-ticket/' . $ticketId;
        $this->data['log_resumo'] = LogResumoService::getResumo('sac_tickets', $ticketId, $returnUrl);

        // Categorias para select de alteração de status
        $categoriesRepo = new SacCategoriesRepository();
        $this->data['categories'] = $categoriesRepo->getActiveCategories();

        $this->data['statuses'] = ['Aberto', 'Em análise', 'Em atendimento', 'Aguardando cliente', 'Resolvido', 'Encerrado'];

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();

        $departmentsRepo = new DepartmentsRepository();
        $this->data['departments'] = $departmentsRepo->getAllDepartmentsSelect();

        // Layout
        $code = $this->data['ticket']['code'] ?? '';
        $pageElements = [
            'title_head' => 'Chamado #' . $code . ' - SAC',
            'menu' => 'sac-list-tickets',
            'buttonPermission' => ['SacViewTicket', 'SacUpdateTicket', 'SacDeleteTicket', 'SacReplyTicket', 'SacTransferTicket'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/tickets/view", $this->data);
        $loadView->loadView();
    }
}
