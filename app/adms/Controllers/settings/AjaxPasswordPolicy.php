<?php

namespace App\adms\Controllers\settings;

use App\adms\Controllers\Services\ValidationUserLogin;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;

class AjaxPasswordPolicy
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

    public function validatePassword(): void
    {
        // Recebe a senha enviada via AJAX
        $input = json_decode(file_get_contents('php://input'), true);
        $senha = $input['senha'] ?? '';

        // Pega o usuário logado da sessão
        $username = $_SESSION['user_username'] ?? null;
        if (!$username) {
            GenerateLog::generateLog('debug', 'Validação de senha na modal - usuário não autenticado', [
                'session' => $_SESSION,
                'input' => $input,
                'username' => $username
            ]);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado!']);
            exit;
        }

        // Usa o serviço de validação com preserveSession=true para não destruir a sessão atual
        $validador = new ValidationUserLogin();
        $ok = $validador->validationUserLogin([
            'username' => $username,
            'password' => $senha
        ], true); // preserveSession=true para validação AJAX
        GenerateLog::generateLog('debug', 'Validação de senha na modal', [
            'session' => $_SESSION,
            'input' => $input,
            'username' => $username,
            'resultado' => $ok ? 'Senha OK' : 'Senha incorreta'
        ]);

        if ($ok) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Senha incorreta!']);
        }
        exit;
    }

    public function index(): void
    {
        $this->checarSessaoInvalidadaAjax();
        header('Content-Type: application/json');
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!$form) {
            echo json_encode(['success' => false, 'message' => 'Dados não recebidos.']);
            return;
        }
        $repo = new AdmsPasswordPolicyRepository();
        // Se não veio id, buscar o id do registro existente
        if (empty($form['id'])) {
            $policy = $repo->getPolicy();
            if ($policy && isset($policy->id)) {
                $form['id'] = $policy->id;
            } else {
                echo json_encode(['success' => false, 'message' => 'Registro de política de senha não encontrado.']);
                return;
            }
        }
        $nivel = $form['nivel_seguranca'] ?? 'Baixo';
        $exemplos = [
            'Baixo' => 'abcd',
            'Médio' => 'Abcde1',
            'Elevado' => 'Abcde12@',
            'Customizado' => $form['exemplo_senha'] ?? ''
        ];
        // Garantir que os campos sejam inteiros válidos
        $form['tentativas_bloqueio_temporario'] = isset($form['tentativas_bloqueio_temporario']) && $form['tentativas_bloqueio_temporario'] !== '' ? (int)$form['tentativas_bloqueio_temporario'] : 3;
        $form['tempo_bloqueio_temporario'] = isset($form['tempo_bloqueio_temporario']) && $form['tempo_bloqueio_temporario'] !== '' ? (int)$form['tempo_bloqueio_temporario'] : 15;
        $dados['expirar_sessao_por_tempo'] = $form['expirar_sessao_por_tempo'] ?? 'Não';
        $dados['tempo_expiracao_sessao'] = isset($form['tempo_expiracao_sessao']) ? (int)$form['tempo_expiracao_sessao'] : 30;
        $data = [
            'id' => $form['id'],
            'nivel_seguranca' => $nivel,
            'vencimento_dias' => $form['vencimento_dias'] ?? 90,
            'comprimento_minimo' => $form['comprimento_minimo'] ?? 8,
            'min_maiusculas' => $form['min_maiusculas'] ?? 0,
            'min_minusculas' => $form['min_minusculas'] ?? 0,
            'min_digitos' => $form['min_digitos'] ?? 0,
            'min_nao_alfanumericos' => $form['min_nao_alfanumericos'] ?? 0,
            'historico_senhas' => $form['historico_senhas'] ?? 5,
            'tentativas_bloqueio' => $form['tentativas_bloqueio'] ?? 5,
            'tentativas_bloqueio_temporario' => $form['tentativas_bloqueio_temporario'],
            'tempo_bloqueio_temporario' => $form['tempo_bloqueio_temporario'],
            'bloqueio_temporario' => isset($form['bloqueio_temporario']) && $form['bloqueio_temporario'] === 'Sim' ? 'Sim' : 'Não',
            'notificar_usuario_bloqueio' => isset($form['notificar_usuario_bloqueio']) && $form['notificar_usuario_bloqueio'] === 'Sim' ? 'Sim' : 'Não',
            'notificar_admins_bloqueio' => isset($form['notificar_admins_bloqueio']) && $form['notificar_admins_bloqueio'] === 'Sim' ? 'Sim' : 'Não',
            'forcar_logout_troca_senha' => isset($form['forcar_logout_troca_senha']) && $form['forcar_logout_troca_senha'] === 'Sim' ? 'Sim' : 'Não',
            'expirar_sessao_por_tempo' => $form['expirar_sessao_por_tempo'] ?? 'Não',
            'tempo_expiracao_sessao' => isset($form['tempo_expiracao_sessao']) ? (int)$form['tempo_expiracao_sessao'] : 30,
            'exemplo_senha' => $nivel !== 'Customizado' ? $exemplos[$nivel] : $exemplos['Customizado'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $result = $repo->update($data);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Política de senha atualizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar política de senha.']);
        }
    }
} 