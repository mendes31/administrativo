<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\RoomServiceRequestAccessHelper;
use App\adms\Models\Repository\RoomRequestGroupsRepository;
use App\adms\Models\Repository\RoomServiceRequestsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Visualizar solicitação avulsa - Reserva de Salas
 */
class RoomsViewServiceRequest
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $id = (int)$id;
        if (!$id) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        $repo = new RoomServiceRequestsRepository();
        $request = $repo->getById($id);
        if (!$request) {
            $_SESSION['error'] = 'Solicitação não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        if (!RoomServiceRequestAccessHelper::currentUserMayViewServiceRequest($request)) {
            $_SESSION['error'] = 'Não tem permissão para ver esta solicitação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-list-service-requests');
            return;
        }

        // Ação: assumir (claim)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim') {
            $this->claim($request);
            // claim redireciona
        }

        $this->data['request'] = $request;
        $this->data['csrf_claim'] = CSRFHelper::generateCSRFToken('form_claim_room_service_request');

        $pageElements = [
            'title_head' => 'Visualizar Solicitação (Salas)',
            'menu' => 'RoomsListServiceRequests',
            'buttonPermission' => [
                'RoomsListServiceRequests',
                'RoomsUpdateServiceRequest',
                'RoomsDeleteServiceRequest',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rooms/view_service_request', $this->data);
        $loadView->loadView();
    }

    private function claim(array $request): void
    {
        if (!CSRFHelper::validateCSRFToken('form_claim_room_service_request', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . (int)$request['id']);
            exit;
        }

        $groupId = !empty($request['responsible_group_id']) ? (int)$request['responsible_group_id'] : 0;
        if (!$groupId) {
            $_SESSION['error'] = 'Esta solicitação não possui equipe responsável definida.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . (int)$request['id']);
            exit;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $groupsRepo = new RoomRequestGroupsRepository();
        $memberIds = $groupsRepo->getMemberUserIds($groupId);
        if (!in_array($userId, $memberIds, true)) {
            $_SESSION['error'] = 'Você não faz parte da equipe responsável por esta solicitação.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . (int)$request['id']);
            exit;
        }

        $repo = new RoomServiceRequestsRepository();
        $repo->update((int)$request['id'], [
            'claimed_by_user_id' => $userId,
            'claimed_at' => date('Y-m-d H:i:s'),
            'status' => 'in_progress',
        ]);

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Solicitação assumida com sucesso!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'rooms-view-service-request/' . (int)$request['id']);
        exit;
    }
}

