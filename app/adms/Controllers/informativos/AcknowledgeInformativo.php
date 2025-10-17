<?php

namespace App\adms\Controllers\informativos;

use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Repository\AdmsSessionsRepository;

/**
 * Controller para confirmar ciência de informativos
 * 
 * @author Rafael Mendes de Oliveira
 * @since 2025-08-25
 */
class AcknowledgeInformativo
{
    /**
     * Processa a confirmação de ciência de um informativo
     * 
     * @return void
     */
    public function index(): void
    {
        // Verificar se é uma requisição AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Requisição inválida']);
            return;
        }

        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
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

        // Obter o ID do informativo da URL
        $urlParts = explode('/', $_SERVER['REQUEST_URI']);
        $informativoId = end($urlParts);
        
        if (!is_numeric($informativoId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID do informativo inválido']);
            return;
        }

        $informativoId = (int) $informativoId;
        $userId = (int) $_SESSION['user_id'];

        try {
            // Instanciar o repositório
            $informativosRepository = new InformativosRepository();
            
            // Verificar se o informativo existe e se exige ciência
            $informativo = $informativosRepository->getInformativoById($informativoId);
            
            if (!$informativo) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Informativo não encontrado']);
                return;
            }

            if (!$informativo['requires_ack']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Este informativo não exige confirmação de ciência']);
                return;
            }

            // Confirmar a ciência
            $result = $informativosRepository->acknowledge($informativoId, $userId);
            
            if ($result) {
                // Log da ação
                $logMessage = "Usuário ID {$userId} confirmou ciência do informativo ID {$informativoId}";
                file_put_contents(__DIR__ . '/../../../logs/informativos_ack.log', 
                    date('Y-m-d H:i:s') . ' - ' . $logMessage . PHP_EOL, 
                    FILE_APPEND | LOCK_EX
                );

                echo json_encode([
                    'success' => true, 
                    'message' => 'Ciência confirmada com sucesso',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao confirmar ciência']);
            }

        } catch (\Exception $e) {
            // Log do erro
            $errorMessage = "Erro ao confirmar ciência: " . $e->getMessage();
            file_put_contents(__DIR__ . '/../../../logs/informativos_ack_error.log', 
                date('Y-m-d H:i:s') . ' - ' . $errorMessage . PHP_EOL, 
                FILE_APPEND | LOCK_EX
            );

            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }
}
