<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar sala de reunião
 */
class UpdateMeetingRoom
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        $this->data = [];

        $id = $id ? (int)$id : 0;

        if ($id === 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID da sala não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
        } else {
            $this->showForm($id);
        }
    }

    private function showForm(int $id): void
    {
        $repository = new MeetingRoomsRepository();
        $room = $repository->getById($id);

        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $this->data['form'] = $room;

        $pageElements = [
            'title_head' => 'Editar Sala de Reunião',
            'menu' => 'update-meeting-room',
            'buttonPermission' => [
                'ListMeetingRooms',
                'ViewMeetingRoom',
                'DeleteMeetingRoom',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/update', $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('form_update_meeting_room', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-meeting-room/' . $id);
            exit;
        }

        $repository = new MeetingRoomsRepository();
        $room = $repository->getById($id);

        if (!$room) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Sala não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-meeting-rooms');
            exit;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'capacity' => (int)($_POST['capacity'] ?? 1),
            'location' => trim($_POST['location'] ?? ''),
            'floor' => trim($_POST['floor'] ?? ''),
            'building' => trim($_POST['building'] ?? ''),
            'status' => $_POST['status'] ?? 'active',
            'requires_approval' => isset($_POST['requires_approval']) && $_POST['requires_approval'] === '1',
            'min_advance_booking_hours' => !empty($_POST['min_advance_booking_hours']) ? (int)$_POST['min_advance_booking_hours'] : null,
            'max_advance_booking_days' => !empty($_POST['max_advance_booking_days']) ? (int)$_POST['max_advance_booking_days'] : null,
            'booking_duration_limit_hours' => !empty($_POST['booking_duration_limit_hours']) ? (int)$_POST['booking_duration_limit_hours'] : null,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Nome da sala é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-meeting-room/' . $id);
            exit;
        }

        if ($data['capacity'] < 1) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Capacidade deve ser maior que zero!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-meeting-room/' . $id);
            exit;
        }

        // Processar upload de imagem se fornecido
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Deletar imagem antiga se existir
            if (!empty($room['image'])) {
                $oldImagePath = 'public/adms/uploads/' . $room['image'];
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            
            $imagePath = ImageHelper::uploadImage($_FILES['image'], 'rooms', 5242880); // 5MB max
            if ($imagePath) {
                $data['image'] = $imagePath;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Aviso: Erro ao fazer upload da imagem. A imagem anterior será mantida.</div>';
            }
        }

        try {
            $repository->update($id, $data);

            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Sala atualizada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $id);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao atualizar sala: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-meeting-room/' . $id);
            exit;
        }
    }
}

