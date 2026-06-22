<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Helpers\SstEquipamentoQrHelper;
use App\adms\Models\Repository\SstEquipamentosRepository;

/**
 * Retorna PNG do QR Code do equipamento (etiqueta / impressão).
 */
class SstExportEquipamentoQr
{
    public function index(string|int $id = 0): void
    {
        $equipamentoId = (int) $id;
        if ($equipamentoId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        $repo = new SstEquipamentosRepository();
        $item = $repo->getById($equipamentoId);
        if (!$item) {
            $_SESSION['msg'] = 'Equipamento não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamentos');
            exit;
        }

        $token = $repo->ensureQrToken($equipamentoId);
        if ($token === null || $token === '') {
            $_SESSION['msg'] = 'Não foi possível gerar o token do QR Code.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId);
            exit;
        }

        $scanUrl = SstEquipamentoQrHelper::buildScanUrl($token);
        $png = SstEquipamentoQrHelper::renderPng($scanUrl, 320);

        if ($png === null) {
            $_SESSION['msg'] = 'Biblioteca de QR Code indisponível no servidor. Use a etiqueta na tela do equipamento.';
            $_SESSION['msg_type'] = 'warning';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento/' . $equipamentoId);
            exit;
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $filename = 'QR_' . preg_replace('/\W+/', '_', (string) ($item['codigo'] ?? 'equipamento')) . '.png';
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=3600');
        echo $png;
        exit;
    }
}
