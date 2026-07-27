<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\RhEntrevistaAvaliadoresRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Services\RhEntrevistaAvaliadorConviteService;
use App\adms\Models\Services\RhPermissionService;

/**
 * POST: gestor/RH reenvia convite a avaliador adicional (convidado/recusado).
 */
class RhEntrevistasReenviarConviteAvaliador
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
        if (!CSRFHelper::validateCSRFToken('form_reenviar_convite_avaliador', $csrfToken)) {
            $_SESSION['error'] = 'Token de segurança inválido ou expirado. Recarregue a página e tente novamente.';
            $this->redirect($entrevistaId);
            return;
        }

        $avaliadorId = (int) ($_POST['avaliador_id'] ?? 0);
        if ($entrevistaId <= 0 || $avaliadorId <= 0) {
            $_SESSION['error'] = 'Dados inválidos para reenvio.';
            $this->redirect($entrevistaId);
            return;
        }

        $entrevista = (new RhEntrevistasRepository())->getById($entrevistaId);
        if ($entrevista === null) {
            $_SESSION['error'] = 'Entrevista não encontrada.';
            $this->redirect(0);
            return;
        }

        if (!RhPermissionService::canManageEntrevista($entrevista)) {
            $_SESSION['error'] = 'Você não tem permissão para reenviar este convite.';
            $this->redirect($entrevistaId);
            return;
        }

        $repo = new RhEntrevistaAvaliadoresRepository();
        $row = $repo->getByEntrevistaAndAvaliador($entrevistaId, $avaliadorId);
        if ($row === null || ($row['papel'] ?? '') !== RhEntrevistaAvaliadoresRepository::PAPEL_AVALIADOR) {
            $_SESSION['error'] = 'Avaliador adicional não encontrado no painel.';
            $this->redirect($entrevistaId);
            return;
        }

        $status = (string) ($row['status'] ?? '');
        if (!in_array($status, [
            RhEntrevistaAvaliadoresRepository::STATUS_CONVIDADO,
            RhEntrevistaAvaliadoresRepository::STATUS_RECUSADO,
        ], true)) {
            $_SESSION['error'] = 'Só é possível reenviar convite com status convidado ou recusado.';
            $this->redirect($entrevistaId);
            return;
        }

        try {
            $repo->marcarComoConvidado($entrevistaId, $avaliadorId);
            (new RhEntrevistaAvaliadorConviteService())->reenviar($entrevistaId, $avaliadorId);
            $_SESSION['success'] = 'Convite reenviado ao avaliador.';
            $this->redirect($entrevistaId);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Falha ao reenviar convite de avaliador.', [
                'entrevista_id' => $entrevistaId,
                'avaliador_id' => $avaliadorId,
                'error' => $e->getMessage(),
            ]);
            $_SESSION['error'] = 'Erro ao reenviar o convite.';
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
