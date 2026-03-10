<?php

namespace App\adms\Controllers\policies;

use App\adms\Models\Repository\PoliciesRepository;

/**
 * Endpoint AJAX para confirmar ciência de políticas internas.
 *
 * Espelha AcknowledgeInformativo, mas utilizando a tabela de políticas.
 */
class AcknowledgePolicy
{
    public function index(): void
    {
        // Verificar se é uma requisição AJAX
        if (
            !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
        ) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verificar se o usuário está logado
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
            return;
        }

        // Verificar se o método é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }

        // Obter o ID da política a partir da URL
        $urlParts = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
        $policyId = (int) end($urlParts);

        if ($policyId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da política inválido']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];

        try {
            $repo = new PoliciesRepository();

            $policy = $repo->getPolicyById($policyId);
            if (!$policy) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Política não encontrada']);
                return;
            }

            if (empty($policy['requires_ack'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Esta política não exige confirmação de ciência']);
                return;
            }

            $ok = $repo->acknowledge($policyId, $userId);
            if ($ok) {
                echo json_encode([
                    'success'   => true,
                    'message'   => 'Ciência confirmada com sucesso',
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao confirmar ciência']);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }
}

