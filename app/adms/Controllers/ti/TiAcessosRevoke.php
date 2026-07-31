<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiAcessoRepository;
use App\adms\Models\Services\TiAcessoService;
use Exception;

final class TiAcessosRevoke
{
    public function index(int|string $id = 0): void
    {
        $acessoId = (int) $id;
        if ($acessoId <= 0) {
            $acessoId = (int) ($_POST['acesso_id'] ?? 0);
        }

        $csrfOk = CSRFHelper::validateCSRFToken(
            'form_ti_acesso_revoke',
            (string) ($_POST['csrf_token'] ?? '')
        );

        $acesso = (new TiAcessoRepository())->getById($acessoId);
        $fallback = ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas';
        if ($acesso !== null) {
            $fallback = ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . (int) $acesso['ti_sistema_id'];
        }
        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        $redirect = ($returnTo !== '' && str_starts_with($returnTo, ($_ENV['URL_ADM'] ?? '')))
            ? $returnTo
            : $fallback;

        if (!$csrfOk || $acessoId <= 0) {
            $_SESSION['msg'] = 'Requisição inválida para revogar acesso.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $redirect);
            exit;
        }

        try {
            (new TiAcessoService())->revogar(
                $acessoId,
                (int) ($_SESSION['user_id'] ?? 0),
                trim((string) ($_POST['data_revogacao'] ?? '')) ?: null,
                trim((string) ($_POST['observacoes'] ?? '')) ?: null
            );
            $_SESSION['msg'] = 'Acesso marcado como inativado no mapa.';
            $_SESSION['msg_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $redirect);
        exit;
    }
}
