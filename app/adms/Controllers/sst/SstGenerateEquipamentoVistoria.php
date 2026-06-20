<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\SstEquipamentoVistoriaGeneratorService;

class SstGenerateEquipamentoVistoria
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        $equipamentoId = (int) ($_POST['adms_sst_equipamento_id'] ?? 0);
        $redirect = $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId;

        if ($equipamentoId <= 0 || !CSRFHelper::validateCSRFToken('sst_generate_equipamento_vistoria', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Requisição inválida.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($equipamentoId > 0 ? $redirect : $_ENV['URL_ADM'] . 'sst-list-equipamentos'));
            exit;
        }

        $competencia = trim((string) ($_POST['competencia'] ?? ''));
        if ($competencia !== '' && !preg_match('/^\d{4}-\d{2}$/', $competencia)) {
            $competencia = '';
        }

        $result = (new SstEquipamentoVistoriaGeneratorService())->tryGenerateManual(
            $equipamentoId,
            $competencia !== '' ? $competencia : null
        );

        $_SESSION['msg'] = $result['message'];
        $_SESSION['msg_type'] = match ($result['code']) {
            'created' => 'success',
            'completed' => 'warning',
            'exists' => 'info',
            default => 'danger',
        };

        if ($result['ok'] && !empty($result['vistoria_id'])) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . (int) $result['vistoria_id']);
            exit;
        }

        if (!$result['ok'] && !empty($result['vistoria_id']) && in_array($result['code'], ['exists', 'completed'], true)) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . (int) $result['vistoria_id']);
            exit;
        }

        header('Location: ' . $redirect);
        exit;
    }
}
