<?php

namespace App\adms\Controllers\notifications;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Views\Services\LoadViewService;

class ListNotifications
{
    private array $data = [];

    public function index(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'login');
            exit;
        }

        $repo = new NotificationsRepository();

        // Marcar uma como lida (GET mark=id)
        if (isset($_GET['mark']) && is_numeric($_GET['mark'])) {
            $repo->markAsRead((int)$_GET['mark'], $userId);
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-notifications');
            exit;
        }

        // Marcar todas como lidas (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['mark_all_read'])) {
            if (CSRFHelper::validateCSRFToken('list_notifications', $_POST['csrf_token'] ?? '')) {
                $repo->markAllAsRead($userId);
                $_SESSION['msg'] = '<div class="alert alert-success">Todas as notificações foram marcadas como lidas.</div>';
            }
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'list-notifications');
            exit;
        }

        $this->data['notifications'] = $repo->listForUser($userId, 50);
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('list_notifications');

        $pageElements = [
            'title_head' => 'Minhas Notificações',
            'menu' => 'list-notifications',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/notifications/list', $this->data);
        $loadView->loadView();
    }
}
