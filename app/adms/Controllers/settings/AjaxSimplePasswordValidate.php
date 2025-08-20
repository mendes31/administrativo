<?php

namespace App\adms\Controllers\settings;

use App\adms\Models\Repository\LoginRepository;

class AjaxSimplePasswordValidate
{
    private function checarSessaoInvalidadaAjax()
    {
        if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
            $sessionRepo = new \App\adms\Models\Repository\AdmsSessionsRepository();
            $sess = $sessionRepo->getSessionByUserIdAndSessionId($_SESSION['user_id'], $_SESSION['session_id']);
            $userRepo = new \App\adms\Models\Repository\LoginRepository();
            $user = $userRepo->getUserById($_SESSION['user_id']);
            $motivos = [];
            if (!$sess || !$user) {
                $motivos[] = 'Sessão inválida';
            } else {
                if ($sess['status'] === 'invalidada') {
                    if ($user['status'] === 'Inativo') {
                        $motivos[] = 'Usuário Inativo';
                    }
                    if ($user['bloqueado'] === 'Sim') {
                        $motivos[] = 'Usuário Bloqueado';
                    }
                }
            }
            if (!empty($motivos) || ($sess && $sess['status'] === 'invalidada')) {
                $msg = !empty($motivos) ? implode(' e ', $motivos) . '! Contate o Administrador do sistema.' : 'Sessão invalidada. Faça login novamente.';
                session_destroy();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $msg, 'logout' => true]);
                exit;
            }
        }
    }

    public function index(): void
    {
        $this->checarSessaoInvalidadaAjax();

        // Log simples para debug
        file_put_contents('logs/teste_ajax.txt', date('Y-m-d H:i:s') . " - Entrou no validate AJAX simples\n", FILE_APPEND);

        $input = json_decode(file_get_contents('php://input'), true);
        $senha = $input['senha'] ?? '';
        $username = $_SESSION['user_username'] ?? null;

        if (!$username) {
            file_put_contents('logs/teste_ajax.txt', date('Y-m-d H:i:s') . " - Usuário não autenticado\n", FILE_APPEND);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado!']);
            exit;
        }

        $repo = new LoginRepository();
        $user = $repo->getUser($username);
        if (!$user) {
            file_put_contents('logs/teste_ajax.txt', date('Y-m-d H:i:s') . " - Usuário não encontrado no banco\n", FILE_APPEND);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado!']);
            exit;
        }

        if (password_verify($senha, $user['password'])) {
            file_put_contents('logs/teste_ajax.txt', date('Y-m-d H:i:s') . " - Senha OK\n", FILE_APPEND);
            echo json_encode(['sucesso' => true]);
        } else {
            file_put_contents('logs/teste_ajax.txt', date('Y-m-d H:i:s') . " - Senha incorreta\n", FILE_APPEND);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta!']);
        }
        exit;
    }
} 