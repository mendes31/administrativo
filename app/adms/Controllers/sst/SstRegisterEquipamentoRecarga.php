<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
use App\adms\Models\Repository\SstEquipamentoRecargasRepository;
use App\adms\Models\Repository\SstEquipamentosRepository;

class SstRegisterEquipamentoRecarga
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sst_equipamento_recarga_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        $equipamentoId = (int) ($_POST['adms_sst_equipamento_id'] ?? 0);
        $equipamento = (new SstEquipamentosRepository())->getById($equipamentoId);
        if (!$equipamento) {
            $_SESSION['msg'] = 'Equipamento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        if (empty($equipamento['controla_recarga'])) {
            $_SESSION['msg'] = 'Este tipo de equipamento não controla recarga.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId);
            exit;
        }

        $dataRecarga = trim((string) ($_POST['data_recarga'] ?? ''));
        if ($dataRecarga === '') {
            $_SESSION['msg'] = 'Informe a data da recarga.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId);
            exit;
        }

        $proximaInformada = trim((string) ($_POST['data_proxima_recarga'] ?? ''));
        $meses = (int) ($equipamento['validade_recarga_meses'] ?? 12);
        if ($meses <= 0) {
            $meses = 12;
        }

        $id = (new SstEquipamentoRecargasRepository())->register([
            'adms_sst_equipamento_id' => $equipamentoId,
            'tipo_evento' => $_POST['tipo_evento'] ?? 'Recarga',
            'data_recarga' => $dataRecarga,
            'data_proxima_recarga' => $proximaInformada !== '' ? $proximaInformada : null,
            'validade_meses' => $meses,
            'empresa' => $_POST['empresa'] ?? null,
            'numero_documento' => $_POST['numero_documento'] ?? null,
            'observacao' => $_POST['observacao'] ?? null,
        ]);

        if ($id) {
            $proxima = $proximaInformada !== ''
                ? $proximaInformada
                : SstEquipamentoRecargaHelper::calcularProxima($dataRecarga, $meses);
            $_SESSION['msg'] = 'Recarga registrada. Próxima data: ' . date('d/m/Y', strtotime($proxima)) . '.';
            $_SESSION['msg_type'] = 'success';
        } else {
            $_SESSION['msg'] = 'Erro ao registrar a recarga.';
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId);
        exit;
    }
}
