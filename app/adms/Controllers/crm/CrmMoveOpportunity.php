<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;

/**
 * API para mover oportunidade entre estágios (Drag & Drop)
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmMoveOpportunity
{
    public function index(): void
    {
        header('Content-Type: application/json');

        // Verificar se é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            exit;
        }

        // Receber dados JSON
        $input = json_decode(file_get_contents('php://input'), true);

        $opportunityId = (int) ($input['opportunity_id'] ?? 0);
        $newStageId = (int) ($input['new_stage_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? 1;

        if (!$opportunityId || !$newStageId) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            exit;
        }

        // Mover oportunidade
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $result = $opportunitiesRepo->moveOpportunity($opportunityId, $newStageId, $userId);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Oportunidade movida com sucesso!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao mover oportunidade'
            ]);
        }
        exit;
    }
}

