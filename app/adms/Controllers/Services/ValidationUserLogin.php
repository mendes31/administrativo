<?php

namespace App\adms\Controllers\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\LoginRepository;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;
use App\adms\Controllers\Services\SecurityService;

class ValidationUserLogin
{
    /**
     * Retorna array de dados do usuário autenticado ou false em caso de falha
     * @param array $data
     * @param bool $preserveSession Se true, não destrói a sessão atual (usado para validação AJAX)
     * @return array|false
     */
    public function validationUserLogin(array $data, bool $preserveSession = false)
    {
        // Só limpar sessão se não for para preservar (usado para validação AJAX)
        if (!$preserveSession) {
            // Limpar sessão antiga, exceto CSRF token se necessário
            $csrf = $_SESSION['csrf_token'] ?? null;
            session_unset();
            if ($csrf) {
                $_SESSION['csrf_token'] = $csrf;
            }
            session_regenerate_id();
        }

        // Instanciar o Repository para validar o usuário no banco de dados
        $login = new LoginRepository();
        $result = $login->getUser((string) $data['username']);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Verificar se existe o registro no banco de dados
        if (!$result) {
            // Registrar tentativa: usuário não encontrado
            $login->registrarTentativaLogin(
                null,
                $data['username'],
                $ip,
                $userAgent,
                'USER_NOT_FOUND',
                'Usuário não encontrado'
            );
            // Chamar o método para salvar o log
            GenerateLog::generateLog("error", "Usuário incorreto.", ['username' => $data['username']]);
            // Criar a mensagem de erro 
            if (empty($_SESSION['error'])) {
                $_SESSION['error'] = "Usuário ou senha incorreta.";
            }
            return false;
        }
        // Verificar se o usuário está bloqueado ou inativo antes de validar a senha
        $motivos = [];
        if ($result && isset($result['status']) && $result['status'] === 'Inativo') {
            $motivos[] = 'Usuário Inativo';
        }
        if ($result && isset($result['bloqueado']) && $result['bloqueado'] === 'Sim') {
            // Se for bloqueio temporário, mostrar mensagem correta
            if (isset($result['data_bloqueio_temporario']) && $result['data_bloqueio_temporario']) {
                // Chamar verificação real do tempo de bloqueio
                $securityService = new SecurityService();
                if ($securityService->isUsuarioBloqueado($result)) {
                    // Incrementar tentativas mesmo durante o bloqueio
                    $securityService->processarSenhaIncorreta($result, $data['username'], $ip, $userAgent);
                    // Mensagem de erro já será setada pelo método
                    return false;
                }
            } else {
                $motivos[] = 'Usuário Bloqueado';
            }
        }
        if (!empty($motivos)) {
            $msg = implode(' e ', $motivos) . '! Contate o Administrador do sistema.';
            $_SESSION['error'] = $msg;
            return false;
        }
        if (password_verify($data['password'], $result['password'])) {
            // Registrar tentativa: sucesso
            $login->registrarTentativaLogin(
                $result['id'],
                $data['username'],
                $ip,
                $userAgent,
                'SUCCESS',
                null
            );
            // Se estava bloqueado temporariamente, reseta tudo
            if ($result['bloqueado'] === 'Sim' && $result['data_bloqueio_temporario']) {
                $login->resetarTentativasLoginCompleto($result['id']);
            } else {
                // Se não estava bloqueado, só zera tentativas
                $login->resetarTentativasLogin($result['id']);
            }
            
            // Só salvar dados na sessão se não for para preservar (usado para validação AJAX)
            if (!$preserveSession) {
                // Extrair o array para imprimir o elemento do array através do nome
                extract($result);
                // Salvar os dados do usuário na sessão
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_username'] = $username;
                $_SESSION['user_image'] = $image;
                $_SESSION['user_department'] = $dep_name;
                $_SESSION['user_position'] = $pos_name;
                // Limpar mensagem de erro da sessão após login bem-sucedido
                unset($_SESSION['error']);
                // Log temporário para depuração de sessões
                file_put_contents(__DIR__ . '/../../../logs/login_debug.log', date('Y-m-d H:i:s') . " - Login: username={$result['username']} id={$result['id']}\n", FILE_APPEND);
            }
            
            return $result;
        }
        // Incrementar tentativas de login e aplicar bloqueio se necessário
        $securityService = new SecurityService();
        $securityService->processarSenhaIncorreta($result, $data['username'], $ip, $userAgent);
        // Buscar limite de tentativas da política
        $policyRepo = new AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        $limiteTentativas = $policy ? (int)$policy->tentativas_bloqueio : 3; // valor padrão 3
        // Buscar tentativas ATUALIZADAS do usuário
        $userAtualizado = $login->getUserById($result['id']);
        // Se atingiu o limite definitivo, garantir que data_bloqueio_temporario seja NULL
        if ($userAtualizado && isset($userAtualizado['tentativas_login']) && $userAtualizado['tentativas_login'] >= $limiteTentativas) {
            $sql = 'UPDATE adms_users SET data_bloqueio_temporario = NULL WHERE id = :id';
            $stmt = $login->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $result['id']);
            $stmt->execute();
        }
        // Chamar o método para salvar o log
        GenerateLog::generateLog("error", "Senha incorreta.", ['username' => $data['password']]);
        // Só sobrescrever a mensagem de erro se ainda não houver uma mensagem específica
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = "Usuário ou senha incorreta.";
        }
        return false;
    }
}
