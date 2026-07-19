<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\RhEntrevistaComunicacaoReenvioService;

/**
 * POST: reenvia comunicação failed/blocked (nova intenção + outbox).
 */
class RhEntrevistasResendComunicacao
{
    public function index(int|string $id = 0): void
    {
        $entrevistaFallback = (int) ($_POST['entrevista_id'] ?? $id);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método não permitido.';
            $this->redirectView($entrevistaFallback);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_resend_rh_entrevista_comunicacao', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            $this->redirectView($entrevistaFallback);
            return;
        }

        $comunicacaoId = (int) ($_POST['comunicacao_id'] ?? 0);
        if ($comunicacaoId <= 0) {
            $_SESSION['error'] = 'Comunicação inválida.';
            $this->redirectView($entrevistaFallback);
            return;
        }

        $actorUserId = (int) ($_SESSION['user_id'] ?? 0);

        try {
            $result = (new RhEntrevistaComunicacaoReenvioService())->reenviar($comunicacaoId, $actorUserId);
            $_SESSION['success'] = 'Reenvio registrado. A nova intenção aguarda preflight e worker SMTP.';
            $this->redirectView((int) $result['entrevista_id']);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Falha ao reenviar comunicação de entrevista.', [
                'comunicacao_id' => $comunicacaoId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = $e->getMessage();
            $this->redirectView($entrevistaFallback);
        }
    }

    private function redirectView(int $entrevistaId): void
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        if ($entrevistaId > 0) {
            header('Location: ' . $base . '/rh-entrevistas-view/' . $entrevistaId);
        } else {
            header('Location: ' . $base . '/rh-entrevistas');
        }
        exit;
    }
}
