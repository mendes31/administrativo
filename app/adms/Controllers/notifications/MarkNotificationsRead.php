<?php
declare(strict_types=1);

namespace App\adms\Controllers\notifications;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\NotificationsRepository;

class MarkNotificationsRead
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }

        if (!CSRFHelper::validateCSRFToken('navbar_notifications_mark_all', $_POST['csrf_token'] ?? '')) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $repo = new NotificationsRepository();
        $ok = $repo->markAllAsRead($userId);
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível atualizar notificações']);
            return;
        }

        echo json_encode(['success' => true]);
    }
}

