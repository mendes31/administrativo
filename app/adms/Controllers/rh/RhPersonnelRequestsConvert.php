<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\RhPersonnelRequestService;

class RhPersonnelRequestsConvert
{
    public function index(int|string $id): void
    {
        $id = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_convert_rh_personnel_request', $token)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
            return;
        }

        try {
            $service = new RhPersonnelRequestService();
            $vagaId = $service->convertToVaga($id, (int) ($_SESSION['user_id'] ?? 0), [
                'titulo' => trim((string) ($_POST['titulo'] ?? '')),
                'descricao' => $_POST['descricao'] ?? null,
                'status' => 'pausada',
                'responsavel_id' => (int) ($_SESSION['user_id'] ?? 0),
            ]);
            $_SESSION['success'] = 'Vaga criada a partir da requisição (status pausada). Complete os detalhes e abra quando estiver pronta.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-vagas-edit/' . $vagaId);
            return;
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao converter requisição em vaga.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
        }
    }
}
