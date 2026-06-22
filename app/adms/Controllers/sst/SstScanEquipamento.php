<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Services\SstEquipamentoQrScanService;
use App\adms\Views\Services\LoadViewService;

/**
 * Sem token: abre câmera para ler QR dentro do app.
 * Com token: resolve equipamento e redireciona para o checklist da vistoria.
 */
class SstScanEquipamento
{
    private array $data = [];

    public function index(string $token = ''): void
    {
        $token = trim($token);
        $userId = (int) ($_SESSION['user_id'] ?? 0);

        if ($userId <= 0) {
            $_SESSION['error'] = 'Faça login para realizar a vistoria.';
            $_SESSION['return_url'] = $_ENV['URL_ADM'] . 'sst-scan-equipamento'
                . ($token !== '' ? '/' . rawurlencode($token) : '');
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        if ($token === '') {
            $this->showScanner();
            return;
        }

        $result = (new SstEquipamentoQrScanService())->resolveForUser($token, $userId);

        if (!$result['ok']) {
            $_SESSION['msg'] = $result['message'];
            $_SESSION['msg_type'] = match ($result['code']) {
                'forbidden' => 'warning',
                'inactive' => 'warning',
                default => 'danger',
            };
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-scan-equipamento');
            exit;
        }

        $vistoriaId = (int) ($result['vistoria_id'] ?? 0);
        if ($vistoriaId <= 0) {
            $_SESSION['msg'] = 'Vistoria não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-scan-equipamento');
            exit;
        }

        if ($result['code'] === 'completed') {
            $_SESSION['msg'] = $result['message'];
            $_SESSION['msg_type'] = 'info';
        } elseif (($_GET['from'] ?? '') !== 'camera') {
            $_SESSION['msg'] = 'QR Code lido com sucesso.';
            $_SESSION['msg_type'] = 'success';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'sst-execute-equipamento-vistoria/' . $vistoriaId . '?from=qr');
        exit;
    }

    private function showScanner(): void
    {
        $pageElements = [
            'title_head' => 'Ler QR equipamento - SST',
            'menu' => 'sst-minhas-equipamento-vistorias',
            'buttonPermission' => ['SstScanEquipamento', 'SstExecuteEquipamentoVistoria', 'SstMinhasEquipamentoVistorias'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/scan_qr', $this->data))->loadView();
    }
}
