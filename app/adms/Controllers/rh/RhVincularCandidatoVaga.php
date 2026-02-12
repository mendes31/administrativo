<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhVagasRepository;

/**
 * Controller para vincular candidato a uma vaga.
 * Pode ser chamado tanto da tela do candidato quanto da tela da vaga.
 */
class RhVincularCandidatoVaga
{
    public function index(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
            exit;
        }

        $candidatosIds = $_POST['candidato_id'] ?? [];
        $vagasIds = $_POST['vaga_id'] ?? [];
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Garantir que sejam arrays
        if (!is_array($candidatosIds)) {
            $candidatosIds = [$candidatosIds];
        }
        if (!is_array($vagasIds)) {
            $vagasIds = [$vagasIds];
        }
        $candidatosIds = array_filter(array_map('intval', $candidatosIds));
        $vagasIds = array_filter(array_map('intval', $vagasIds));

        if (empty($candidatosIds) || empty($vagasIds)) {
            echo json_encode(['success' => false, 'message' => 'Selecione pelo menos um candidato e uma vaga.']);
            exit;
        }

        try {
            $repo = new RhVagasRepository();
            $sucessos = 0;
            $erros = 0;
            $mensagens = [];

            // Iterar sobre todas as combinações de candidato x vaga
            foreach ($candidatosIds as $candidatoId) {
                if ($candidatoId <= 0) {
                    continue;
                }
                foreach ($vagasIds as $vagaId) {
                    if ($vagaId <= 0) {
                        continue;
                    }
                    try {
                        $ok = $repo->vincularCandidato($vagaId, $candidatoId, $observacoes ?: null);
                        if ($ok) {
                            $sucessos++;
                        } else {
                            $erros++;
                            $mensagens[] = "Candidato ID {$candidatoId} já estava vinculado à vaga ID {$vagaId} ou houve erro.";
                        }
                    } catch (\Exception $e) {
                        $erros++;
                        $mensagens[] = "Candidato ID {$candidatoId} / Vaga ID {$vagaId}: " . $e->getMessage();
                    }
                }
            }

            if ($sucessos > 0) {
                $msg = "{$sucessos} candidato(s) vinculado(s) com sucesso!";
                if ($erros > 0) {
                    $msg .= " {$erros} candidato(s) não puderam ser vinculados.";
                }
                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'sucessos' => $sucessos,
                    'erros' => $erros,
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Nenhum candidato foi vinculado. ' . implode(' ', $mensagens),
                ]);
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao vincular candidato à vaga.', [
                'candidatos_ids' => $candidatosIds,
                'vagas_ids'      => $vagasIds,
                'error'          => $e->getMessage(),
            ]);
            echo json_encode([
                'success' => false,
                'message' => 'Erro inesperado: ' . $e->getMessage(),
            ]);
        }
        exit;
    }
}

