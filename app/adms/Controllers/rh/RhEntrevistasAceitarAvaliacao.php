<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhEntrevistaAvaliadoresRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Services\RhEntrevistaAvaliadorConviteService;

/**
 * POST: avaliador convidado aceita participar da entrevista.
 */
class RhEntrevistasAceitarAvaliacao
{
    public function index(int|string $id = 0): void
    {
        $entrevistaId = (int) ($_POST['entrevista_id'] ?? $id);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método não permitido.';
            $this->redirect($entrevistaId);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFHelper::validateCSRFToken('form_aceitar_avaliacao_entrevista', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            $this->redirect($entrevistaId);
            return;
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($entrevistaId <= 0 || $userId <= 0) {
            $_SESSION['error'] = 'Convite inválido.';
            $this->redirect(0);
            return;
        }

        $entrevista = (new RhEntrevistasRepository())->getById($entrevistaId);
        if ($entrevista === null) {
            $_SESSION['error'] = 'Entrevista não encontrada.';
            $this->redirect(0);
            return;
        }

        try {
            $ok = (new RhEntrevistaAvaliadoresRepository())->aceitarConvite($entrevistaId, $userId);
            if (!$ok) {
                $_SESSION['error'] = 'Não há convite pendente para você nesta entrevista.';
                $this->redirect($entrevistaId);
                return;
            }

            (new NotificationsRepository())->markAsReadByEntity(
                $userId,
                RhEntrevistaAvaliadorConviteService::ENTITY_TYPE,
                $entrevistaId
            );

            $_SESSION['success'] = 'Convite aceito. Você já pode avaliar a entrevista.';
            $this->redirect($entrevistaId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Falha ao aceitar convite de avaliador.', [
                'entrevista_id' => $entrevistaId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao aceitar o convite.';
            $this->redirect($entrevistaId);
        }
    }

    private function redirect(int $entrevistaId): void
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
