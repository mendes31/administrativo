<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstTreinamentoNecessidadeRepository;

class SstSaveMatrizTreinamentoCargo
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-matriz-treinamento-cargo');
            exit;
        }

        $positionId = (int) ($_POST['adms_position_id'] ?? 0);
        $departmentId = (int) ($_POST['adms_department_id'] ?? 0) ?: null;
        $redirect = $_ENV['URL_ADM'] . 'sst-matriz-treinamento-cargo/' . $positionId;
        if ($departmentId) {
            $redirect .= '?adms_department_id=' . $departmentId;
        }

        if ($positionId <= 0 || !CSRFHelper::validateCSRFToken('sst_matriz_treinamento_cargo', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Operação inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-matriz-treinamento-cargo');
            exit;
        }

        $treinamentoIds = is_array($_POST['treinamentos'] ?? null) ? array_map('intval', $_POST['treinamentos']) : [];
        (new SstTreinamentoNecessidadeRepository())->syncMatrizForPosition($positionId, $departmentId, $treinamentoIds);

        $_SESSION['msg'] = 'Matriz do cargo salva. Execute a sincronização de vínculos para gerar pendências.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $redirect);
        exit;
    }
}
