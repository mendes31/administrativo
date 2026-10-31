<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CompanyEventExternalInviteAccess;
use App\adms\Helpers\CompanyEventRsvpAccessHelper;
use App\adms\Helpers\CompanyEventUploadHelper;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SendWhatsAppService;
use App\adms\Helpers\NavbarLayoutCacheHelper;
use App\adms\Helpers\NotificationOpenHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\CompanyEventExternalInvitesRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\CompanyEventExternalInviteMessage;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewCompanyEvent
{
    private array $data = [];

    public function index(string|null $id = null): void
    {
        $eventId = (int)($id ?? 0);
        if ($eventId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Evento inválido.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }

        $repo = new CompanyEventsRepository();
        $event = $repo->getByIdWithDisplayContext($eventId);
        if (!$event) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Evento não encontrado.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);

        if ($userId > 0 && isset($_GET['mark_notification']) && is_numeric($_GET['mark_notification'])) {
            (new NotificationsRepository())->markAsRead((int)$_GET['mark_notification'], $userId);
        }

        $pageElements = [
            'title_head' => 'Visualizar evento',
            'menu' => 'list-company-events',
            'buttonPermission' => [
                'ViewCompanyEvent',
                'ListCompanyEvents',
                'CreateCompanyEvent',
                'UpdateCompanyEvent',
                'DeleteCompanyEvent',
                'CompanyEventReport',
                'ResendCompanyEventPush',
            ],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $btnPerms = $this->data['buttonPermission'] ?? [];
        $menuPerms = $this->data['menuPermission'] ?? [];

        // Rota já exige permissão da página ViewCompanyEvent (ou superusuário). Aqui só restringe
        // visibilidade do conteúdo: quem só "visualiza" (sem gestão na lista) vê apenas eventos publicados/ativos.
        $hasManagementAccess = $this->hasManagementEventAccess($btnPerms, $menuPerms);
        if (!$hasManagementAccess && !$repo->isEventVisibleToCollaborators($event)) {
            $_SESSION['msg'] = '<div class="alert alert-warning">Este evento não está disponível para visualização.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['external_invite_action'])) {
            $this->handleExternalInvitePost($event, $eventId, $userId, $btnPerms);
            return;
        }

        // Sem RSVP obrigatório: marcar como lido ao abrir (mesma ideia do dashboard)
        if (empty($event['requires_rsvp']) && $userId > 0) {
            $repo->upsertRead($eventId, $userId);
            NavbarLayoutCacheHelper::clear();
        }

        if ($userId > 0) {
            $repo->autoDeclineRsvpIfDeadlinePassed($eventId, $userId);
        }
        $rsvp = $userId > 0 ? $repo->getRsvpForUser($eventId, $userId) : null;
        $rsvpId = (int)($rsvp['id'] ?? 0);
        $guests = $rsvpId > 0 ? $repo->getGuestsForRsvpId($rsvpId) : [];

        $this->data['event'] = $event;
        $eventImages = $repo->getImagesForEvent($eventId);
        $this->data['event_images'] = $eventImages;
        $canExternal = CompanyEventExternalInviteAccess::canManage($event, $userId, $btnPerms);
        $this->data['can_manage_external_invites'] = $canExternal;
        $this->data['external_invites'] = $canExternal
            ? (new CompanyEventExternalInvitesRepository())->listByEvent($eventId)
            : [];
        $this->data['external_invite_csrf'] = $canExternal
            ? CSRFHelper::generateCSRFToken('form_company_event_external_invite')
            : '';
        $firstPath = (string) ($eventImages[0]['image_path'] ?? '');
        $this->data['external_invite_image_url'] = $firstPath !== ''
            ? NotificationOpenHelper::urlOnCurrentHost(CompanyEventUploadHelper::serveUrl($firstPath))
            : '';
        $this->data['rsvp'] = $rsvp;
        $this->data['rsvp_guests'] = $guests;
        $this->data['can_edit'] = in_array('UpdateCompanyEvent', $btnPerms, true);
        $this->data['can_admin_rsvp_others'] = CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $userId, $btnPerms);
        $this->data['event_id'] = $eventId;
        $returnUrl = $_ENV['URL_ADM'] . 'view-company-event/' . $eventId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_company_events', $eventId, $returnUrl);

        $this->data['prefill_manage_rsvp'] = null;
        if (!empty($event['requires_rsvp']) && CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $userId, $btnPerms)) {
            $manageUid = (int)($_GET['manage_rsvp'] ?? 0);
            if ($manageUid > 0) {
                $usersRepo = new UsersRepository();
                $basics = $usersRepo->getActiveUserBasics($manageUid);
                if ($basics !== null) {
                    $this->data['prefill_manage_rsvp'] = $basics;
                }
            }
        }

        $loadView = new LoadViewService('adms/Views/companyEvents/view', $this->data);
        $loadView->loadView();
    }

    /**
     * @param array<string, mixed> $event
     * @param array<int, string> $btnPerms
     */
    private function handleExternalInvitePost(array $event, int $eventId, int $userId, array $btnPerms): void
    {
        $back = ($_ENV['URL_ADM'] ?? '') . 'view-company-event/' . $eventId . '#convidados-externos';
        if (!CompanyEventExternalInviteAccess::canManage($event, $userId, $btnPerms)) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Só o criador do evento, ou quem pode criar e editar eventos, inclui convidados de fora da empresa.</div>';
            header('Location: ' . $back);
            exit;
        }
        if (!CSRFHelper::validateCSRFToken('form_company_event_external_invite', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = '<div class="alert alert-danger">A sessão do formulário expirou. Atualize a página e tente de novo.</div>';
            header('Location: ' . $back);
            exit;
        }

        $repo = new CompanyEventExternalInvitesRepository();
        $action = (string) ($_POST['external_invite_action'] ?? '');
        $inviteId = (int) ($_POST['invite_id'] ?? 0);

        if ($action === 'add') {
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $reason = trim((string) ($_POST['invite_reason'] ?? ''));
            $phone = CompanyEventExternalInviteMessage::normalizePhone((string) ($_POST['phone'] ?? ''));
            if ($name === '' || $reason === '' || $phone === null) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Informe nome, motivo do convite e um celular com DDD.</div>';
            } elseif (mb_strlen($name) > 200 || mb_strlen($reason) > 255) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Nome ou motivo do convite passou do tamanho permitido.</div>';
            } elseif ($repo->phoneExists($eventId, $phone)) {
                $_SESSION['msg'] = '<div class="alert alert-warning">Já existe um convite deste evento para este celular.</div>';
            } else {
                $repo->add($eventId, $name, $reason, $phone, $userId);
                $_SESSION['msg'] = '<div class="alert alert-success">Convidado incluído. Use o WhatsApp para enviar o convite.</div>';
            }
        } elseif ($action === 'status' && $inviteId > 0) {
            $status = (string) ($_POST['status'] ?? '');
            if (!in_array($status, ['confirmed', 'declined', 'pending'], true) || $repo->findForEvent($eventId, $inviteId) === null) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível atualizar a resposta deste convidado.</div>';
            } else {
                $repo->setStatus($eventId, $inviteId, $status);
                $_SESSION['msg'] = '<div class="alert alert-success">Resposta do convidado atualizada.</div>';
            }
        } elseif ($action === 'whatsapp' && $inviteId > 0) {
            $invite = $repo->findForEvent($eventId, $inviteId);
            if ($invite === null) {
                $_SESSION['msg'] = '<div class="alert alert-danger">Convidado não encontrado.</div>';
            } else {
                $inviteUrl = CompanyEventExternalInviteMessage::inviteUrl((string) ($invite['token'] ?? ''));
                $text = CompanyEventExternalInviteMessage::message(
                    (string) ($invite['full_name'] ?? ''),
                    (string) ($invite['invite_reason'] ?? ''),
                    $event,
                    $inviteUrl
                );
                $result = SendWhatsAppService::sendMessage((string) ($invite['phone'] ?? ''), $text);
                if (!empty($result['success'])) {
                    $_SESSION['msg'] = '<div class="alert alert-success">Convite enviado pelo número cadastrado na API de WhatsApp.</div>';
                } else {
                    $_SESSION['msg'] = '<div class="alert alert-danger">Não foi possível enviar pelo WhatsApp: '
                        . htmlspecialchars((string) ($result['error'] ?? 'erro desconhecido'), ENT_QUOTES, 'UTF-8')
                        . '</div>';
                }
            }
        } elseif ($action === 'delete' && $inviteId > 0 && $repo->findForEvent($eventId, $inviteId) !== null) {
            $repo->delete($eventId, $inviteId);
            $_SESSION['msg'] = '<div class="alert alert-success">Convidado removido da lista.</div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Ação inválida.</div>';
        }

        header('Location: ' . $back);
        exit;
    }

    /**
     * @param array<int, string> $btnPerms
     * @param array<int, string> $menuPerms
     */
    private function hasManagementEventAccess(array $btnPerms, array $menuPerms): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }
        if (in_array('ListCompanyEvents', $menuPerms, true)) {
            return true;
        }
        foreach (['CreateCompanyEvent', 'UpdateCompanyEvent', 'DeleteCompanyEvent', 'CompanyEventReport'] as $p) {
            if (in_array($p, $btnPerms, true)) {
                return true;
            }
        }
        return false;
    }
}
