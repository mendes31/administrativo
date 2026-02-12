<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;

/**
 * Controller para atualizar status de candidatura (pipeline).
 * Exemplo: Candidatado -> Em Análise -> Aprovado/Reprovado
 */
class RhAtualizarStatusCandidatura
{
    public function index(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $candidatoId = (int)($_POST['candidato_id'] ?? 0);
        $vagaId = (int)($_POST['vaga_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($candidatoId <= 0 || $vagaId <= 0 || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios não fornecidos.']);
            exit;
        }

        $statusValidos = ['candidatado', 'em_analise', 'aprovado', 'reprovado', 'desistiu'];
        if (!in_array($status, $statusValidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Status inválido.']);
            exit;
        }

        try {
            $repo = new RhVagasRepository();
            $ok = $repo->atualizarStatusVinculo($vagaId, $candidatoId, $status, $observacoes ?: null);

            if ($ok) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso!',
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar status.',
                ]);
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar status de candidatura.', [
                'candidato_id' => $candidatoId,
                'vaga_id'      => $vagaId,
                'status'       => $status,
                'error'        => $e->getMessage(),
            ]);
            echo json_encode([
                'success' => false,
                'message' => 'Erro inesperado: ' . $e->getMessage(),
            ]);
        }
        exit;
    }
}

