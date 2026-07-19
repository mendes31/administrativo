<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\RhCandidaturaMotivoCatalog;
use App\adms\Models\Services\RhPermissionService;

/**
 * Controller para atualizar status de candidatura (pipeline).
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

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_rh_atualizar_status_candidatura', $csrfToken)) {
            echo json_encode([
                'success' => false,
                'message' => 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.',
            ]);
            exit;
        }

        $candidatoId = (int) ($_POST['candidato_id'] ?? 0);
        $vagaId = (int) ($_POST['vaga_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $motivoCodigo = trim($_POST['motivo_codigo'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($candidatoId <= 0 || $vagaId <= 0 || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Dados obrigatórios não fornecidos.']);
            exit;
        }

        if (!RhPermissionService::canManagePipelineByVagaId($vagaId)) {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para alterar o status desta candidatura.']);
            exit;
        }

        if (!\App\adms\Models\Services\RhPipelineStageCatalog::isValidCode($status)) {
            echo json_encode(['success' => false, 'message' => 'Status inválido.']);
            exit;
        }
        if ($status === 'em_analise') {
            $status = 'em_entrevista';
        }

        if ($motivoCodigo === '') {
            echo json_encode(['success' => false, 'message' => 'Selecione o motivo da movimentação.']);
            exit;
        }

        if (!RhCandidaturaMotivoCatalog::isValidForStatus($status, $motivoCodigo)) {
            echo json_encode(['success' => false, 'message' => 'Motivo inválido para o status selecionado.']);
            exit;
        }

        if (RhCandidaturaMotivoCatalog::requiresObservacao($motivoCodigo) && $observacoes === '') {
            echo json_encode(['success' => false, 'message' => 'Descreva o motivo em observações quando escolher "Outro".']);
            exit;
        }

        try {
            $repo = new RhVagasRepository();
            $ok = $repo->atualizarStatusVinculo(
                $vagaId,
                $candidatoId,
                $status,
                $observacoes !== '' ? $observacoes : null,
                \App\adms\Models\Repository\RhCandidaturaHistoricoRepository::ORIGEM_PIPELINE,
                null,
                $motivoCodigo
            );

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
                'vaga_id' => $vagaId,
                'status' => $status,
                'motivo_codigo' => $motivoCodigo,
                'error' => $e->getMessage(),
            ]);
            echo json_encode([
                'success' => false,
                'message' => 'Erro inesperado: ' . $e->getMessage(),
            ]);
        }
        exit;
    }
}
