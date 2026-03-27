<?php

namespace App\adms\Controllers\session;

use App\adms\Models\Repository\AdmsSessionsRepository;

/**
 * Controller para estender a sessão do usuário
 */
class ExtendSession
{
    /**
     * Estender a sessão atual
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
            $this->sendJsonResponse(['error' => 'Usuário não autenticado'], 401);
            return;
        }

        // Buscar configuração da política de senhas
        $policyRepo = new \App\adms\Models\Repository\AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        
        // Verificar se a expiração por tempo está habilitada
        $expirarPorTempo = ($policy && isset($policy->expirar_sessao_por_tempo) && $policy->expirar_sessao_por_tempo === 'Sim');
        
        if (!$expirarPorTempo) {
            $this->sendJsonResponse(['error' => 'Expiração por tempo desabilitada'], 400);
            return;
        }

        // Atualizar última atividade no banco
        $sessionsRepository = new AdmsSessionsRepository();
        $result = $sessionsRepository->updateSessionActivity($_SESSION['user_id'], $_SESSION['session_id']);
        
        // Sempre atualiza atividade na sessão PHP para o cliente não receber 500 por falha pontual no BD
        $_SESSION['last_activity'] = time();

        if ($result) {
            $this->sendJsonResponse([
                'success' => true,
                'message' => 'Sessão estendida com sucesso',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return;
        }

        $this->sendJsonResponse([
            'success' => true,
            'message' => 'Atividade registrada localmente',
            'timestamp' => date('Y-m-d H:i:s'),
            'best_effort' => true,
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