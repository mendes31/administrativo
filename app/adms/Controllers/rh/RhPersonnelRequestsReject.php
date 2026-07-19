<?php

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\RhPersonnelRequestService;

class RhPersonnelRequestsReject
{
    public function index(int|string $id): void
    {
        $id = (int) $id;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_reject_rh_personnel_request', $token)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
            return;
        }

        try {
            $service = new RhPersonnelRequestService();
            $service->reject($id, (int) ($_SESSION['user_id'] ?? 0), (string) ($_POST['rejection_reason'] ?? ''));
            $_SESSION['success'] = 'Requisição rejeitada.';
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao rejeitar requisição de pessoal.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'rh-personnel-requests-view/' . $id);
    }
}
