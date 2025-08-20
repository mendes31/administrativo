<?php

namespace App\adms\Controllers\login;

use App\adms\Models\Repository\LogsRepository;
use App\adms\Models\Repository\LogAcessosRepository;

class Logout
{

    public function index(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
            $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
            $sessionRepo->invalidateSessionByUserIdAndSessionId($_SESSION['user_id'], $_SESSION['session_id']);
        }
        
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . $_ENV['URL_ADM'] . 'login');
        exit;
    }
}
