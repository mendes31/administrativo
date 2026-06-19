<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Services\SstAsoSolicitacaoService;

/**
 * Abre solicitação de ASO (status aguardando exames) a partir de uma pendência.
 */
class SstAbrirAsoPendencia
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['msg'] = 'Método não permitido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-report-pendencias');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sst_abrir_aso_pendencia', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-report-pendencias');
            exit;
        }

        $userId = (int) ($_POST['adms_user_id'] ?? 0);
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $redirect = trim((string) ($_POST['redirect'] ?? 'sst-report-pendencias'));
        $redirectUrl = $_ENV['URL_ADM'] . (str_contains($redirect, 'sst-') ? $redirect : 'sst-report-pendencias');

        if ($userId <= 0 || !SstCategoriaAsoHelper::isValid($categoria)) {
            $_SESSION['msg'] = 'Colaborador ou categoria ASO inválidos.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectUrl);
            exit;
        }

        try {
            $repo = new SstAsosRepository();
            $existente = $repo->findAguardando($userId, $categoria);
            if ($existente !== null) {
                $_SESSION['msg'] = 'Já existe um ASO aguardando resultados para este colaborador e tipo.';
                $_SESSION['msg_type'] = 'info';
                header('Location: ' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . (int) $existente['id']);
                exit;
            }

            $asoId = (new SstAsoSolicitacaoService())->abrirFromPacote($userId, $categoria);
            $_SESSION['msg'] = 'Solicitação de ASO aberta. Registre os resultados após a realização dos exames.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . $asoId);
        } catch (\Throwable $e) {
            $_SESSION['msg'] = 'Erro ao abrir ASO: ' . $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirectUrl);
        }
        exit;
    }
}
