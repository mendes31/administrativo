<?php

namespace App\adms\Controllers\session;

use App\adms\Models\Repository\AdmsSessionsRepository;
use App\adms\Models\Repository\LogAcessosRepository;
use App\adms\Controllers\Services\RequestHelper;

/**
 * Controller para verificar se a sessão atual é válida
 */
class CheckSession
{
    /**
     * Verificar se a sessão atual é válida
     */
    public function index(): void
    {
        // Verificar se é uma requisição AJAX
        if (!$this->isAjaxRequest()) {
            $this->sendJsonResponse(['error' => 'Requisição inválida'], 400);
            return;
        }

        // Verificar se há uma sessão ativa
        if (empty($_SESSION['user_id']) || empty($_SESSION['session_id'])) {
            $this->sendJsonResponse(['valid' => false, 'message' => 'Usuário não autenticado', 'user_id' => null]);
            return;
        }

        // Buscar configuração da política de senhas
        $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        
        // Verificar se a expiração por tempo está habilitada
        $expirarPorTempo = ($policy && isset($policy->expirar_sessao_por_tempo) && $policy->expirar_sessao_por_tempo === 'Sim');
        
        if (!$expirarPorTempo) {
            // Se não estiver habilitado, sempre retornar válida
            $this->sendJsonResponse(['valid' => true, 'message' => 'Expiração por tempo desabilitada', 'user_id' => (int)$_SESSION['user_id']]);
            return;
        }

        // Verificar se a sessão existe no banco
        $sessionsRepository = new AdmsSessionsRepository();
        $sessionData = $sessionsRepository->getSessionByUserIdAndSessionId($_SESSION['user_id'], $_SESSION['session_id']);
        
        if (!$sessionData || $sessionData['status'] !== 'ativa') {
            $this->sendJsonResponse(['valid' => false, 'message' => 'Sessão inválida', 'user_id' => (int)$_SESSION['user_id']]);
            return;
        }

        // Calcular tempo restante baseado na última atividade
        $lastActivity = strtotime($sessionData['updated_at'] ?? $sessionData['created_at']);
        $sessionTimeout = ($policy && isset($policy->tempo_expiracao_sessao)) ? ((int)$policy->tempo_expiracao_sessao * 60) : 1800; // Padrão 30 minutos
        $currentTime = time();
        $expiresIn = $sessionTimeout - ($currentTime - $lastActivity);
        
        // Verificar se a sessão expirou
        if ($expiresIn <= 0) {
            // Sessão expirou - invalidar
            $sessionsRepository->invalidateSessionByUserIdAndSessionId($_SESSION['user_id'], $_SESSION['session_id']);
            
            // Registrar LOGOUT_TIMEOUT
            try {
                $logRepo = new LogAcessosRepository();
                $ip = RequestHelper::getClientIp();
                $ua = RequestHelper::getUserAgent();
                $logRepo->registrarAcesso((int)$_SESSION['user_id'], 'LOGOUT_TIMEOUT', $ip, $ua);
            } catch (\Throwable $t) { /* noop */ }
            
            $this->sendJsonResponse(['valid' => false, 'message' => 'Sessão expirada', 'user_id' => (int)$_SESSION['user_id']]);
            return;
        }
        
        $this->sendJsonResponse([
            'valid' => true,
            'message' => 'Sessão válida',
            'expiresIn' => $expiresIn,
            'lastActivity' => date('Y-m-d H:i:s', $lastActivity),
            'user_id' => (int)$_SESSION['user_id']
        ]);
    }

    /**
     * Verificar se é uma requisição AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Enviar resposta JSON
     */
    private function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}