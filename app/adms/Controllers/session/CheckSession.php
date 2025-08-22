<?php

namespace App\adms\Controllers\session;

use App\adms\Models\Repository\AdmsSessionsRepository;

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
        if (empty($_SESSION['user_id'])) {
            $this->sendJsonResponse(['valid' => false, 'message' => 'Usuário não autenticado']);
            return;
        }

        // Buscar configuração da política de senhas
        $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        
        // Verificar se a expiração por tempo está habilitada
        $expirarPorTempo = ($policy && isset($policy->expirar_sessao_por_tempo) && $policy->expirar_sessao_por_tempo === 'Sim');
        
        if (!$expirarPorTempo) {
            // Se não estiver habilitado, sempre retornar válida
            $this->sendJsonResponse(['valid' => true, 'message' => 'Expiração por tempo desabilitada']);
            return;
        }

        // Verificar se a sessão existe no banco
        $sessionsRepository = new AdmsSessionsRepository();
        $sessionData = $sessionsRepository->getSessionBySessionId($_SESSION['session_id'] ?? '');
        
        if (!$sessionData) {
            $this->sendJsonResponse(['valid' => false, 'message' => 'Sessão inválida']);
            return;
        }

        // Verificar se a sessão não expirou (fallback robusto)
        $updatedAt = $sessionData['updated_at'] ?? null;
        $createdAt = $sessionData['created_at'] ?? null;
        $lastActivity = null;
        if (!empty($updatedAt)) {
            $lastActivity = strtotime($updatedAt);
        }
        if (!$lastActivity && !empty($createdAt)) {
            $lastActivity = strtotime($createdAt);
        }
        if (!$lastActivity) {
            $lastActivity = time(); // fallback defensivo
        }
        $sessionTimeout = ($policy && isset($policy->tempo_expiracao_sessao)) ? ((int)$policy->tempo_expiracao_sessao * 60) : 1800; // Padrão 30 minutos
        $currentTime = time();
        
        if (($currentTime - $lastActivity) > $sessionTimeout) {
            // Sessão expirou
            $sessionsRepository->deleteSessionBySessionId($_SESSION['session_id']);
            session_destroy();
            
            $this->sendJsonResponse(['valid' => false, 'message' => 'Sessão expirada']);
            return;
        }

        // Calcular tempo restante
        $expiresIn = $sessionTimeout - ($currentTime - $lastActivity);
        
        $this->sendJsonResponse([
            'valid' => true,
            'message' => 'Sessão válida',
            'expiresIn' => $expiresIn,
            'lastActivity' => date('Y-m-d H:i:s', $lastActivity)
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