<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\CompanyEventsRepository;
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

        // Sem RSVP obrigatório: marcar como lido ao abrir (mesma ideia do dashboard)
        if (empty($event['requires_rsvp']) && $userId > 0) {
            $repo->upsertRead($eventId, $userId);
        }

        $rsvp = $userId > 0 ? $repo->getRsvpForUser($eventId, $userId) : null;
        $rsvpId = (int)($rsvp['id'] ?? 0);
        $guests = $rsvpId > 0 ? $repo->getGuestsForRsvpId($rsvpId) : [];

        $this->data['event'] = $event;
        $this->data['rsvp'] = $rsvp;
        $this->data['rsvp_guests'] = $guests;
        $this->data['can_edit'] = in_array('UpdateCompanyEvent', $btnPerms, true);
        $this->data['event_id'] = $eventId;

        $loadView = new LoadViewService('adms/Views/companyEvents/view', $this->data);
        $loadView->loadView();
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
