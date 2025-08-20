<?php

namespace App\adms\Controllers\notifications;

use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;

class Notificacoes
{
    private array $data = [];

    public function index(): void
    {
        $usuarioId = $_SESSION['user_id'] ?? null;
        if (!$usuarioId) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }
        try {
            $repo = new NotificationsRepository();
            $this->data['notifications'] = $repo->getUserNotifications($usuarioId, 50);
        } catch (\Throwable $e) {
            $this->data['notifications'] = [];
            $this->data['error'] = 'Erro ao buscar notificações: ' . $e->getMessage();
        }
        $pageLayout = new PageLayoutService();
        $this->data = $pageLayout->configurePageElements([
            'title_head' => 'Notificações',
            'menu' => 'notificacoes',
            'buttonPermission' => [],
        ]);
        $loadView = new LoadViewService('adms/Views/notifications/notificacoes', $this->data);
        $loadView->loadView();
    }
} 