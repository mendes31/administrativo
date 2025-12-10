<?php

namespace App\adms\Controllers\rooms;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Models\Repository\MeetingRoomsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar sala de reunião
 */
class CreateMeetingRoom
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        } else {
            $this->showForm();
        }
    }

    private function showForm(): void
    {
        $pageElements = [
            'title_head' => 'Criar Sala de Reunião',
            'menu' => 'create-meeting-room',
            'buttonPermission' => [
                'ListMeetingRooms',
            ],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        $loadView = new LoadViewService('adms/Views/rooms/create', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_meeting_room', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Token de segurança inválido!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-meeting-room');
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
            'created_by' => $_SESSION['user_id'] ?? 0,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Nome da sala é obrigatório!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-meeting-room');
            exit;
        }

        if ($data['capacity'] < 1) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Capacidade deve ser maior que zero!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-meeting-room');
            exit;
        }

        // Processar upload de imagem se fornecido
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = ImageHelper::uploadImage($_FILES['image'], 'rooms', 5242880); // 5MB max
            if ($imagePath) {
                $data['image'] = $imagePath;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-warning" role="alert">Aviso: Erro ao fazer upload da imagem. A sala será criada sem imagem.</div>';
            }
        }

        try {
            $repository = new MeetingRoomsRepository();
            $roomId = $repository->create($data);

            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Sala criada com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-meeting-room/' . $roomId);
            exit;
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao criar sala: ' . htmlspecialchars($e->getMessage()) . '</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-meeting-room');
            exit;
        }
    }
}

